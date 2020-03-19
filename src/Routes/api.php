<?php

Route::group(['namespace'=>'Limeworx\FileHandler\Http\Controllers'], function(){

    Route::middleware('auth:api')->group(function () {
        Route::post('api/file', 'FilesController@UploadFile');
        Route::get('api/file', 'FilesController@GetFile');
        Route::post('api/file/stream', 'FilesController@StreamFileToServer');
        Route::delete('api/file', 'FilesController@DeleteFile'); 

        Route::post('api/file/csv', 'FilesController@CreateAndDownloadCSV');
    });

});
