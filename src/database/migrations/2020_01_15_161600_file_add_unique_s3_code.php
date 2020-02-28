<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FileAddUniqueS3Code extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        $q='ALTER TABLE file_uploads
            ADD COLUMN `S3_unique_token` VARCHAR(15) NOT NULL AFTER file_name';
            DB::select($q);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        $q='ALTER TABLE file_uploads
            DROP COLUMN S3_unique_token';
            DB::select($q);
    }
}
