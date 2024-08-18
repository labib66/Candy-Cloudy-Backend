<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class DeleteOldSeoSettings extends Migration
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
        if (!Schema::hasColumn('settings', 'name')) {
            $table->string('name');
        }
    });

    DB::table('settings')->where('name', 'LIKE', 'seo.%')->delete();
}


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
