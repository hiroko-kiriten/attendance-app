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
        Schema::create('attendance_correction_request_breaks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendance_correction_request_id');
            
            $table->foreign('attendance_correction_request_id','acrb_request_fk')
            ->references('id')
            ->on('attendance_correction_requests')
            ->onDelete('cascade');
            
            $table->time('break_in');
            $table->time('break_out');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_request_breaks');
    }
};
