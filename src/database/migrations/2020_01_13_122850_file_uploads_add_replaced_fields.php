<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FileUploadsAddReplacedFields extends Migration
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
        ADD COLUMN `is_current_file` int(11) NULL DEFAULT NULL,
        ADD COLUMN `date_file_replaced` DATETIME NULL DEFAULT NULL';
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
        DROP COLUMN is_current_file, DROP date_file_replaced';
        DB::select($q);
    }
}
