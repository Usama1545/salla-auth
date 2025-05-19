<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialLinkRequest;
use App\Models\SocialLink;
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
            $user = Auth::user();
            $socialLinks = $user->socialLinks()->get();

            return response()->json([
                'message' => 'success',
                'data' => $socialLinks
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

    /**
     * Store a newly created social link in storage.
     *
     * @param  SocialLinkRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(SocialLinkRequest $request)
    {
        try {
            $user = Auth::user();

            // Set up the service with the authenticated user
            $this->service->forUser($user);

            // Get store info from Salla API to get the store_id
            $storeInfo = $this->service->request('GET', 'https://api.salla.dev/admin/v2/store/info');
            $storeId = $storeInfo['data']['id'] ?? null;

            if (!$storeId) {
                throw new \Exception('Unable to retrieve store ID from Salla');
            }

            // // Create the social link
            // $socialLink = new SocialLink([
            //     'user_id' => $user->id,
            //     'store_id' => $storeId,
            //     'name' => $request->name,
            //     'value' => $request->value,
            // ]);

            $socialLink = $user->socialLinks()->create([
                'store_id' => $storeId,
                'name' => $request->name,
                'value' => $request->value,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $socialLink
            ], 201);
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

    /**
     * Display the specified social link.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $user = Auth::user();
            $socialLink = $user->socialLinks()->findOrFail($id);

            return response()->json([
                'message' => 'success',
                'data' => $socialLink
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch social link: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Update the specified social link in storage.
     *
     * @param  SocialLinkRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(SocialLinkRequest $request, $id)
    {
        try {
            $user = Auth::user();
            $socialLink = $user->socialLinks()->findOrFail($id);

            $socialLink->update([
                'name' => $request->name,
                'value' => $request->value,
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $socialLink->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update social link: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Remove the specified social link from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $socialLink = $user->socialLinks()->findOrFail($id);

            $socialLink->delete();

            return response()->json([
                'message' => 'success',
                'data' => null
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete social link: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
