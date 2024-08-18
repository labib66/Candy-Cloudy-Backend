<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {

            $table->integer('age')->nullable()->after('last_name');
            $table->string('Add_company', 100)->nullable()->after('age');
            $table->string('job_occubation', 100)->nullable()->after('Add_company');
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            
            $table->dropColumn(['age', 'Add_company', 'job_occubation']);
        });
    }
};
