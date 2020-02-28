<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedFlagToFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('files', function (Blueprint $table) {
            //
            $q='ALTER TABLE file_uploads
            ADD COLUMN `FLAG_DELETED` VARCHAR(10) NULL DEFAULT NULL,
            ADD COLUMN `DELETED_DATE` DATETIME NULL DEFAULT NULL';
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
        Schema::table('files', function (Blueprint $table) {
            //
            $q='ALTER TABLE file_uploads
            DROP COLUMN FLAG_DELETED, DROP COLUMN DELETED_DATE';
            DB::select($q);
        });
    }
}
