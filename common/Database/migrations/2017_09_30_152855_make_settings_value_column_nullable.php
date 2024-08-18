<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeSettingsValueColumnNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('settings', function (Blueprint $table) {
        // Add the column if it doesn't exist
        if (!Schema::hasColumn('settings', 'value')) {
            $table->string('value')->nullable();
        }
    });
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->text('value')->change();
        });
    }
}
