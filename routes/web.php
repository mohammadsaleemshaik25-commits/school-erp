<?php

use Illuminate\Support\Facades\DB;

Route::get('/test-db', function () {
    return DB::table('students')->count();
});
