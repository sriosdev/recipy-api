<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->bigIncrements('id');
            $table->unsignedSmallInteger('profile_id');
            $table->string('nick')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->text('description')->nullable();
            $table->binary('image')->nullable();
            $table->string('mime')->nullable();
            $table->tinyInteger('enabled');
            $table->tinyInteger('verified');
            $table->string('verification_email_token')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('profile_id')
                  ->references('id')->on('profiles')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        DB::statement("ALTER TABLE `users` CHANGE `image` `image` MEDIUMBLOB NULL DEFAULT NULL;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
