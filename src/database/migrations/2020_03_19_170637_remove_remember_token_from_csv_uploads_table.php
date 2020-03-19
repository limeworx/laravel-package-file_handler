<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveRememberTokenFromCsvUploadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('csv_uploads', function (Blueprint $table) {
            //
            $q='ALTER TABLE csv_uploads
            DROP remember_token';
            DB::select($q);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('csv_uploads', function (Blueprint $table) {
            //
            $q='ALTER TABLE file_uploads
            ADD remember_token VARCHAR(255) NOT NULL AFTER generated_by_browser';
            DB::select($q);
        });
    }
}
