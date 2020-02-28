<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExtensionToFileUploads extends Migration
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
            ADD COLUMN `file_extension` VARCHAR(10) NULL DEFAULT NULL AFTER file_name';
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
            DROP COLUMN file_extension, DROP date_file_replaced';
            DB::select($q);
        });
    }
}
