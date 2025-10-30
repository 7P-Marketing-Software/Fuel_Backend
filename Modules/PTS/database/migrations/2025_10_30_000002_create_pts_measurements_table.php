<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pts_measurements', function (Blueprint $table) {
            $table->id();
            $table->string('pts_id')->index();
            $table->string('probe_id');
            $table->decimal('fuel_level', 8, 2)->nullable();
            $table->decimal('temperature_1', 5, 2)->nullable();
            $table->decimal('temperature_2', 5, 2)->nullable();
            $table->json('additional_data')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();

            $table->index(['pts_id', 'probe_id']);
            $table->index(['measured_at']);
            $table->index(['pts_id', 'measured_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pts_measurements');
    }
};
