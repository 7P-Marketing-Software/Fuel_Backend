<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pts_logs', function (Blueprint $table) {
            $table->id();
            $table->string('pts_id')->nullable()->index();
            $table->enum('method', ['POST', 'WEBSOCKET', 'GET']);
            $table->string('uri');
            $table->json('headers');
            $table->json('body')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('config_identifier')->nullable();
            $table->string('packet_type')->nullable();
            $table->integer('packet_id')->nullable();
            $table->boolean('forwarded')->default(false);
            $table->text('forward_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'pts_id']);
            $table->index(['packet_type', 'created_at']);
            $table->index(['method', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pts_logs');
    }
};
