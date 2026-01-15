<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return ['Laravel' => app()->version()];
// });

Route::get('/', function(){
    return view('register');
});
Route::get('/login', function () {
    return view('auth.login');
})->name('dashboard');


require __DIR__.'/auth.php';
