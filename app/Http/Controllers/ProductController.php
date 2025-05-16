<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Requests\UpdateProductStatusRequest;
use App\Services\SallaAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
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
     * Get a list of products.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            $params = [];

            // Handle pagination and filtering
            if ($request->has('page')) {
                $params['page'] = $request->page;
            }

            if ($request->has('per_page')) {
                $params['per_page'] = $request->per_page;
            }

            if ($request->has('keyword')) {
                $params['keyword'] = $request->keyword;
            }

            if ($request->has('status')) {
                $params['status'] = $request->status;
            }

            // Call Salla API to get products
            $response = $this->service->request('GET', 'https://api.salla.dev/admin/v2/products', [
                'query' => $params
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch products: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get product details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            // Call Salla API to get product details
            $response = $this->service->request('GET', "https://api.salla.dev/admin/v2/products/{$id}");

            return response()->json([
                'message' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to fetch product {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Create a new product.
     *
     * @param  StoreProductRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreProductRequest $request)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            // Get validated data from the request
            $data = $request->validated();

            // Call Salla API to create product
            $response = $this->service->request('POST', 'https://api.salla.dev/admin/v2/products', [
                'json' => $data
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $response
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create product: ' . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Update an existing product.
     *
     * @param  UpdateProductRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateProductRequest $request, $id)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            // Get validated data from the request
            $data = $request->validated();

            // Call Salla API to update product
            $response = $this->service->request('PUT', "https://api.salla.dev/admin/v2/products/{$id}", [
                'json' => $data
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update product {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Delete a product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            // Call Salla API to delete product
            $response = $this->service->request('DELETE', "https://api.salla.dev/admin/v2/products/{$id}");

            return response()->json([
                'message' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to delete product {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Update product status.
     *
     * @param  UpdateProductStatusRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UpdateProductStatusRequest $request, $id)
    {
        try {
            // Set up the service with the authenticated user
            $this->service->forUser(auth()->user());
            
            // Get validated data from the request
            $data = $request->validated();

            // Call Salla API to update product status
            $response = $this->service->request('PUT', "https://api.salla.dev/admin/v2/products/{$id}/status", [
                'json' => [
                    'status' => $data['status']
                ]
            ]);

            return response()->json([
                'message' => 'success',
                'data' => $response
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to update status for product {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'error',
                'data' => [
                    'error' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
