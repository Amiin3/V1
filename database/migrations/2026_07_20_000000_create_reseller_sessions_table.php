<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('reseller_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number')->unique();
            $table->text('session_data')->nullable();
            $table->boolean('is_ready')->default(false);
            $table->timestamps();
        });
    }
    public function down() {
        Schema::dropIfExists('reseller_sessions');
    }
};
