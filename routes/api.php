<?php

use Illuminate\Http\Request;
use Tymon\JWTAuth\Http\Middleware\Authenticate;
use App\Http\Middleware\EnsureApiAuthenticated;
use App\Http\Controllers\Api\AuthController;

date_default_timezone_set('Pacific/Pitcairn');
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::post('/login', [AuthController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
Route::post('service/listing', '\App\Http\Controllers\Api\CustomerserviceController@listing')->middleware('auth:api');
Route::post('service/add', '\App\Http\Controllers\Api\CustomerserviceController@add')->middleware('auth:api');
Route::post('service/delete', '\App\Http\Controllers\Api\CustomerserviceController@delete')->middleware('auth:api');
Route::post('forceLogout', '\App\Http\Controllers\Api\StaffController@forceLogout')->middleware('auth:api', 'checkrole:admin,"');

Route::post('addStaff', '\App\Http\Controllers\Api\StaffController@addStaff')->middleware('auth:api', 'checkrole:admin,"');
Route::post('deleteStaff', '\App\Http\Controllers\Api\StaffController@deleteStaff')->middleware('auth:api', 'checkrole:admin,""');
Route::post('listStaff', '\App\Http\Controllers\Api\StaffController@listStaff')->middleware('auth:api', 'checkrole:admin,""');
Route::post('dailyStaff', '\App\Http\Controllers\Api\StaffController@dailyStaff')->middleware('auth:api');
Route::post('appointment/add', '\App\Http\Controllers\Api\AppointmentController@add')->middleware('auth:api');
Route::post('appointment/listing', '\App\Http\Controllers\Api\AppointmentController@listing');
Route::post('appointment/dashboard', '\App\Http\Controllers\Api\AppointmentController@dashboard')->middleware('auth:api');
Route::post('appointment/edit', '\App\Http\Controllers\Api\AppointmentController@editAppointment')->middleware('auth:api');
Route::post('appointment/delete', '\App\Http\Controllers\Api\AppointmentController@delete')->middleware('auth:api');
Route::post('sortOrder', '\App\Http\Controllers\Api\StaffController@updateOrder')->middleware('auth:api', 'checkrole:admin,""');

Route::post('setting/view', '\App\Http\Controllers\Api\SettingController@view')->middleware('auth:api');
Route::post('setting/edit', '\App\Http\Controllers\Api\SettingController@edit')->middleware('auth:api', 'checkrole:admin,""');
Route::post('setting/basicsetting', '\App\Http\Controllers\Api\SettingController@basicsetting');
Route::post('setting/calenderHeading', '\App\Http\Controllers\Api\SettingController@calenderHeading')->middleware('auth:api');

Route::post('statistics', '\App\Http\Controllers\Api\AppointmentController@statistics')->middleware('auth:api', 'checkrole:admin,""');
Route::post('viewAppointment', '\App\Http\Controllers\Api\AppointmentController@viewAppointment')->middleware('auth:api');
Route::post('yaerOptions', '\App\Http\Controllers\Api\AppointmentController@yaerOptions')->middleware('auth:api');
Route::post('weeklyService', '\App\Http\Controllers\Api\CustomerserviceController@weeklyService')->middleware('auth:api');
Route::post('setSchedule', '\App\Http\Controllers\Api\StaffController@setSchedule')->middleware('auth:api', 'checkrole:admin,""');
Route::post('viewSchedule', '\App\Http\Controllers\Api\StaffController@viewSchedule')->middleware('auth:api', 'checkrole:admin,""');
Route::post('viewcompanySetting', '\App\Http\Controllers\Api\SettingController@viewcompanySetting')->middleware('auth:api', 'checkrole:admin,""');
Route::post('editcompanySetting', '\App\Http\Controllers\Api\SettingController@editcompanySetting')->middleware('auth:api', 'checkrole:admin,""');
Route::post('monthlyReport', '\App\Http\Controllers\Api\StaffController@monthlyReport')->middleware('auth:api', 'checkrole:admin,""');
Route::post('business-hour', '\App\Http\Controllers\Api\StaffController@findbusinessHours')->middleware('auth:api');
Route::post('staffAvailability', '\App\Http\Controllers\Api\StaffController@staffAvailability')->middleware('auth:api');

Route::post('reSchedule', '\App\Http\Controllers\Api\AppointmentController@reSchedule')->middleware('auth:api');
Route::post('currentAppointment', '\App\Http\Controllers\Api\AppointmentController@currentAppointment')->middleware('auth:api');
Route::post('my-favourite', '\App\Http\Controllers\Api\AppointmentController@myFavouriteList')->middleware('auth:api');
Route::post('add-favourite', '\App\Http\Controllers\Api\AppointmentController@myFavouriteAdd')->middleware('auth:api');
Route::post('logs', '\App\Http\Controllers\Api\AppointmentLogController@listLog')->middleware('auth:api');
Route::post('viewLog', '\App\Http\Controllers\Api\AppointmentLogController@viewLog')->middleware('auth:api');
});




