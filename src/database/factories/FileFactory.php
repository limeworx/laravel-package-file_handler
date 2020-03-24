<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Limeworx\FileHandler\Models\FileUploads;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(FileUploads::class, function (Faker $faker) {
    return [
        'file_name'             => $faker->name,
        'S3_unique_token'       => $faker->password,
        'file_extension'        => $faker->fileExtension,
        'file_type'             => $faker->randomElement($array = array ("Drafts", "Artwork", "General", "Profile Pictures", "Hero Images", "Assets")),
        'upload_timestamp'      => $faker->unixtime($max='now'),
        's3_bucket_name'        => $faker->shuffle("AN-EXTREMELY-COOL-BUCKET-NAME"),
        'uploader_id'           => $faker->randomNumber(),
        'is_current_file'       => $faker->randomElement($array=array(0,1)),
    ];
});
