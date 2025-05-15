<?php

namespace App\Http\Middleware;

use App\Helpers\TokenEncryption;
use App\Models\OauthToken;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SallaTokenAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        
        if (!$bearerToken) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => 'Unauthorized - No token provided'
                ]
            ], 401);
        }
        
        // First, try to find the token in the database exactly as provided
        $oauthToken = OauthToken::where('access_token', $bearerToken)->first();
        
        // For debugging
        Log::debug('Token lookup: ' . substr($bearerToken, 0, 20) . '...');
        
        if (!$oauthToken) {
            Log::debug('Token not found directly, trying other methods');
            
            // Try to find the token in the database after encrypting it (in case client sent unencrypted token)
            $encryptedToken = TokenEncryption::encrypt_decrypt($bearerToken);
            $oauthToken = OauthToken::where('access_token', $encryptedToken)->first();
            
            // If still not found, try decrypting the provided token (in case it's double-encrypted)
            if (!$oauthToken) {
                Log::debug('Token not found after encryption, trying decryption');
                $decryptedToken = TokenEncryption::encrypt_decrypt($bearerToken, true);
                $oauthToken = OauthToken::where('access_token', $decryptedToken)->first();
            }
        }
        
        if (!$oauthToken) {
            Log::error('Token not found by any method: ' . substr($bearerToken, 0, 20) . '...');
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => 'Unauthorized - Invalid token'
                ]
            ], 401);
        }
        
        // Get the user associated with the token
        $user = $oauthToken->user;
        
        if (!$user) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => 'Unauthorized - User not found'
                ]
            ], 401);
        }
        
        // Check if token is expired, but allow expired tokens for the refresh token endpoint
        $isRefreshEndpoint = $request->is('api/oauth/refresh-token');
        if ($oauthToken->hasExpired() && !$isRefreshEndpoint) {
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => 'Token has expired',
                    'redirect' => route('oauth.redirect')
                ]
            ], 401);
        }
        
        // Set the authenticated user
        Auth::login($user);
        
        return $next($request);
    }
}
