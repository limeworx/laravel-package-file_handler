<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimestampToFiles extends Migration
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
            ADD upload_timestamp INT(11) NOT NULL AFTER file_type';
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
            DROP upload_timestamp';
            DB::select($q);
        });
    }
}
