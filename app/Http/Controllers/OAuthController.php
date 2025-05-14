<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SallaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

            /** @var \Salla\OAuth2\Client\Provider\SallaUser $user */
            $user = $this->service->getResourceOwner($token);
            /**
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
             */
            // var_export($user->toArray());

            // echo 'User ID: '.$user->getId()."<br>";
            // echo 'User Name: '.$user->getName()."<br>";
            // echo 'Store ID: '.$user->getStoreID()."<br>";
            // echo 'Store Name: '.$user->getStoreName()."<br>";

            //
            // 🥳
            //
            // You can now save the access token and refresh token in your database
            // with the merchant details and redirect him again to Salla dashboard (https://s.salla.sa/apps)
            // dd($request->user(), auth()->user());
            $request->user()->token()->delete();

            $request->user()->token()->create([
                'access_token'  => $token->getToken(),
                'expires_in'    => $token->getExpires(),
                'refresh_token' => $token->getRefreshToken()
            ]);

            return response()->json([
                'message' => 'success',
                'data' => [
                    'access_token' => $token->getToken(),
                    'expires_in' => $token->getExpires(),
                    'refresh_token' => $token->getRefreshToken()
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
            ]);
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
}
