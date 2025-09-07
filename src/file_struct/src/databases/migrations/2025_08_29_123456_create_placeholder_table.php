<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placeholder', function (Blueprint $table) {
            // Define your table columns here
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placeholder');
    }
};