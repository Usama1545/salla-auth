<?php

namespace App\Services;

use App\Helpers\TokenEncryption;
use App\Models\OauthToken;
use App\Models\User;
use Illuminate\Support\Traits\ForwardsCalls;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Salla\OAuth2\Client\Provider\Salla;
use Salla\OAuth2\Client\Provider\SallaUser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @mixin Salla
 */
class SallaAuthService
{
    use ForwardsCalls;

    /**
     * @var Salla
     */
    protected $provider;

    /**
     * @var AccessToken
     */
    public $token;

    /**
     * @var User
     */
    public $user;

    public function __construct()
    {
        $this->provider = new Salla([
            'clientId'     => config('services.salla.client_id'), // The client ID assigned to you by Salla
            'clientSecret' => config('services.salla.client_secret'), // The client password assigned to you by Salla
            'redirectUri'  => config('services.salla.redirect'), // the url for current page in your service
        ]);
    }

    /**
     * Set the user for the service.
     *
     * @param User $user
     * @return $this
     */
    public function forUser(User $user): self
    {
        $this->user = $user;

        if ($user->token) {
            // Always decrypt tokens from the database for internal use
            $this->token = new AccessToken([
                'access_token' => TokenEncryption::encrypt_decrypt($user->token->access_token, true),
                'refresh_token' => TokenEncryption::encrypt_decrypt($user->token->refresh_token, true),
                'expires' => $user->token->expires_in,
            ]);
        }

        return $this;
    }

    /**
     * Get the Salla provider instance
     * 
     * @return Salla
     */
    public function getProvider(): Salla
    {
        return $this->provider;
    }

    /**
     * Get the details of store for the current token.
     *
     *  {
     *      "id": 181690847,
     *      "name": "eman elsbay",
     *      "email": "user@salla.sa",
     *      "mobile": "555454545",
     *      "role": "user",
     *      "created_at": "2018-04-28 17:46:25",
     *      "store": {
     *        "id": 633170215,
     *        "owner_id": 181690847,
     *        "owner_name": "eman elsbay",
     *        "username": "good-store",
     *        "name": "متجر الموضة",
     *        "avatar": "https://cdn.salla.sa/XrXj/g2aYPGNvafLy0TUxWiFn7OqPkKCJFkJQz4Pw8WsS.jpeg",
     *        "store_location": "26.989000873354787,49.62477639657287",
     *        "plan": "special",
     *        "status": "active",
     *        "created_at": "2019-04-28 17:46:25"
     *      }
     *    }
     * @return \League\OAuth2\Client\Provider\ResourceOwnerInterface|SallaUser
     */
    public function getStoreDetail()
    {
        return $this->provider->getResourceOwner($this->token);
    }

    /**
     * Get A new access token via refresh token.
     *
     * @return \League\OAuth2\Client\Token\AccessToken|\League\OAuth2\Client\Token\AccessTokenInterface
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function getNewAccessToken()
    {
        try {
            // Always request a new access token via refresh token
            // The refresh token is already decrypted in the forUser method
            // Do NOT encrypt or decrypt it again here
            $token = $this->provider->getAccessToken('refresh_token', [
                'refresh_token' => $this->token->getRefreshToken()
            ]);

            // Store the new token in the service
            $this->token = $token;

            // Update the token in the database
            if ($this->user && $this->user->token) {
                // Encrypt tokens for storage
                $encryptedAccessToken = TokenEncryption::encrypt_decrypt($token->getToken());
                $encryptedRefreshToken = TokenEncryption::encrypt_decrypt($token->getRefreshToken());

                $this->user->token()->update([
                    'access_token'  => $encryptedAccessToken,
                    'expires_in'    => $token->getExpires(),
                    'expires_at'    => $this->calculateSafeExpiresAt($token->getExpires()),
                    'refresh_token' => $encryptedRefreshToken
                ]);

                // Refresh the user to get the updated token
                $this->user->refresh();
            }

            return $token;
        } catch (\Exception $e) {
            // If refresh token is invalid, we need to redirect to login
            throw new \Exception('Refresh token is invalid or expired. Please login again: ' . $e->getMessage());
        }
    }

    /**
     * Calculate a safe expires_at date that won't exceed MySQL's datetime limits
     *
     * @param int $expiresIn
     * @return \Carbon\Carbon
     */
    protected function calculateSafeExpiresAt($expiresIn)
    {
        // If expires_in is very large (likely a timestamp), use a safe default
        if ($expiresIn > 31536000) { // More than 1 year in seconds
            // Just use a safe default of 1 day
            return now()->addDay();
        }

        // Ensure expires_in is a reasonable value
        if ($expiresIn <= 0) {
            return now()->addHour(); // Default to 1 hour if value is invalid
        }

        // Normal case: expires_in is seconds from now (cap at 1 year to be safe)
        $expiresIn = min($expiresIn, 31536000); // Cap at 1 year
        return now()->addSeconds($expiresIn);
    }

    /**
     * Make a request to the Salla API.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
     * @param string $url API endpoint URL
     * @param array $options Request options
     * @return mixed
     * @throws \Exception
     */
    public function request(string $method, string $url, array $options = [])
    {
        // Check if the token is expired
        if($this->token->getExpires() < now()->getTimestamp()) {
            $this->getNewAccessToken();
        }
        
        // Log the request details for debugging
        Log::debug('Salla API Request', [
            'method' => $method,
            'url' => $url,
            'options' => $options
        ]);
        
        try {
            // Prepare the request options
            $requestOptions = [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ]
            ];
            
            // For GET requests, include query parameters
            if ($method === 'GET' && isset($options['query'])) {
                $url .= '?' . http_build_query($options['query']);
            } 
            // For POST, PUT, DELETE formats, directly set the body as a JSON string
            else if (isset($options['json'])) {
                // Convert the PHP array to a JSON string and set as body
                $requestOptions['body'] = json_encode($options['json']);
                
                Log::debug('Request body set as JSON', [
                    'json_body' => $requestOptions['body']
                ]);
            }
            
            // Make the API request with the formatted options
            $response = $this->provider->fetchResource(
                $method, 
                $url, 
                $this->token->getToken(), 
                $requestOptions
            );
            
            Log::debug('Salla API Response', [
                'status' => 'success',
                'response' => $response
            ]);
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Salla API Error', [
                'error' => $e->getMessage(),
                'method' => $method,
                'url' => $url
            ]);
            
            throw $e;
        }
    }

    /**
     * As shortcut to call the functions of provider class.
     *
     *
     * @param $name
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->forwardCallTo($this->provider, $name, $arguments);
    }

    /**
     * Determine if the authorization mode is easy
     *
     * @return bool
     */
    public function isEasyMode(): bool
    {
        return config('services.salla.authorization_mode') === 'custom';
    }

    /**
     * Requests and returns the resource owner of given access token.
     *
     * @param  AccessToken $token
     * @return ResourceOwnerInterface
     */
    public function getResourceOwner(?AccessToken $token)
    {
        return $this->provider->getResourceOwner($token ?: $this->token);
    }
}
