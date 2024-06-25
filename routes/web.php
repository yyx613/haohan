<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

//Clear route cache:
Route::get('/route-cache', function () {
    $exitCode = Artisan::call('route:cache');
    return 'Routes cache cleared';
});

//Clear config cache:
Route::get('/config-cache', function () {
    $exitCode = Artisan::call('config:cache');
    return 'Config cache cleared';
});

//Clear config 
Route::get('/config-clear', function () {
    $exitCode = Artisan::call('config:clear');
    return 'Config cleared';
});

// Clear application cache:
Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
    return 'Application cache cleared';
});

// Clear view cache:
Route::get('/view-clear', function () {
    $exitCode = Artisan::call('view:clear');
    return 'View cache cleared';
});

// Migrate
Route::get('/db-migrate', function () {
    try {
        $exitCode = Artisan::call('migrate', ['--force' => true]);
    } catch (Exception $ex) {
        return $ex->getMessage();
    }

    return 'Migration Completed';
});