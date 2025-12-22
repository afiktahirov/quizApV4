<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('merchant_user', function (Blueprint $table) {
            $table->id(); // Auto increment id
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('merchant_id');

            // Primary key olaraq composite key (user_id, merchant_id)
//            $table->primary(['user_id', 'merchant_id']);

            // Foreign key əlaqələrini təyin et
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_user');
    }
};
