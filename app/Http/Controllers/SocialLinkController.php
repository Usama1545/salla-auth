<?php

namespace App\Http\Controllers;

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

}
