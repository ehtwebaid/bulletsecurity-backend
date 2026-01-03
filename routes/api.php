<?php

use Illuminate\Http\Request;
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

// Route::post('forgotPassword', 'App\Http\Controllers\Api\AuthController@forgotPassword');
// Route::post('file-upload', 'App\Http\Controllers\ApiApi\AuthController@fileUpload');
// Route::middleware('auth:api')->group(function () {
// Route::post('listCustomers', [\App\Http\Controllers\Api\CustomerController::class, 'listCustomers']);
// });


// Route::post('changePassword', 'Api\AuthController@changePassword')->middleware('auth:api');
// Route::post('logOut', 'Api\AuthController@logOut')->middleware('auth:api');

// Route::post('listCustomers', 'Api\CustomerController@listCustomers')->middleware('auth:api');
// Route::post('addCustomer', 'Api\CustomerController@addCustomer')->middleware('auth:api');
// Route::post('deleteCustomer', 'Api\CustomerController@deleteCustomer')->middleware('auth:api');
// Route::post('service/listing', 'Api\CustomerserviceController@listing')->middleware('auth:api');
// Route::post('service/add', 'Api\CustomerserviceController@add')->middleware('auth:api');
// Route::post('service/delete', 'Api\CustomerserviceController@delete')->middleware('auth:api');
// Route::post('forceLogout', 'Api\StaffController@forceLogout')->middleware('auth:api', 'checkrole:admin,"');

// Route::post('addStaff', 'Api\StaffController@addStaff')->middleware('auth:api', 'checkrole:admin,"');
// Route::post('deleteStaff', 'Api\StaffController@deleteStaff')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('listStaff', 'Api\StaffController@listStaff')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('dailyStaff', 'Api\StaffController@dailyStaff')->middleware('auth:api');
// Route::post('appointment/add', 'Api\AppointmentController@add')->middleware('auth:api');
// Route::post('appointment/listing', 'Api\AppointmentController@listing')->middleware('auth:api');
// Route::post('appointment/dashboard', 'Api\AppointmentController@dashboard')->middleware('auth:api');
// Route::post('appointment/edit', 'Api\AppointmentController@editAppointment')->middleware('auth:api');
// Route::post('appointment/delete', 'Api\AppointmentController@delete')->middleware('auth:api');
// Route::post('sortOrder', 'Api\StaffController@updateOrder')->middleware('auth:api', 'checkrole:admin,""');

// Route::post('setting/view', 'Api\SettingController@view')->middleware('auth:api');
// Route::post('setting/edit', 'Api\SettingController@edit')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('setting/basicsetting', 'Api\SettingController@basicsetting')->middleware('auth:api');
// Route::post('setting/calenderHeading', 'Api\SettingController@calenderHeading')->middleware('auth:api');
// Route::post('editNote', 'Api\CustomerController@editNote')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('dailyCustomers', 'Api\CustomerController@dailyCustomers')->middleware('auth:api');

// Route::post('statistics', 'Api\AppointmentController@statistics')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('viewAppointment', 'Api\AppointmentController@viewAppointment')->middleware('auth:api');
// Route::post('yaerOptions', 'Api\AppointmentController@yaerOptions')->middleware('auth:api');
// Route::post('weeklyService', 'Api\CustomerserviceController@weeklyService')->middleware('auth:api');
// Route::post('setSchedule', 'Api\StaffController@setSchedule')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('viewSchedule', 'Api\StaffController@viewSchedule')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('viewcompanySetting', 'Api\SettingController@viewcompanySetting')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('editcompanySetting', 'Api\SettingController@editcompanySetting')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('monthlyReport', 'Api\StaffController@monthlyReport')->middleware('auth:api', 'checkrole:admin,""');
// Route::post('importCustomer', 'Api\CustomerController@importCustomer')->middleware('auth:api','checkrole:admin,""');
// Route::post('mergeCustomer', 'Api\CustomerController@mergeCustomer')->middleware('auth:api','checkrole:admin,""');
// Route::post('all-customer', 'Api\CustomerController@allCustomers')->middleware('auth:api');
// Route::post('business-hour', 'Api\StaffController@findbusinessHours')->middleware('auth:api');
// Route::post('staffAvailability', 'Api\StaffController@staffAvailability')->middleware('auth:api');

// Route::post('submitMerge', 'Api\CustomerController@submitMerge')->middleware('auth:api','checkrole:admin,""');
// Route::post('reSchedule', 'Api\AppointmentController@reSchedule')->middleware('auth:api');
// Route::post('currentAppointment', 'Api\AppointmentController@currentAppointment')->middleware('auth:api');
// Route::post('my-favourite', 'Api\AppointmentController@myFavouriteList')->middleware('auth:api');
// Route::post('add-favourite', 'Api\AppointmentController@myFavouriteAdd')->middleware('auth:api');
// Route::post('logs', 'Api\AppointmentLogController@listLog')->middleware('auth:api');
// Route::post('viewLog', 'Api\AppointmentLogController@viewLog')->middleware('auth:api');
