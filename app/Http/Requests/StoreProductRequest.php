<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:50',
            'mpn' => 'nullable|string',
            'gtin' => 'nullable|string',
            'description' => 'nullable|string|max:65535',
            'short_description' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:active,draft,out',
            'product_type' => 'nullable|string|in:product,digital,service,food',
            'require_shipping' => 'nullable|boolean',
            'unlimited_quantity' => 'nullable|boolean',
            'manage_quantity' => 'nullable|boolean',
            'hide_quantity' => 'nullable|boolean',
            'enable_upload_image' => 'nullable|boolean',
            'enable_note' => 'nullable|boolean',
            'pinned' => 'nullable|boolean',
            'active_advance' => 'nullable|boolean',
            
            // Maximum allowed quantity in a single order
            'maximum_quantity_per_order' => 'nullable|integer|min:0',
            'max_quantity_per_order' => 'nullable|integer|min:1',
            
            // Weight information
            'weight' => 'nullable|numeric|min:0',
            'weight_type' => 'nullable|string|in:kg,g,lb,oz',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            
            // Donation related fields
            'min_amount_donating' => 'nullable|numeric|min:0',
            'max_amount_donating' => 'nullable|numeric|gt:min_amount_donating',
            
            // Dates
            'sale_end' => 'nullable|date|after:today',
            
            // SEO and display fields
            'subtitle' => 'nullable|string|max:255',
            'promotion_title' => 'nullable|string|max:255',
            'metadata_title' => 'nullable|string|max:255',
            'metadata_description' => 'nullable|string|max:500',
            
            // Relationship fields
            'brand_id' => 'nullable|integer',
            'categories' => 'nullable|array',
            'categories.*' => 'integer',
            'tags' => 'nullable|array',
            'tags.*' => 'integer',
            
            // Images - complex structure
            'images' => 'nullable|array',
            'images.*.original' => 'required_with:images|url',
            'images.*.thumbnail' => 'nullable|url',
            'images.*.alt' => 'nullable|string',
            'images.*.default' => 'nullable|boolean',
            'images.*.sort' => 'nullable|integer|min:0',
            
            // Main image if not using complex structure
            'main_image' => 'nullable|string',
            
            // Product options
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string',
            'options.*.display_type' => 'required_with:options.*.name|string|in:image,text,color',
            'options.*.values' => 'required_with:options.*.name|array',
            'options.*.values.*.name' => 'required_with:options.*.values|string',
            'options.*.values.*.price' => 'nullable|numeric|min:0',
            'options.*.values.*.quantity' => 'nullable|integer|min:0',
            
            // Custom fields
            'metadata' => 'nullable|array',
            'metadata.*.key' => 'required_with:metadata|string',
            'metadata.*.value' => 'required_with:metadata.*.key|string',
            
            // Related products
            'related_products' => 'nullable|array',
            'related_products.*' => 'integer',
            
            // Booking details
            'booking_details' => 'nullable|array',
            'booking_details.location' => 'nullable|string',
            'booking_details.type' => 'nullable|string|in:date,range,session',
            'booking_details.time_strict_value' => 'nullable|integer|min:1',
            'booking_details.time_strict_type' => 'nullable|string|in:days,hours,minutes',
            'booking_details.sessions_count' => 'nullable|integer|min:1',
            'booking_details.session_gap' => 'nullable|integer|min:0',
            'booking_details.session_duration' => 'nullable|integer|min:1',
            
            // Availabilities
            'booking_details.availabilities' => 'nullable|array',
            'booking_details.availabilities.*.day' => 'required_with:booking_details.availabilities|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'booking_details.availabilities.*.is_available' => 'required_with:booking_details.availabilities.*.day|boolean',
            'booking_details.availabilities.*.times' => 'nullable|array',
            'booking_details.availabilities.*.times.*.from' => 'required_with:booking_details.availabilities.*.times|date_format:H:i',
            'booking_details.availabilities.*.times.*.to' => 'required_with:booking_details.availabilities.*.times.*.from|date_format:H:i|after:booking_details.availabilities.*.times.*.from',
            
            // Overrides
            'booking_details.overrides' => 'nullable|array',
            'booking_details.overrides.*.day' => 'required_with:booking_details.overrides|string|in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
            'booking_details.overrides.*.date' => 'required_with:booking_details.overrides.*.day|date',
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
            'maximum_quantity_per_order' => 'Maximum Quantity Per Order',
            'weight' => 'Weight',
            'weight_type' => 'Weight Unit',
            'images.*.original' => 'Image URL',
            'images.*.alt' => 'Image Alt Text',
            'min_amount_donating' => 'Minimum Donation Amount',
            'max_amount_donating' => 'Maximum Donation Amount',
            'sale_end' => 'Sale End Date',
            'booking_details.location' => 'Booking Location',
            'booking_details.type' => 'Booking Type',
            'booking_details.availabilities' => 'Booking Availabilities',
            'booking_details.overrides' => 'Booking Overrides',
        ];
    }
}