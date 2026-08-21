<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'role_id']);
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->primary('user_id');
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('recipient_name'); $table->string('phone', 30);
            $table->string('line_1'); $table->string('line_2')->nullable();
            $table->string('ward')->nullable(); $table->string('district')->nullable(); $table->string('province');
            $table->char('country_code', 2)->default('VN'); $table->string('postal_code', 20)->nullable();
            $table->boolean('is_default_shipping')->default(false); $table->boolean('is_default_billing')->default(false);
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable();
            $table->string('image_path')->nullable(); $table->boolean('is_active')->default(true); $table->integer('sort_order')->default(0);
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('brands', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable();
            $table->string('logo_path')->nullable(); $table->string('website')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('collections', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->text('description')->nullable();
            $table->string('banner_path')->nullable(); $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name'); $table->string('slug')->unique(); $table->enum('product_type', ['watch', 'jewelry']);
            $table->string('short_description', 500)->nullable(); $table->text('description')->nullable();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->unsignedBigInteger('base_price_amount')->default(0); $table->char('currency', 3)->default('VND');
            $table->boolean('is_featured')->default(false); $table->string('seo_title')->nullable(); $table->string('seo_description', 320)->nullable();
            $table->string('source_url', 1000)->nullable(); $table->timestamps(); $table->softDeletes();
            $table->index(['category_id', 'brand_id', 'status']);
        });
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique(); $table->string('barcode')->nullable()->unique(); $table->string('name')->nullable();
            $table->unsignedBigInteger('price_amount'); $table->unsignedBigInteger('compare_at_price_amount')->nullable();
            $table->unsignedBigInteger('cost_amount')->nullable(); $table->decimal('weight_grams', 10, 2)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active'); $table->timestamps();
        });
        Schema::create('product_collections', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'collection_id']);
        });
        Schema::create('attributes', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('code')->unique(); $table->enum('display_type', ['select', 'color', 'text', 'number'])->default('select');
            $table->boolean('is_filterable')->default(true); $table->timestamps();
        });
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id(); $table->foreignId('attribute_id')->constrained()->cascadeOnDelete(); $table->string('value'); $table->string('label');
            $table->string('swatch_value', 50)->nullable(); $table->integer('sort_order')->default(0); $table->unique(['attribute_id', 'value']);
        });
        Schema::create('variant_attribute_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete(); $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_variant_id', 'attribute_value_id']);
        });
        Schema::create('product_images', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('storage_disk')->default('public'); $table->string('path', 1000); $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0); $table->boolean('is_primary')->default(false); $table->boolean('is_licensed')->default(false);
            $table->string('source_url', 1000)->nullable(); $table->timestamps();
        });
        Schema::create('watch_details', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->primary('product_id');
            $table->string('movement')->nullable(); $table->string('caliber')->nullable(); $table->string('case_material')->nullable();
            $table->decimal('case_diameter_mm', 6, 2)->nullable(); $table->decimal('case_thickness_mm', 6, 2)->nullable();
            $table->string('dial_color')->nullable(); $table->unsignedSmallInteger('water_resistance_m')->nullable(); $table->string('crystal')->nullable();
            $table->string('strap_material')->nullable(); $table->string('strap_color')->nullable(); $table->string('clasp_type')->nullable();
            $table->unsignedSmallInteger('power_reserve_hours')->nullable(); $table->json('functions')->nullable(); $table->unsignedSmallInteger('warranty_months')->nullable();
        });
        Schema::create('jewelry_details', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->primary('product_id');
            $table->enum('jewelry_type', ['ring', 'earrings', 'necklace', 'bracelet', 'pendant', 'other']); $table->string('gender')->default('unisex');
            $table->string('style')->nullable(); $table->string('ring_size_system', 30)->nullable(); $table->decimal('chain_length_mm', 8, 2)->nullable();
            $table->decimal('bracelet_length_mm', 8, 2)->nullable(); $table->string('dimensions')->nullable(); $table->decimal('total_weight_grams', 10, 2)->nullable();
            $table->string('plating')->nullable(); $table->text('care_instructions')->nullable();
        });
        Schema::create('materials', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->enum('material_type', ['metal', 'gemstone', 'leather', 'ceramic', 'other']); $table->unique(['name', 'material_type']);
        });
        Schema::create('product_materials', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->decimal('percentage', 5, 2)->nullable(); $table->string('notes')->nullable(); $table->primary(['product_id', 'material_id']);
        });
        Schema::create('gemstones', function (Blueprint $table) {
            $table->id(); $table->string('name')->unique(); $table->decimal('hardness_mohs', 3, 1)->nullable();
        });
        Schema::create('product_gemstones', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('gemstone_id')->constrained()->restrictOnDelete(); $table->unsignedSmallInteger('quantity')->default(1); $table->decimal('total_carat', 8, 3)->nullable();
            $table->string('cut_grade')->nullable(); $table->string('color_grade')->nullable(); $table->string('clarity_grade')->nullable(); $table->string('setting_type')->nullable();
        });
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id(); $table->string('code')->unique(); $table->string('name'); $table->string('province')->nullable(); $table->char('country_code', 2)->default('VN'); $table->boolean('is_active')->default(true);
        });
        Schema::create('inventory', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete(); $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_on_hand')->default(0); $table->unsignedInteger('quantity_reserved')->default(0); $table->unsignedInteger('reorder_level')->default(0); $table->timestamps();
            $table->unique(['warehouse_id', 'product_variant_id']);
        });
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('warehouse_id')->constrained()->restrictOnDelete(); $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->enum('transaction_type', ['receive', 'sale', 'return', 'adjustment', 'reservation', 'release']); $table->integer('quantity_delta');
            $table->string('reference_type')->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->string('notes', 500)->nullable(); $table->timestamps();
        });
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete(); $table->string('session_token')->nullable()->unique();
            $table->char('currency', 3)->default('VND'); $table->timestamp('expires_at')->nullable(); $table->timestamps();
        });
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('cart_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity'); $table->unsignedBigInteger('unit_price_amount'); $table->timestamps(); $table->unique(['cart_id', 'product_variant_id']);
        });
        Schema::create('wishlists', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('name')->nullable(); $table->timestamps(); });
        Schema::create('wishlist_items', function (Blueprint $table) { $table->id(); $table->foreignId('wishlist_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->timestamps(); $table->unique(['wishlist_id', 'product_id']); });
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('order_number')->unique(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending_confirmation', 'confirmed', 'preparing', 'shipping', 'completed', 'cancelled', 'returned'])->default('pending_confirmation');
            $table->enum('payment_status', ['unpaid', 'pending', 'paid', 'partially_refunded', 'refunded', 'failed'])->default('unpaid');
            $table->enum('fulfillment_status', ['unfulfilled', 'partial', 'fulfilled', 'returned'])->default('unfulfilled');
            $table->char('currency', 3)->default('VND'); $table->unsignedBigInteger('subtotal_amount'); $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('shipping_amount')->default(0); $table->unsignedBigInteger('tax_amount')->default(0); $table->unsignedBigInteger('total_amount');
            $table->string('customer_name'); $table->string('customer_email'); $table->string('customer_phone', 30); $table->text('note')->nullable(); $table->timestamp('placed_at')->nullable(); $table->timestamps();
        });
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id(); $table->foreignId('product_variant_id')->constrained()->restrictOnDelete(); $table->foreignId('cart_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('quantity'); $table->enum('status', ['active', 'released', 'converted', 'expired'])->default('active'); $table->timestamp('expires_at'); $table->timestamps();
        });
        Schema::create('order_addresses', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->enum('address_type', ['shipping', 'billing']);
            $table->string('recipient_name'); $table->string('phone', 30); $table->string('line_1'); $table->string('line_2')->nullable(); $table->string('ward')->nullable(); $table->string('district')->nullable(); $table->string('province'); $table->char('country_code', 2); $table->string('postal_code', 20)->nullable(); $table->unique(['order_id', 'address_type']);
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku'); $table->string('product_name'); $table->string('variant_name')->nullable(); $table->string('image_path', 1000)->nullable();
            $table->unsignedBigInteger('unit_price_amount'); $table->unsignedInteger('quantity'); $table->unsignedBigInteger('discount_amount')->default(0); $table->unsignedBigInteger('tax_amount')->default(0); $table->unsignedBigInteger('total_amount'); $table->json('product_snapshot')->nullable(); $table->timestamps();
        });
        Schema::create('order_status_history', function (Blueprint $table) { $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->string('status', 40); $table->string('comment', 500)->nullable(); $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamps(); });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('order_id')->constrained()->restrictOnDelete(); $table->string('provider'); $table->string('transaction_id')->nullable(); $table->string('payment_method');
            $table->unsignedBigInteger('amount'); $table->char('currency', 3)->default('VND'); $table->char('payment_currency', 3)->default('VND'); $table->decimal('provider_amount', 14, 2)->nullable(); $table->decimal('exchange_rate', 14, 6)->nullable();
            $table->enum('status', ['pending', 'authorized', 'paid', 'failed', 'cancelled', 'refunded'])->default('pending'); $table->timestamp('paid_at')->nullable(); $table->json('provider_payload')->nullable(); $table->timestamps(); $table->unique(['provider', 'transaction_id']);
        });
        Schema::create('shipments', function (Blueprint $table) { $table->id(); $table->foreignId('order_id')->constrained()->cascadeOnDelete(); $table->string('carrier')->nullable(); $table->string('tracking_number')->nullable(); $table->enum('status', ['pending', 'packed', 'shipped', 'delivered', 'failed', 'returned'])->default('pending'); $table->timestamp('shipped_at')->nullable(); $table->timestamp('delivered_at')->nullable(); $table->timestamps(); });
        Schema::create('reviews', function (Blueprint $table) { $table->id(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->foreignId('product_id')->constrained()->cascadeOnDelete(); $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete(); $table->unsignedTinyInteger('rating'); $table->string('title')->nullable(); $table->text('body')->nullable(); $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending'); $table->boolean('verified_purchase')->default(false); $table->timestamps(); });
        Schema::create('warranties', function (Blueprint $table) { $table->id(); $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete(); $table->string('warranty_number')->unique(); $table->date('starts_at'); $table->date('ends_at'); $table->text('terms')->nullable(); $table->enum('status', ['active', 'expired', 'void'])->default('active'); $table->timestamps(); });
        Schema::create('warranty_claims', function (Blueprint $table) { $table->id(); $table->foreignId('warranty_id')->constrained()->restrictOnDelete(); $table->string('claim_number')->unique(); $table->text('description'); $table->enum('status', ['submitted', 'approved', 'in_repair', 'resolved', 'rejected'])->default('submitted'); $table->text('resolution')->nullable(); $table->timestamp('resolved_at')->nullable(); $table->timestamps(); });
        Schema::create('return_requests', function (Blueprint $table) { $table->id(); $table->foreignId('order_id')->constrained()->restrictOnDelete(); $table->string('return_number')->unique(); $table->enum('status', ['requested', 'approved', 'rejected', 'received', 'refunded', 'closed'])->default('requested'); $table->string('reason', 500); $table->timestamp('requested_at')->useCurrent(); $table->timestamp('approved_at')->nullable(); $table->timestamps(); });
        Schema::create('store_settings', function (Blueprint $table) { $table->id(); $table->string('key')->unique(); $table->text('value'); $table->timestamps(); });
    }

    public function down(): void
    {
        foreach (['store_settings','return_requests','warranty_claims','warranties','reviews','shipments','payments','order_status_history','order_items','order_addresses','stock_reservations','orders','wishlist_items','wishlists','cart_items','carts','inventory_transactions','inventory','warehouses','product_gemstones','gemstones','product_materials','materials','jewelry_details','watch_details','product_images','variant_attribute_values','attribute_values','attributes','product_collections','product_variants','products','collections','brands','categories','addresses','customer_profiles','role_permissions','user_roles','permissions','roles'] as $table) Schema::dropIfExists($table);
    }
};
