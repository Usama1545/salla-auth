<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->string('salla_id');
            $table->string('owner_id')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->text('avatar')->nullable();
            $table->string('store_location')->nullable();
            $table->string('plan')->nullable();
            $table->string('status')->nullable();
            $table->timestamp('salla_created_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
