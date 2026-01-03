<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator, Redirect, Response;
use Illuminate\Support\Facades\Crypt;
use Mail;
use Myhelper;
use App\Models\Staff;
use App\Models\User;
use App\Models\Workinghour;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Models\Log;
use Storage;
use DB;
use DateTime;
use Pusher;
class StaffController extends Controller
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
        $this->middleware('auth:api');
    }

    public function listStaff(Request $request)
    {
        try {
            $staff_lists = [];
            $or_cond = [];
            $or_cond[] = ['user_type', 'staff'];
            $or_cond[] = ['user_type', 'admin'];
            $cond[] = ['id', '!=', 1];

            if ($request->ignore_delete) {
            } else {
                $cond[] = ['is_del', 0];
            }
            $staffs = User::where($cond)->orWhere($or_cond)->select('id', 'name', 'email', 'phone_no', 'current_status', 'user_type')->with(['staff'])->orderBy('sort_order', 'asc')->get();
            foreach ($staffs as $staff) {
                $access_level = [];

                $staff_lists[] = [
                    'id' => (int)$staff->id, 'name' => $staff->name, 'title' => $staff->name, 'email' => $staff->email,
                    'phone_no' => !is_null($staff->phone_no) ? $staff->phone_no : '',
                    'current_status' => $staff->current_status == 'A' ? 1 : 0, 'comments' => (!empty($staff->staff->comments) and $staff->staff->comments!='null')?$staff->staff->comments:'', 'access_level' => $access_level, 'user_type' => $staff->user_type,
                    'require_estimate'=>!empty($staff->staff->require_estimate)?(int)$staff->staff->require_estimate:0
                ];
            }
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => !empty($staff_lists) ? $staff_lists : array(),
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
    public function viewStaff($user_id = '')
    {
        $staff = User::where('id', $user_id)->select('id', 'name', 'email', 'phone_no', 'current_status', 'user_type')->with(['staff'])->first();

        $access_level = [];
        // $access_level_array = json_decode($staff->staff->access_level, true);
        // foreach ($access_level_array as $key => $level) {
        //     if ($level == 1) {
        //         $access_level[] = $key;
        //     }
        // }
        $staff_detail = [
            'id' => (int)$staff->id, 'name' => $staff->name, 'title' => $staff->name, 'email' => $staff->email,
            'phone_no' => !is_null($staff->phone_no) ? $staff->phone_no : '',
            'current_status' => $staff->current_status == 'A' ? 1 : 0, 'comments' => @$staff->staff->comments ?? null, 'access_level' => $access_level, 'user_type' => $staff->user_type,
            'require_estimate'=>(int)$staff->staff->require_estimate
        ];
        return $staff_detail;
    }
    public function dailyStaff(Request $request)
    {
        try {
            $setting = \App\Models\Setting::where('id', 1)->first();
            //$no_emp=$setting->no_emp;
            $no_emp = 1000;
            $staff_lists = [];
            $or_cond = [];
            $or_cond[] = ['user_type', 'staff'];
            $or_cond[] = ['user_type', 'admin'];
            $cond[] = ['is_del', 0];
            $cond[] = ['id', '!=', 1];
            if (!empty($request->staff_id) and is_numeric($request->staff_id)) {
                $cond[] = ['id', $request->staff_id];
            }
            $staffs = User::where($cond)->orWhere($or_cond)->select('id', 'name', 'email', 'phone_no', 'current_status', 'sort_order')->with(['staff'])->orderBy('sort_order', 'asc')->offset(0)->limit($no_emp)->get();
            foreach ($staffs as $staff) {
                $access_level = [];
                // if(!empty($staff->staff->access_level))
                // {
                //     $access_level_array = json_decode($staff->staff->access_level, true);
                //     foreach ($access_level_array as $key => $level) {
                //         if ($level == 1) {
                //             $access_level[] = $key;
                //         }
                //     }
                // }

                $staff_lists[] = [
                    'id' => (int)$staff->id, 'name' => Myhelper::convert_from_latin1_to_utf8_recursively($staff->name), 'title' => Myhelper::convert_from_latin1_to_utf8_recursively($staff->name), 'email' => $staff->email, 'phone_no' => $staff->phone_no,
                    'current_status' => $staff->current_status == 'A' ? 1 : 0, 'comments' => $staff->comments, 'access_level' => $access_level,
                    'sort_order' => (int)$staff->sort_order,'require_estimate'=>!empty($staff->staff->require_estimate)?true:false
                ];
            }
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => !empty($staff_lists) ? $staff_lists : array(),
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
    public function forceLogout(Request $request)
    {

        try {
            require base_path() . '/vendor/pusher/autoload.php';
            $app_id = getenv('PUSHER_APP_ID');
            $app_key = getenv('PUSHER_API_KEY');
            $app_secret = getenv('PUSHER_SECRET_KEY');

            $options = array(
                'cluster' => getenv('PUSHER_CLUSTER'),
                'useTLS' => true
            );

            $pusher = new Pusher\Pusher($app_key, $app_secret, $app_id, $options);
            $validator = Validator::make($request->all(), [
                //'name' => 'required|regex:/^[a-zA-Z\s]+$/u|max:255',
                'user_id'     => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $log = new Log();
            $log->user_id=$request->user_id;
            $log->action_by=\Auth::guard('api')->id();
            $log->action='force_log_out';
            $log->ip=$request->ip();
            $log->save();
            $pusher_response = [
                'status' => false,
                'logOut' => true,
                'message' => "Log out successfully",
                'data' => ['user_id' => $request->user_id, 'type' => 'force_log_out'],
            ];

            $pusher->trigger('my-channel', 'my-event', json_encode($pusher_response));
            $response = [
                'status' => true,
                'message' => "Force Log out successfully",
                'data' => [],
            ];
            return response()->json($response, 200);

        }
        catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function addStaff(Request $request)
    {
        try {
            $setting = \App\Models\Setting::where('id', 1)->first();
            $unique_email = "";
            $unique_phone = "";
            $password = 'required';

            if (!empty($request->id)) {
                $unique_email = ",email," . $request->id;

                $password = 'nullable';
            }
            $validator = Validator::make($request->all(), [
                //'name' => 'required|regex:/^[a-zA-Z\s]+$/u|max:255',
                'name'     => 'required|max:255',
                'user_type'     => 'required|in:admin,staff',
                'email'    => 'required|email|unique:users' . $unique_email . ',id,is_del,0',
                'password' => $password,
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            if (!empty($request->id)) {
                $user = User::find($request->id);
                $staff = Staff::where('user_id', $request->id)->first();
            } else {
                $user = new User();
                $staff = new Staff();
            }
            // $access_levels = array('one_day_view' => 0, 'view_all' => 0, 'edit' => 0);
            // if (in_array('one_day_view', explode(",", $request->access_level))) {
            //     $access_levels['one_day_view'] = 1;
            // }
            // if (in_array('view_all', explode(",", $request->access_level))) {
            //     $access_levels['view_all'] = 1;
            // }
            // if (in_array('edit', explode(",", $request->access_level))) {
            //     $access_levels['edit'] = 1;
            // }
            $user->name = $request->name;
            $user->email = $request->email;
            $user->username = $request->email;
            $user->phone_no = $request->phone_no;
            $user->user_type = $request->user_type;
            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }
            $user->current_status = $request->current_status ? 'A' : 'P';

            $user->save();
            if (empty($request->id)) {
                $sort_order = User::where('user_type', 'staff')->count();
                $user->sort_order = $sort_order;
                $user->save();
            }
            $staff->user_id = $user->id;
            $staff->comments = $request->comments;
            $staff->require_estimate = !empty($request->require_estimate)?(bool)$request->require_estimate:0;

            ///$staff->access_level = json_encode($access_levels);
            $staff->save();
            if (empty($request->id)) {
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday','Saturday','Sunday'];
                foreach ($days as $day) {
                    $working_hour = new Workinghour();
                    $working_hour->day_name = $day;
                    $working_hour->user_id = $user->id;
                    $working_hour->start_time = $setting->start_hour . ':00:00';
                    $working_hour->end_time = $setting->end_hour . ':00:00';
                    $working_hour->is_weekday=($day=='Saturday' or $day=='Sunday')?0:1;
                    $working_hour->save();
                }
            }



            return response()->json([
                'status' => true,
                'message' => "Staff has been saved successfully",
                'data' => $this->viewStaff($user->id)

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function deleteStaff(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $ids = explode(",", $request->ids);
            foreach ($ids as $id) {
                $id = $id;
                $user = User::find($id);
                $user->is_del = 1;
                $user->save();
                $staff = Staff::where('user_id', $id)->first();
                $staff->is_del = 1;
                $staff->save();

            }

            return response()->json([
                'status' => true,
                'message' => "User has been Deleted successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function setSchedule(Request $request)
    {
        try {
            $body = json_decode($request->getContent(), true);
            $validator = Validator::make($body, [
                'workinghours' => 'required',
            ]);
            foreach ($body['workinghours'] as $hour) {
                $working_hour = Workinghour::find($hour['id']);
                $working_hour->start_time = $hour['start_time'];
                $working_hour->end_time = $hour['end_time'];
                $working_hour->is_weekday = (bool)$hour['is_weekday'];

                $working_hour->save();
            }
            return response()->json([
                'status' => true,
                'message' => "Working Hours has been set successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function viewSchedule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);
            $workinghours = Workinghour::where('user_id', $request->user_id)->get()->map(function ($item, $key) {
            $item->is_weekday=(bool)$item->is_weekday ;
            return $item;
            });

            return response()->json([
                'message' => "",
                'status' => true,
                'data' => !empty($workinghours) ? $workinghours : array(),
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

    public function monthlyReport(Request $request)
    {

        try {
            $setting = \App\Models\Setting::where('id', 1)->first();
            if (!empty($request->start_date) and !empty($request->end_date)) {
                $start_date = $request->start_date;
                $end_date = $request->end_date;
            } else {
                $start_date = Carbon::now()->startOfMonth()->format("Y-m-d");
                $end_date = Carbon::parse($start_date)->endOfMonth()->format("Y-m-d");
            }
            //$codition[] = ['appointments.is_del', 0];
            // $codition[] = ['repeat_appointments.start_time', '>=', $start_date . ' 00:00:00'];
            // $codition[] = ['repeat_appointments.end_time', '<=', $end_date . ' 23:59:59'];
            $codition=[];
            $select = [
                'id', 'start_time', 'end_time', 'staff_id', 'customer_id', 'current_status', 'code', 'customerservice_id',
                'notes', 'extra_materials', 'project_manager', 'time_in', 'time_out',"updated_at"
            ];
            if (!empty($request->staff_id) and $request->staff_id != 'null') {
                $codition[] = ['appointments.staff_id', $request->staff_id];
            }
            if (!empty($request->customerservice_id) and $request->customerservice_id != 'null') {
                $codition[] = ['appointments.customerservice_id', $request->customerservice_id];
            }
            if (empty($request->page_no)) {
                $request->page_no = 0;
            }
            if (empty($request->itemsPerPage)) {
                $request->itemsPerPage = 150;
            }
            $raw_appointments = Appointment::select('appointments.id', 'repeat_appointments.start_time', 'repeat_appointments.end_time','appointments.updated_at')
                ->rightJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')
                ->where($codition)
                ->orderBy('appointments.id', 'desc');
                $rowsTotal= $raw_appointments->count();
                $raw_appointments= $raw_appointments->offset($request->page_no)->limit($request->itemsPerPage)
                ->get();

            foreach ($raw_appointments as $raw_appointment) {
                $last_action = AppointmentLog::where('appointment_id',$raw_appointment->id)->with('user')->orderByDesc('id')->first();
                if(!empty($last_action)){
                $last_action_type= $last_action->action;
                $last_action_by= !empty($last_action->user->name)?$last_action->user->name:'';

                }
                else{
                    $last_action_type= "N/A";
                    $last_action_by= "N/A";
                }

                $new_appointment = Appointment::select($select)->where('id', $raw_appointment->id)->with(['customer' => function ($query) {
                    $query->select('id', 'name', 'mobile');
                }, 'service' => function ($query) {
                    $query->select('id', 'name');
                }, 'staff' => function ($query) {
                    $query->select('id', 'name');
                }])->first();
                $new_appointment->start_time = $raw_appointment->start_time;
                $new_appointment->end_time = $raw_appointment->end_time;
                $new_appointment->notes = strip_tags(Myhelper::convert_from_latin1_to_utf8_recursively($new_appointment->notes));
                $new_appointment->last_action = $last_action_type;
                $new_appointment->last_action_by = $last_action_by;
                $new_appointment->updated_at = $raw_appointment->updated_at;

                $appointments[] = $new_appointment;
            }
            return response()->json([
                'message' => "",
                'status' => true,
                'rowsTotal'=>$rowsTotal,
                'data' => !empty($appointments) ? Myhelper::convert_from_latin1_to_utf8_recursively($appointments) : array(),
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
    public function updateOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'staffs' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $staffs = explode(",", $request->staffs);
            $sort_order = 1;
            foreach ($staffs as $key => $staff) {
                $user = User::find($staff);
                $user->sort_order = $sort_order;
                $user->save();
                $sort_order++;
            }
            return response()->json([
                'status' => true,
                'message' => "Working Hours has been set successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }


    function findbusinessHours(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $businessHours = [];
            $day_names = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday'];
            $workinghours = Workinghour::where('user_id', $request->user_id)->get();


            foreach ($workinghours as $whour) {
                $daysOfWeek = array_search($whour->day_name, $day_names, true);
                $businessHours[] = ['daysOfWeek' => [$daysOfWeek], 'startTime' => $whour->start_time, 'endTime' => $whour->end_time];
            }
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => !empty($businessHours) ? $businessHours : array(),
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


    function staffAvailability(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $current_dt=date('Y-m-d');
            $nearest_appoints=[];
            $staffs=$request->ids;
            $staff_array=explode(",",$staffs);
            $available_days=[];
            foreach($staff_array as $staff)
            {
                $days = Workinghour::where('user_id', $staff)->where('is_weekday',1)->get();
                foreach($days as $new_day)
                {
                    $available_days[]=$new_day->day_name;
                }

            }
            $vals = array_count_values($available_days);
            $newArray = array_filter($vals,function($value, $key)  use($staff_array) {
                return $value==count($staff_array);
            },ARRAY_FILTER_USE_BOTH);
            $filter_days="";
            foreach(array_keys($newArray) as $key=> $day)
            {

                if($key<count(array_keys($newArray)) and $key>0)
                {
                    $filter_days.=" or DAYNAME(day)='".$day."'";
                    if($key==(count(array_keys($newArray))-1))
                    {
                        $filter_days.=' ) ';
                    }

                }
                else if(count(array_keys($newArray))==1)
                {
                    $filter_days.=" and ( DAYNAME(day)='".$day."')";
                }

                else{
                    $filter_days.=" and ( DAYNAME(day)='".$day."'";
                }
            }

            if(empty($filter_days))
            {
                $filter_days.=" and ( DAYNAME(day)='Sunday'  and DAYNAME(day)='Monday'
                and DAYNAME(day)='Tuesday' and DAYNAME(day)='Wednesday'
                and DAYNAME(day)='Thursday' and DAYNAME(day)='Friday' and DAYNAME(day)='Saturday')";
            }

            $sql="SELECT * FROM (
                SELECT DATE_ADD('".$current_dt."', INTERVAL t4+t16+t64+t256+t1024 DAY) day
                FROM
                 (SELECT 0 t4    UNION ALL SELECT 1   UNION ALL SELECT 2   UNION ALL SELECT 3  ) t4,
                 (SELECT 0 t16   UNION ALL SELECT 4   UNION ALL SELECT 8   UNION ALL SELECT 12 ) t16,
                 (SELECT 0 t64   UNION ALL SELECT 16  UNION ALL SELECT 32  UNION ALL SELECT 48 ) t64,
                 (SELECT 0 t256  UNION ALL SELECT 64  UNION ALL SELECT 128 UNION ALL SELECT 192) t256,
                 (SELECT 0 t1024 UNION ALL SELECT 256 UNION ALL SELECT 512 UNION ALL SELECT 768) t1024
                ) b
              WHERE day NOT IN (SELECT DATE(`start_time`) FROM appointments where is_del=0 and staff_id in(".$staffs.")  order by start_time asc) AND day>'".$current_dt."' ".$filter_days." limit 1;";
              //die($sql);
            $results = DB::select($sql);
            $missingDate = !empty($results)?$results[0]:'';


            $date = Carbon::createFromFormat('Y-m-d', $current_dt);

            $date = $date->addDays(1);



            return response()->json([
                'message' => "",
                'status' => true,
                'data' => !empty($missingDate->day)?date('F jS Y',strtotime($missingDate->day)):'',
                'formatted_date' => !empty($missingDate->day)?date('Y-m-d',strtotime($missingDate->day)):'',
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
//SELECT DATE(t1.start_time) + INTERVAL 1 DAY AS missing_date FROM appointments AS t1 where (staff_id IN(14,27,30) and DATE(t1.start_time)>'2023-07-12'  and DATE(t1.start_time) + INTERVAL 1 DAY not in(select DATE(t2.start_time) FROM appointments AS t2 where staff_id IN(14,27,30) and  DATE(t1.start_time)>'2023-07-12')) ORDER BY t1.start_time limit 1
