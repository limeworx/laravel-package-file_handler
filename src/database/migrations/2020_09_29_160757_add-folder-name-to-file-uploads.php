<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFolderNameToFileUploads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('file_uploads', function (Blueprint $table) 
        {
            $q='ALTER TABLE file_uploads
            ADD project_s3_folder_name VARCHAR(100) NOT NULL AFTER id';
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
        $q='ALTER TABLE file_uploads
        DROP project_s3_folder_name';
        DB::select($q);
    }
}
