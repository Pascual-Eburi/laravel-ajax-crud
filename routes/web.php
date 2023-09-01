<?php

use App\Http\Controllers\EmployeeController;
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
    return view('index');
});

Route::get('employes', [EmployeeController::class, 'index'])->name('list'); // get list of employees
Route::post('/store', [EmployeeController::class, 'store'])->name('store'); // add new employee to database
Route::get('/edit', [EmployeeController::class, 'edit'])->name('edit'); // get single employee
Route::post('/update', [EmployeeController::class, 'update'])->name('update'); // update employee data
Route::delete('/delete', [EmployeeController::class, 'delete'])->name('delete'); // delete employee

