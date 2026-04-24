<?php

use Illuminate\Support\Facades\Route;

Route::view('/{any?}', 'application')->where('any', '.*');
