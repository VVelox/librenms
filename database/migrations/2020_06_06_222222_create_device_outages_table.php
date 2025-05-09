<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('device_outages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('device_id')->index();
            $table->bigInteger('going_down');
            $table->bigInteger('up_again')->nullable();
            $table->bigInteger('uptime')->nullable();
            $table->unique(['device_id', 'going_down']);
        });
        Schema::create('availability', function (Blueprint $table) {
            $table->increments('availability_id');
            $table->unsignedInteger('device_id')->index();
            $table->bigInteger('duration');
            $table->double('availability_perc')->default(0.000000);
            $table->unique(['device_id', 'duration']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::drop('device_outages');
        Schema::drop('availability');
    }
};
