<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator, Redirect, Response;
use Mail;
use Myhelper;
use App\Models\Customerservice;
use Storage;
use DB;

class CustomerserviceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */


    /**
     * Where to redirect users after login.
     *
     * @var string
     */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function listing(Request $request)
    {

        try {
            $cond = [];
            if ($request->ignore_delete) {
            } else {
                $cond[] = ['is_del', 0];
            }
            $services = Customerservice::where($cond)->orderBy('id', 'desc')->get();
            $result_array = ['status' => true, 'data' => !empty($services) ? $services : array()];
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => $result_array,
                'error' => []
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function add(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                'minutes' => 'required|numeric',
                'name' => 'required',
                'color' => 'required',
                'border_color' => 'required',


            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            if (!empty($request->id) and is_numeric(($request->id))) {
                $service = Customerservice::find($request->id);
            } else {
                $service = new Customerservice();
            }
            $service->name = !empty($request->name) ? $request->name : '';
            $service->minutes = !empty($request->minutes) ? $request->minutes : '';
            $service->description = !empty($request->description) ? $request->description : '';
            $service->color = !empty($request->color) ? $request->color : '';
            $service->border_color = !empty($request->border_color) ? $request->border_color : '';

            $service->save();
            return response()->json([
                'status' => true,
                'message' => "Service has been saved successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function delete(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:customerservices,id',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $id = $request->id;
            $service = Customerservice::find($id);
            $service->is_del = 1;
            $service->save();
            return response()->json([
                'status' => true,
                'message' => "Service has been Deleted successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }


    public function weeklyService(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'staff_id' => 'required|exists:users,id',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $setting = \App\Models\Setting::where('id', 1)->first();
            $week_start = Myhelper::weekstart($setting->week_start);
            $start_date = Carbon::now()->startOfWeek($week_start)->format("Y-m-d");
            $end_date = Carbon::parse($start_date)->endOfWeek()->format("Y-m-d");
            $codition[] = ['is_del', 0];
            $codition[] = ['start_time', ">=", $start_date . ' 00:00:00'];
            $codition[] = ['end_time', "<=", $end_date . ' 23:59:59'];
            $select = ['id', 'customerservice_id', 'start_time', 'end_time'];
            $codition[] = ['staff_id', $request->staff_id];

            $appointments = \App\Models\Appointment::select($select)->where($codition)->with(['service' => function ($query) {
                    $query->select('id', 'name');
                }])
                ->get()->map(function ($item, $key) {
                    $end_time = Carbon::createFromFormat('Y-m-d H:i:s', $item->end_time);

                    $start_time = Carbon::createFromFormat('Y-m-d H:i:s', $item->start_time);
                    $item->meeting_length = $end_time->diffInMinutes($start_time);
                    return $item;
                });
            $response = ['appointments' => !empty($appointments) ? $appointments : array(), 'week_start' => $start_date, 'week_end' => $end_date];
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => $response,
                'error' => []
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
}
