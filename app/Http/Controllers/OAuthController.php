<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Services\SallaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

            // Create or update the store
            if (isset($sallaUserData['store'])) {
                $store = Store::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'salla_id' => $sallaUserData['store']['id'] ?? null,
                        'owner_id' => $sallaUserData['store']['owner_id'] ?? null,
                        'owner_name' => $sallaUserData['store']['owner_name'] ?? null,
                        'username' => $sallaUserData['store']['username'] ?? null,
                        'name' => $sallaUserData['store']['name'] ?? null,
                        'avatar' => $sallaUserData['store']['avatar'] ?? null,
                        'store_location' => $sallaUserData['store']['store_location'] ?? null,
                        'plan' => $sallaUserData['store']['plan'] ?? null,
                        'status' => $sallaUserData['store']['status'] ?? null,
                        'salla_created_at' => $sallaUserData['store']['created_at'] ?? null,
                    ]
                );
            }

            // Delete existing tokens
            $user->tokens()->delete();

            // Create a new Sanctum token
            $sanctumToken = $user->createToken('auth_token')->plainTextToken;

            // Create or update OAuth token
            $user->token()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'access_token' => $token->getToken(),
                    'expires_in' => $token->getExpires(),
                    'refresh_token' => $token->getRefreshToken(),
                    'merchant' => $sallaUserData['store']['id'] ?? null,
                ]
            );

            return response()->json([
                'message' => 'success',
                'data' => [
                    'access_token' => $sanctumToken,
                    'token_type' => 'Bearer',
                    'user' => $user,
                    'salla_token' => [
                        'access_token' => $token->getToken(),
                        'expires_in' => $token->getExpires(),
                        'refresh_token' => $token->getRefreshToken()
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

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
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
            $this->service->forUser($request->user());
            $token = $this->service->getNewAccessToken();

            return response()->json([
                'message' => 'success',
                'data' => [
                    'access_token' => $token->getToken(),
                    'expires_in' => $token->getExpires(),
                    'refresh_token' => $token->getRefreshToken()
                ]
            ]);
        } catch (IdentityProviderException $e) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 401);
        }
    }
}
