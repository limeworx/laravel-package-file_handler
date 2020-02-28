<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustFilesTableToAcceptFileType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            //
            $q='ALTER TABLE file_uploads
            ADD file_type VARCHAR(100) NOT NULL AFTER file_extension';
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
        Schema::table('file_uploads', function (Blueprint $table) {
            //
            $q='ALTER TABLE file_uploads
            DROP file_type';
            DB::select($q);
        });
    }
}
