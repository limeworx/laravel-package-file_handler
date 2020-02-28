<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FileUploadsAddExtensionColumn extends Migration
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
        DROP COLUMN upload_time, DROP version';
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
        ADD COLUMN `upload_time` datetime NOT NULL,
        ADD COLUMN `version` int(11) NOT NULL';
        DB::select($q);
    }
}
