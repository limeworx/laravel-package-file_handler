<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use Limeworx\FileHandler\Models\CsvUploads;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(CsvUploads::class, function (Faker $faker) {
    return [
        'csv_name'                  => $faker->name,
        'generated_by'              => $faker->randomNumber,
        'generated_by_ip'           => $faker->ipv4,
        'generated_by_browser'      => $faker->userAgent ,
        'created_at'                => $faker->dateTime()
    ];
});
