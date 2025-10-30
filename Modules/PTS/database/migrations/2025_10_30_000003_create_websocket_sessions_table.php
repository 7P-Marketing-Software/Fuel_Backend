<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('websocket_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('pts_id')->nullable()->index();
            $table->string('remote_ip');
            $table->json('handshake_headers');
            $table->timestamp('connected_at');
            $table->timestamp('disconnected_at')->nullable();
            $table->integer('messages_received')->default(0);
            $table->integer('messages_sent')->default(0);
            $table->integer('connection_duration')->nullable();
            $table->timestamps();

            $table->index(['pts_id', 'connected_at']);
            $table->index(['connected_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('websocket_sessions');
    }
};
