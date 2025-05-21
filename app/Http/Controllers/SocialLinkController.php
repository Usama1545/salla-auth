<?php

namespace App\Http\Controllers;

<<<<<<< Updated upstream
=======
use App\Helpers\TokenEncryption;
use App\Http\Requests\SocialLinkRequest;
use App\Models\SocialLink;
>>>>>>> Stashed changes
use App\Services\SallaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SocialLinkController extends Controller
{
    /**
     * @var SallaAuthService
     */
    protected $service;

    /**
     * Create a new controller instance.
     *
     * @param SallaAuthService $service
     */
    public function __construct(SallaAuthService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of social links for the authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $settings = [];

            $user = Auth::user();
            $socialLinks = $user->socialLinks()->get();
            foreach ($socialLinks as $value) {
                $settings[$value['name']] = $value['value'];
            }
            return response()->json([
                'message' => 'success',
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch social links: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $this->service->forUser($user);

            $storeInfo = $this->service->request('GET', 'https://api.salla.dev/admin/v2/store/info');
            $storeId = $storeInfo['data']['id'] ?? null;

            if (!$storeId) {
                throw new \Exception('Unable to retrieve store ID from Salla');
            }


            foreach ($request->all() as $name => $value) {
                $user->socialLinks()->updateOrCreate(['name' => $name, 'store_id' => $storeId,], ['name' => $name, 'value' => $value, 'store_id' => $storeId,]);
            }

            return response()->json([
                'message' => __('response.updated', ['object' => __("models.SocialLink")]),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create social link: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    public function settings()
    {
        $settings = [];

        $user = Auth::user();
        $socialLinks = $user->socialLinks()->get();
        foreach ($socialLinks as $value) {
            $settings[$value['name']] = $value['value'];
        }
        return response()->json($settings);
    }

    /**
     * Get social links by store ID
     *
     * @param Request $request
     * @param string $storeId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getByStoreId(Request $request, $storeId)
    {
        try {
            $name = $request->query('name');
            
            $query = SocialLink::where('store_id', $storeId);
            
            if ($name) {
                $query->where('name', $name);
            }
            
            $socialLinks = $query->get();
            
            // Format the response
            $formattedLinks = [];
            foreach ($socialLinks as $link) {
                // Skip access_token_conversion_facebook in the response for security
                if ($link->name === 'access_token_conversion_facebook') {
                    continue;
                }
                
                $formattedLinks[$link->name] = $link->value;
            }
            
            return response()->json([
                'message' => 'success',
                'data' => $formattedLinks
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch social links by store ID: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Track an event via Facebook Conversion API
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackFacebookEvent(Request $request)
    {
        try {
            $body = $request->json()->all();
            $user = Auth::user();
            
            // Set up the service with the authenticated user
            $this->service->forUser($user);
            
            // Get store info from Salla API to get the store_id
            $storeInfo = $this->service->request('GET', 'https://api.salla.dev/admin/v2/store/info');
            $storeId = $storeInfo['data']['id'] ?? null;
            
            if (!$storeId) {
                Log::error('Facebook Conversion API: Unable to retrieve store ID from Salla');
                return response()->json(['error' => 'Unable to retrieve store ID from Salla'], 500);
            }
            
            // Find the encrypted Facebook access token in social links
            $socialLink = SocialLink::where('store_id', $storeId)
                ->where('name', 'access_token_conversion_facebook')
                ->first();
                
            if (!$socialLink) {
                Log::error('Facebook Conversion API: Facebook access token not found');
                return response()->json(['error' => 'Facebook access token not configured'], 400);
            }
            
            // Decrypt the access token
            $accessToken = TokenEncryption::encrypt_decrypt($socialLink->value, true);
            
            if (empty($accessToken)) {
                Log::error('Facebook Conversion API: Invalid Facebook access token');
                return response()->json(['error' => 'Invalid Facebook access token'], 400);
            }

            // Get pixel IDs from the request body (base64 encoded string with newlines)
            $pixels = !empty($body['pixel']) ? explode("\n", base64_decode($body['pixel'])) : [];
            
            if (empty($pixels)) {
                return response()->json(['error' => 'No Facebook pixels configured'], 400);
            }

            // Get user IP and user agent
            $clientIpAddress = $request->ip();
            $clientUserAgent = $request->userAgent();
            
            $userData = [
                'client_ip_address' => $clientIpAddress,
                'client_user_agent' => $clientUserAgent,
            ];

            // Add Facebook click ID and browser ID if available
            if (!empty($body['fbc'])) {
                $userData['fbc'] = $body['fbc'];
            }

            if (!empty($body['fbp'])) {
                $userData['fbp'] = $body['fbp'];
            }

            // Generate event ID or use provided one
            $eventId = !empty($body['eventId']) ? $body['eventId'] : uniqid('', true);

            // Hash phone number for Lead events for privacy
            if ($body['eventName'] == 'Lead' && !empty($body['eventData']['lead_phone'])) {
                $userData['ph'] = hash('SHA256', $body['eventData']['lead_phone']);
            }

            // Prepare event data for Facebook API
            $eventData = [
                'data' => [
                    [
                        'event_name' => $body['eventName'],
                        'event_time' => time(),
                        'action_source' => 'website',
                        'event_id' => $eventId,
                        'user_data' => $userData,
                        'custom_data' => $body['eventData'] ?? [],
                        'event_source_url' => $body['eventSourceUrl'] ?? null,
                    ],
                ],
                'access_token' => $accessToken
            ];

            // Add order ID for purchase events
            if ($body['eventName'] == 'Purchase' && !empty($body['eventData']['order_id'])) {
                $eventData['data'][0]['order_id'] = $body['eventData']['order_id'];
            }

            $responses = [];
            $hasErrors = false;

            // Send data to each pixel ID
            foreach ($pixels as $pixel) {
                $pixel = trim($pixel);
                if (empty($pixel)) continue;

                $response = $this->sendToFacebook($pixel, $eventData);
                $responses[$pixel] = $response;
                
                if (isset($response['error'])) {
                    $hasErrors = true;
                }
            }

            // Return success or error based on responses
            if ($hasErrors) {
                return response()->json([
                    'message' => 'Some events failed to track',
                    'responses' => $responses
                ], 207); // 207 Multi-Status
            }

            return response()->json([
                'message' => 'Events tracked successfully',
                'responses' => $responses
            ]);
            
        } catch (\Exception $e) {
            Log::error('Facebook Conversion API Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Send event data to Facebook
     */
    private function sendToFacebook($pixelId, $eventData)
    {
        try {
            $body = json_encode($eventData);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v19.0/{$pixelId}/events");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            $curlResponse = curl_exec($ch);
            
            if ($curlResponse === false) {
                $errorMessage = curl_error($ch);
                curl_close($ch);
                return ['error' => $errorMessage];
            }
            
            curl_close($ch);
            return json_decode($curlResponse, true);
            
        } catch (\Exception $e) {
            Log::error('Facebook API request failed: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}
