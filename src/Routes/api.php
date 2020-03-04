<?php

Route::group(['namespace'=>'Limeworx\FileHandler\Http\Controllers'], function(){

    Route::middleware('auth:api')->group(function () {
        Route::post('file', 'FilesController@UploadFile');
        Route::get('file', 'FilesController@GetFile');
        Route::post('file/stream', 'FilesController@StreamFileToServer');
        Route::delete('file', 'FilesController@DeleteFile'); 
    });

});
