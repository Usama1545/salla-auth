<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:65535',
            'short_description' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:active,draft,out',
            'product_type' => 'nullable|string|in:product,digital,service,food',
            'require_shipping' => 'nullable|boolean',
            'unlimited_quantity' => 'nullable|boolean',
            'manage_quantity' => 'nullable|boolean',
            'main_image' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|string',
            'brand_id' => 'nullable|integer',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string',
            'options.*.display_type' => 'required_with:options.*.name|string|in:image,text,color',
            'options.*.values' => 'required_with:options.*.name|array',
            'options.*.values.*.name' => 'required_with:options.*.values|string',
            'metadata' => 'nullable|array',
            'metadata.*.key' => 'required_with:metadata|string',
            'metadata.*.value' => 'required_with:metadata.*.key|string',
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer',
            'hide_quantity' => 'nullable|boolean',
            'cost_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'is_pinned' => 'nullable|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'max_quantity_per_order' => 'nullable|integer|min:1',
            'mpn' => 'nullable|string',
            'gtin' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'name' => 'Product Name',
            'price' => 'Product Price',
            'sale_price' => 'Sale Price',
            'quantity' => 'Product Quantity',
            'sku' => 'Product SKU',
            'description' => 'Product Description',
            'short_description' => 'Short Description',
            'product_type' => 'Product Type',
            'main_image' => 'Main Product Image',
            'brand_id' => 'Brand',
            'categories' => 'Categories',
            'options' => 'Product Options',
            'related_products' => 'Related Products',
            'cost_price' => 'Cost Price',
            'max_quantity_per_order' => 'Maximum Quantity Per Order',
        ];
    }
}
