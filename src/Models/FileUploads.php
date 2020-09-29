<?php

namespace Limeworx\FileHandler\Models;

use Illuminate\Database\Eloquent\Model;

class FileUploads extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'file_name',
        'project_s3_folder_name',
        's3_unique_token',
        'file_extension',
        'file_type',
        'upload_timestamp',
        's3_bucket_name',
        'uploader_id',
        'remember_token',
        'created_at',
        'updated_at',
        'is_current_file',
        'date_file_replaced',
        'FLAG_DELETED',
        'DELETED_DATE',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
    ];
}
