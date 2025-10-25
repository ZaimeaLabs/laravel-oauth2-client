<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

class CreateOauthProvidersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('oauth_providers', function (Blueprint $table) {
            $table->id()->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider'); // 'github', 'google'
            $table->foreignUuid('provider_user_id')->index();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->json('scopes')->nullable();
            $table->json('meta')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id', 'user_id'], 'oauth_provider_user_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('oauth_providers');
    }
}
