<?php
namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator,Redirect,Response;
use Illuminate\Support\Facades\Crypt;
use Mail;
use App\Helpers\Myhelper;
use App\Models\Setting;
use Storage;
use DB;
class SettingController extends Controller
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

    public function view()
    {
        try {
           $cond[]=['id',1];
           $setting=Setting::where($cond)->first();
           $total_Staff= \App\Models\User::where('is_del',0)->where('user_type','staff')->count();
           $setting->totalStaff=$total_Staff;
           return response()->json([
            'message' => "",
            'status' => true,
            'data' => $setting,
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

    public function edit(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
            'default_mode' => 'required',
            'week_start' => 'required|integer|between:1,7',
            'time_interval' => 'required|integer',
            'timepicker_interval' => 'required|integer',
            'start_hour' => 'required|integer|between:0,24',
            'end_hour' => 'required|integer|gt:start_hour',
            'no_emp' => 'required',
            'default_color' => 'required',
            'border_color'=>'required',
            'completion_color' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }

            $setting = Setting::find(1);

            $setting->default_mode=$request->default_mode;
            $setting->week_start=$request->week_start;
            $setting->time_interval=$request->time_interval;
            $setting->timepicker_interval=$request->timepicker_interval;
            $setting->start_hour=$request->start_hour;
            $setting->calender_stats=$request->calender_stats;
            $setting->no_emp=$request->no_emp;
            $setting->end_hour=$request->end_hour;
            $setting->default_color=$request->default_color;
            $setting->completion_color=$request->completion_color;
            $setting->border_color=$request->border_color;

            $setting->save();

            DB::table('working_hours')->update(['start_time'=>
            Carbon::createFromFormat('H', $request->start_hour)->format('H:i:s'),'end_time'=>Carbon::createFromFormat('H', $request->end_hour)->format('H:i:s')]);
           return response()->json([
                'status' => true,
                'message' =>"Setting has been updated successfully" ,

            ], 200);
            } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
         }



    }

     public function basicsetting()
    {
        try {
           $cond[]=['id',1];

           $setting=Setting::where($cond)->first();
           $slotDuration=Carbon::createFromFormat('i', $setting->time_interval)->format('H:i:s');
           $slotMinTime=Carbon::createFromFormat('H', $setting->start_hour)->format('H:i:s');
           $end_hour=Carbon::createFromFormat('H', $setting->end_hour)->format('H:i:s');
           $calender_setting=array('slotDuration'=>$slotDuration,'firstDay'=>($setting->week_start-1),
                                   'slotMinTime'=>$slotMinTime,'default_mode'=>$setting->default_mode,'time_interval'=>$setting->time_interval,'end_hour'=>$end_hour
                   );
           return response()->json([
            'message' => "",
            'status' => true,
            'data' => $calender_setting,
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
     public function calenderHeading(Request $request)
    {
        try {
           $validator = Validator::make($request->all(), [
            'default_mode' => 'required',
            ]);
            if ($validator->fails()) {
                $errors =Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
           $cond[]=['id',1];
           $setting=Setting::where($cond)->first();
           $week_start=Myhelper::weekstart($setting->week_start);
           if($request->default_mode=='dayGridMonth')
            {
            $start_date=Carbon::now()->startOfMonth()->format("Y-m-d");
            $end_date= Carbon::now()->endOfMonth()->format("Y-m-d");
             $heading=Carbon::parse($request->start_date)->format("F Y");
            }
            if($request->default_mode=='timeGridWeek')
            {
            $start_date=Carbon::now()->startOfWeek($week_start)->format("Y-m-d");
            $end_date= Carbon::parse($start_date)->endOfWeek()->format("Y-m-d");
            $heading=Carbon::parse($start_date)->format("M d").' - '.Carbon::parse($end_date)->format("M d");

            }
            if($request->default_mode=='timeGridDay')
            {
            $start_date=Carbon::now()->format("Y-m-d");
            $end_date= Carbon::now()->format("Y-m-d");
            $heading=Carbon::parse($start_date)->format("F d, Y");
            }

           return response()->json([
            'message' => "",
            'status' => true,
            'data' => $heading,
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

    public function viewcompanySetting()
    {
        try {
          $companySetting= \App\Models\Basicsetting::find(1);
          return response()->json([
            'message' => "",
            'status' => true,
            'data' => $companySetting,
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

    public function editcompanySetting(Request $request)
    {
        try {
          $companySetting= \App\Models\Basicsetting::find(1);
          $validator = Validator::make($request->all(), [
            'industry' => 'required',
            'company_name' => 'required',
            'phone_no' => 'required',
            'currency' => 'required',
            'country' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
          $companySetting->industry=$request->industry;
          $companySetting->phone_no=$request->phone_no;
          $companySetting->currency=$request->currency;
          $companySetting->country=$request->country;
          $companySetting->company_name=$request->company_name;

          $companySetting->save();
          return response()->json([
            'message' => "Company Details has been saved successfully",
            'status' => true,
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
