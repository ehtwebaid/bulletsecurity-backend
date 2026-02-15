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
Route::post('forgotPassword', '\App\Http\Controllers\Api\AuthController@forgotPassword');
Route::post('file-upload', '\App\Http\Controllers\Api\AuthController@fileUpload');


Route::middleware('auth:sanctum')->group(function () {
Route::post('changePassword', '\App\Http\Controllers\Api\AuthController@changePassword');

Route::post('logOut', '\App\Http\Controllers\Api\AuthController@logOut');

Route::post('listCustomers', '\App\Http\Controllers\Api\CustomerController@listCustomers');

Route::post('addCustomer', '\App\Http\Controllers\Api\CustomerController@addCustomer');

Route::post('deleteCustomer', '\App\Http\Controllers\Api\CustomerController@deleteCustomer');

Route::post('service/listing', '\App\Http\Controllers\Api\CustomerserviceController@listing');

Route::post('service/add', '\App\Http\Controllers\Api\CustomerserviceController@add');

Route::post('service/delete', '\App\Http\Controllers\Api\CustomerserviceController@delete');

Route::post('forceLogout', '\App\Http\Controllers\Api\StaffController@forceLogout')->middleware('checkrole:admin,"');

Route::post('addStaff', '\App\Http\Controllers\Api\StaffController@addStaff')->middleware( 'checkrole:admin,"');

Route::post('deleteStaff', '\App\Http\Controllers\Api\StaffController@deleteStaff')->middleware( 'checkrole:admin,""');

Route::post('listStaff', '\App\Http\Controllers\Api\StaffController@listStaff')->middleware( 'checkrole:admin,""');
Route::post('dailyStaff', '\App\Http\Controllers\Api\StaffController@dailyStaff');

Route::post('appointment/add', '\App\Http\Controllers\Api\AppointmentController@add');

Route::post('appointment/listing', '\App\Http\Controllers\Api\AppointmentController@listing');

Route::post('appointment/dashboard', '\App\Http\Controllers\Api\AppointmentController@dashboard');

Route::post('appointment/edit', '\App\Http\Controllers\Api\AppointmentController@editAppointment');

Route::post('appointment/delete', '\App\Http\Controllers\Api\AppointmentController@delete');

Route::post('sortOrder', '\App\Http\Controllers\Api\StaffController@updateOrder')->middleware( 'checkrole:admin,""');

Route::post('setting/view', '\App\Http\Controllers\Api\SettingController@view');

Route::post('setting/edit', '\App\Http\Controllers\Api\SettingController@edit')->middleware( 'checkrole:admin,""');

Route::post('setting/basicsetting', '\App\Http\Controllers\Api\SettingController@basicsetting');

Route::post('setting/calenderHeading', '\App\Http\Controllers\Api\SettingController@calenderHeading');

Route::post('editNote', '\App\Http\Controllers\Api\CustomerController@editNote')->middleware( 'checkrole:admin,""');

Route::post('dailyCustomers', '\App\Http\Controllers\Api\CustomerController@dailyCustomers');

Route::post('statistics', '\App\Http\Controllers\Api\AppointmentController@statistics')->middleware( 'checkrole:admin,""');

Route::post('viewAppointment', '\App\Http\Controllers\Api\AppointmentController@viewAppointment');

Route::post('yaerOptions', '\App\Http\Controllers\Api\AppointmentController@yaerOptions');

Route::post('weeklyService', '\App\Http\Controllers\Api\CustomerserviceController@weeklyService');

Route::post('setSchedule', '\App\Http\Controllers\Api\StaffController@setSchedule')->middleware( 'checkrole:admin,""');

Route::post('viewSchedule', '\App\Http\Controllers\Api\StaffController@viewSchedule')->middleware( 'checkrole:admin,""');

Route::post('viewcompanySetting', '\App\Http\Controllers\Api\SettingController@viewcompanySetting')->middleware( 'checkrole:admin,""');

Route::post('editcompanySetting', '\App\Http\Controllers\Api\SettingController@editcompanySetting')->middleware( 'checkrole:admin,""');

Route::post('monthlyReport', '\App\Http\Controllers\Api\StaffController@monthlyReport')->middleware( 'checkrole:admin,""');

Route::post('importCustomer', '\App\Http\Controllers\Api\CustomerController@importCustomer')->middleware('checkrole:admin,""');

Route::post('mergeCustomer', '\App\Http\Controllers\Api\CustomerController@mergeCustomer')->middleware('checkrole:admin,""');

Route::post('all-customer', '\App\Http\Controllers\Api\CustomerController@allCustomers');

Route::post('business-hour', '\App\Http\Controllers\Api\StaffController@findbusinessHours');

Route::post('staffAvailability', '\App\Http\Controllers\Api\StaffController@staffAvailability');

Route::post('submitMerge', '\App\Http\Controllers\Api\CustomerController@submitMerge')->middleware('checkrole:admin,""');

Route::post('reSchedule', '\App\Http\Controllers\Api\AppointmentController@reSchedule');

Route::post('currentAppointment', '\App\Http\Controllers\Api\AppointmentController@currentAppointment');

Route::post('my-favourite', '\App\Http\Controllers\Api\AppointmentController@myFavouriteList');

Route::post('add-favourite', '\App\Http\Controllers\Api\AppointmentController@myFavouriteAdd');

Route::post('logs', '\App\Http\Controllers\Api\AppointmentLogController@listLog');

Route::post('viewLog', '\App\Http\Controllers\Api\AppointmentLogController@viewLog');
});




