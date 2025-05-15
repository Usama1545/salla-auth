<?php

namespace App\Http\Controllers;

use App\Helpers\TokenEncryption;
use App\Models\User;
use App\Models\Store;
use App\Models\OauthToken;
use App\Services\SallaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuthController extends Controller
{
    /**
     * @var SallaAuthService
     */
    private $service;

    public function __construct(SallaAuthService $service)
    {
        $this->service = $service;
    }

    public function redirect()
    {
        return response()->json([
            'url' => $this->service->getProvider()->getAuthorizationUrl()
        ]);
    }

    public function callback(Request $request)
    {
        abort_if($this->service->isEasyMode(), 401, 'The Authorization mode is not supported');

        try {
            $token = $this->service->getAccessToken('authorization_code', [
                'code' => $request->code ?? ''
            ]);

            /** @var \Salla\OAuth2\Client\Provider\SallaUser $sallaUser */
            $sallaUser = $this->service->getResourceOwner($token);
            $sallaUserData = $sallaUser->toArray();
            // Create or update the user based on Salla data
            $user = User::updateOrCreate(
                ['email' => $sallaUserData['email']],
                [
                    'name' => $sallaUserData['name'],
                    'password' => Hash::make(Str::random(16)), // Random password
                    'salla_id' => $sallaUserData['id'],
                    'mobile' => $sallaUserData['mobile'] ?? null,
                    'role' => $sallaUserData['role'] ?? null,
                    'salla_created_at' => $sallaUserData['created_at'] ?? null,
                ]
            );

            // Encrypt tokens for storage and response
            $encryptedAccessToken = TokenEncryption::encrypt_decrypt($token->getToken());
            $encryptedRefreshToken = TokenEncryption::encrypt_decrypt($token->getRefreshToken());

            // Create or update OAuth token
            $userToken = $user->token()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => $encryptedAccessToken,
                    'expires_in' => $token->getExpires(), // Keep for backward compatibility
                    'expires_at' => $this->calculateExpiresAt($token->getExpires()),
                    'refresh_token' => $encryptedRefreshToken,
                    'merchant' => $sallaUserData['merchant']['id'] ?? null,
                ]
            );

            // Refresh the user with token relationship
            $user->refresh();

            return response()->json([
                'message' => 'success',
                'data' => [
                    'user' => $user->load('token'),
                    'tokens' => [
                        'access_token' => $encryptedAccessToken,
                        'expires_in' => $token->getExpires(),
                        'refresh_token' => $encryptedRefreshToken
                    ]
                ]
            ]);
        } catch (IdentityProviderException $e) {
            // Failed to get the access token or merchant details.
            // show an error message to the merchant with good UI
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 401);
        }
    }
    /**
     * Refresh the Salla access token using the refresh token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshToken(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user || !$user->token) {
                return response()->json([
                    'message' => 'error',
                    'data' => [
                        'error' => 'User not authenticated or token not found'
                    ]
                ], 401);
            }

            // Set up the service with the user's current token
            $this->service->forUser($user);

            try {
                // Get a new token
                $token = $this->service->getNewAccessToken();

                // Store encrypted tokens in the database
                $encryptedAccessToken = TokenEncryption::encrypt_decrypt($token->getToken());
                $encryptedRefreshToken = TokenEncryption::encrypt_decrypt($token->getRefreshToken());
                
                // Update the token in the database
                $user->token()->update([
                    'access_token'  => $encryptedAccessToken,
                    'expires_in'    => $token->getExpires(),
                    'expires_at'    => $this->calculateExpiresAt($token->getExpires()),
                    'refresh_token' => $encryptedRefreshToken
                ]);

                // Refresh the user to get the updated token
                $user->refresh();

                return response()->json([
                    'message' => 'success',
                    'data' => [
                        'access_token' => $encryptedAccessToken,
                        'expires_in' => $token->getExpires(),
                        'refresh_token' => $encryptedRefreshToken
                    ]
                ]);
            } catch (\Exception $e) {
                // Log the error for debugging
                Log::error('Token refresh failed: ' . $e->getMessage());

                // If refresh token is invalid, return a specific error with redirect info
                return response()->json([
                    'message' => 'error',
                    'data' => [
                        'error' => 'Refresh token is invalid or expired: ' . $e->getMessage(),
                        'redirect' => route('oauth.redirect')
                    ]
                ], 401);
            }
        } catch (IdentityProviderException $e) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage(),
                    'redirect' => route('oauth.redirect')
                ]
            ], 401);
        }
    }

    /**
     * Get owner details from Salla
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOwnerDetails(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            if (!$user || !$user->token) {
                return response()->json([
                    'message' => 'error',
                    'data' => [
                        'error' => 'User not authenticated or token not found'
                    ]
                ], 401);
            }

            // Set the user for the service
            $this->service->forUser($user);

            // Get the resource owner (merchant) details from Salla
            $owner = $this->service->getResourceOwner(null);

            return response()->json([
                'message' => 'success',
                'data' => [
                    'owner' => $owner->toArray(),
                    'user' => $user->load('token')
                ]
            ]);
        } catch (IdentityProviderException $e) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Calculate the expires_at date based on expires_in value
     *
     * @param int $expiresIn
     * @return \Carbon\Carbon
     */
    protected function calculateExpiresAt($expiresIn)
    {
        // Add debugging
        Log::debug('Original expires_in value: ' . $expiresIn);
        
        // If expires_in is very large (likely a timestamp), use a safe default
        if ($expiresIn > 31536000) { // More than 1 year in seconds
            Log::warning('Token expiration too large: ' . $expiresIn . ', using 1 day default');
            return now()->addDay(); // Default to 1 day
        }
        
        // Ensure expires_in is a reasonable value
        if ($expiresIn <= 0) {
            Log::warning('Invalid expires_in value: ' . $expiresIn . ', defaulting to 1 hour');
            return now()->addHour(); // Default to 1 hour if value is invalid
        }

        // Normal case: expires_in is seconds from now (cap at 1 year to be safe)
        $expiresIn = min($expiresIn, 31536000); // Cap at 1 year
        $expiresAt = now()->addSeconds($expiresIn);
        Log::debug('Calculated expires_at: ' . $expiresAt->toDateTimeString());
        return $expiresAt;
    }
}
