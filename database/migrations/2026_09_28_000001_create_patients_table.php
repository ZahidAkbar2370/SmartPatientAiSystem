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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('father_husband_name')->nullable();
            $table->string('cnic', 15)->unique();
            $table->date('date_of_birth');
            $table->enum('gender', ['Male', 'Female']);
            $table->string('phone', 20)->nullable();
            $table->text('address');
            $table->enum('document_type', ['cnic', 'driving_license'])->nullable();
            $table->string('document_image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
