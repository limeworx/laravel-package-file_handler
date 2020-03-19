<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCsvFileGenerationColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('csv_uploads', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('csv_name');
            $table->string('generated_by');
            $table->string('generated_by_ip')->nullable();
            $table->string('generated_by_browser');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::dropIfExists('csv_uploads');
    }
}
