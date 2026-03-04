<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('property_types', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('order')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->text('description')->nullable();
            $table->enum('type', ['string', 'integer', 'decimal', 'boolean']);
            $table->jsonb('options')->nullable();
            $table->decimal('min_value', 15, 8)->nullable();
            $table->decimal('max_value', 15, 8)->nullable();
            $table->boolean('is_filterable')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_type_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('order')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // prevent the same attribute from being linked to the same type twice
            $table->unique(['property_type_id', 'attribute_id']);
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('name');
            $table->string('type');
            $table->unsignedSmallInteger('depth')->default(0);
            $table->string('code')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'depth']);
            $table->index('type');
            $table->index('name');
        });

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_type_id')->constrained()->restrictOnDelete();
            $table->enum('listing_type', ['sale', 'rent'])->default('sale');
            $table->string('title');
            $table->decimal('surface', 12, 2);
            $table->enum('surface_unit', ['m2', 'ft2', 'are', 'ha', 'acre', 'km2'])->default('m2');
            $table->text('description')->nullable();
            $table->jsonb('attributes')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->foreignId('country_region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('root_region_id')->constrained('regions')->restrictOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->text('address')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->enum('status', ['available', 'sold', 'rented'])->default('available');
            $table->timestamp('available_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // common query patterns
            $table->index(['is_published', 'status']);
            $table->index(['listing_type', 'is_published']);
            $table->index(['property_type_id', 'is_published']);
            $table->index(['region_id', 'is_published']);

            // GIN index for fast JSONB containment queries on attributes
            // e.g.: WHERE attributes @> '{"rooms": 4}' or '{"wifi": true}'
            $table->rawIndex('attributes', 'idx_properties_attributes_gin', 'gin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('property_type_attributes');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('property_types');
    }
};
