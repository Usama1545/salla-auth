<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class TokenEncryption
{
    /**
     * Encrypt or decrypt a token
     *
     * @param string $token The token to encrypt/decrypt
     * @param bool $decrypt Whether to decrypt (true) or encrypt (false)
     * @return string The encrypted/decrypted token
     */
    public static function encrypt_decrypt($token, $decrypt = false)
    {
        if (empty($token)) {
            return $token;
        }
        
        try {
            if ($decrypt) {
                // Check if the token is actually encrypted
                if (self::isEncrypted($token)) {
                    return Crypt::decryptString($token);
                }
                return $token; // Return as is if not encrypted
            } else {
                // Only encrypt if not already encrypted
                if (!self::isEncrypted($token)) {
                    return Crypt::encryptString($token);
                }
                return $token; // Return as is if already encrypted
            }
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Token encryption/decryption error: ' . $e->getMessage());
            
            // If decryption fails, return the original token
            // This helps with backward compatibility
            return $token;
        }
    }
    
    /**
     * Check if a token appears to be encrypted
     *
     * @param string $token
     * @return bool
     */
    public static function isEncrypted($token)
    {
        // Encrypted tokens in Laravel start with "eyJ" (base64 encoded JSON)
        // and are typically longer than regular OAuth tokens
        return is_string($token) && 
               strlen($token) > 100 && 
               strpos($token, 'eyJ') === 0;
    }
}
