<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

if (!defined("API_NAME")) {
    define("API_NAME", config("app.name") . " - API");
}

Route::get('/', fn () => API_NAME);

Route::prefix('v1')->group(function () {

    Route::get('/', fn () => API_NAME)->name('api.root');

    Route::prefix('auth')->group(function () {

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('user', [AuthController::class, 'me'])->name('manager.me');
            Route::post('logout', [AuthController::class, 'logout']);
        });

        Route::post('login', [AuthController::class, 'login'])->name('manager.login');
        Route::post('signup', [AuthController::class, 'signup'])->name('manager.signup');
        Route::post('reset-link/{reset_code}', [AuthController::class, 'resetPassword'])->name('manager.reset_password');
        Route::post('request-reset-link', [AuthController::class, 'sendResetLink'])->name('manager.request_reset_link');
    });

    Route::get('reset-link/{reset_code}', [AuthController::class, 'viewResetPage'])->name('manager.reset_link');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('overview', [AttendanceController::class, 'index'])->name('attendance.overview');
        Route::get('attendances', [AttendanceController::class, 'getAttendance'])->name('attendance.get');
        Route::get('attendance/export/excel', [AttendanceController::class, 'exportAttendanceExcel'])->name('attendance.export.excel');
        Route::get('attendance/export/pdf', [AttendanceController::class, 'exportAttendancePdf'])->name('attendance.export.pdf');
        Route::post('employees/register-attendance', [AttendanceController::class, 'registerAttendance'])->name('attendance.register');
        Route::group(['prefix' => 'employees'], function () {
            Route::get('search', [EmployeeController::class, 'search'])->name('employee.search');
            Route::get('get/{employee_id}', [EmployeeController::class, 'single'])->name('employee.get');
            Route::post('create', [EmployeeController::class, 'store'])->name('employee.create');
            Route::patch('update/{employee_code}', [EmployeeController::class, 'update'])->name('employee.update');
            Route::delete('delete/{employee_code}', [EmployeeController::class, 'destroy'])->name('employee.delete');
        });
    });
});
