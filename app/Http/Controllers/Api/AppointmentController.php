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
use Ap\Models\Apppointment;
use App\Models\RepeatAppointment;
use App\Models\AppointmentLog;
use App\Models\Favourite;
use Storage;
use DB;

class AppointmentController extends Controller
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

    public function add(Request $request)
    {

        try {
            //dd($request->all());

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
                'staff_id' => 'required|exists:users,id',
                'start_time' => 'required',
                //'customer_link' => 'required_if:customer_id,!=,0|required_if:customer_id,!=,null',

            ]);

            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }

            $staffs[]=$request->staff_id;
            $staff_id=$request->staff_id;
            $customer_id=!empty($request->customer_id)?$request->customer_id:'';
            if(!empty($request->id))
            {
                request()->merge(['action' => 'Edit']);
            }

            if(!empty($customer_id) && $customer_id > 0)
            {
                if(empty($request->customer_link))
                {
                    return response()->json([
                        'status' => false,
                        'data' => [],
                        'message' => 'Customer link cannot be empty if customer added.',
                        'error' => ['Customer link cannot be empty if customer added.']

                    ], 200);
                }
            }
            if(!empty($request->staff_id_sub) and $request->staff_id_sub!='null'){
                $staffs[]=   $request->staff_id_sub;
            }
            if ( count( $staffs ) == count( array_unique( $staffs ) ) )
            {
                if (!empty($request->customer_id) and is_numeric($request->customer_id)) {
                    $customer = \App\Models\Customer::find($request->customer_id);
                } else {
                    $customer = new \App\Models\Customer();
                }
                if ((!empty($request->customer_id) and is_numeric($request->customer_id))
                    or (!empty($request->name) and $request->name != 'null') or (!empty($request->primary_contact_name) and $request->primary_contact_name != 'null')
                ) {
                    $customer->name = (!empty($request->name) and $request->name != 'null') ? $request->name : $request->primary_contact_name;
                    $customer->primary_contact_name = (!empty($request->primary_contact_name) and $request->primary_contact_name != 'null') ? $request->primary_contact_name : '';
                    $customer->mobile = (!empty($request->mobile) and $request->mobile != 'null') ? $request->mobile : '';
                    $customer->alternate_name = (!empty($request->alternate_name) and $request->alternate_name != 'null') ? $request->alternate_name : '';
                    $customer->alternate_mobile = (!empty($request->alternate_mobile) and $request->alternate_mobile != 'null') ? $request->alternate_mobile : '';
                    $customer->notes = (!empty($request->customer_notes) and $request->customer_notes != 'null') ? $request->customer_notes : '';
                    $customer->address = (!empty($request->address) and $request->address != 'null') ? $request->address : '';
                    $customer->customer_link = (!empty($request->customer_link) and $request->customer_link != 'null') ? $request->customer_link : '';

                    $customer->save();
                    $customer_id = $customer->id;
                }
               // echo "<pre>";print_r($staffs);exit;
                foreach($staffs as $staff)
                {
                    $is_repeat='N';
                    $request->bulk_update = $request->bulk_update === 'true'? true: false;

                    if (!empty($request->id)) {
                        $appointment = Appointment::find($request->id);
                        $is_repeat=$appointment->is_repeat;
                    } else {

                        $appointment = new Appointment();
                        $appointment->code = $this->generateRandomString(6);
                    }
                    $service = \App\Models\Customerservice::where('id', $request->customerservice_id)->where('is_del',0)->select('minutes')->first();
                    if (empty($request->duration)) {
                        $request->duration = $service->minutes;
                    }
                    $request->start_time = date('Y-m-d H:i:s', strtotime($request->start_time));
                    $end_time = Carbon::parse($request->start_time)
                        ->addMinutes($request->duration)
                        ->format('Y-m-d H:i:s');


                    $appointment->customer_id = is_numeric($customer_id) ? $customer_id : 0;
                    $appointment->customerservice_id = ($request->customerservice_id != 'null') ? $request->customerservice_id : 0;
                    $appointment->start_time = $request->start_time;
                    $appointment->end_time = $end_time;
                    $appointment->is_repeat = $request->is_repeat;
                    $appointment->notes = !empty($request->notes) ? $request->notes : '';
                    //$appointment->job_completion=!empty($request->job_completion)?$request->job_completion:'';
                    $appointment->extra_materials = !empty($request->extra_materials) ? ($request->extra_materials) : '';
                    $appointment->summary = !empty($request->summary) ? ($request->summary) : '';
                    $appointment->price = $request->price;
                    $appointment->no_term = $request->no_term;
                    $appointment->estimate = $request->estimate;
                    $appointment->current_status = $request->current_status;
                    $appointment->project_manager = $request->project_manager;
                    $appointment->time_in = !empty($request->time_in) ? $request->time_in : '';
                    $appointment->time_out = !empty($request->time_out) ? $request->time_out : '';
                    if ($request->is_repeat == 'C') {
                        $appointment->custom_type = $request->custom_type;
                        $appointment->custom_duration = $request->custom_duration;
                    } else {
                        $appointment->custom_type = null;
                        $appointment->custom_duration = null;
                    }

                    $appointment->staff_id = $staff;
                    $appointment->save();
                    if (empty($request->id)) {
                        $repeatApt = new RepeatAppointment();
                        $repeatApt->start_time = $appointment->start_time;
                        $repeatApt->end_time = $appointment->end_time;
                        $repeatApt->appointment_id = $appointment->id;
                        $repeatApt->parent_id = $appointment->id;
                        $repeatApt->save();

                    } else {
                        $repeatApt = RepeatAppointment::where('appointment_id', $appointment->id)->first();
                        $repeatApt->start_time = $appointment->start_time;
                        $repeatApt->end_time = $appointment->end_time;
                        $repeatApt->appointment_id = $appointment->id;
                        $repeatApt->save();

                    }
                    if($request->is_favourite)
                    {
                        $fav_data = [
                            'is_favourite' => "1"
                        ];
                    }
                    else
                    {
                        $fav_data = [
                            'is_favourite' => "0"
                        ];
                    }
                    $auth_user_id=\Auth::guard('api')->id();
                    $matchThese = ['user_id'=>$auth_user_id,'appoinment_id'=>$appointment->id];
                    Favourite::updateOrCreate($matchThese,$fav_data);
                    if ($request->is_repeat != 'N') {
                        if(empty($request->id))
                        {
                            for ($i = 0; $i < $request->no_term; $i++) {
                                Myhelper::repeatDates($appointment->id);
                            }
                        }
                        if(!empty($request->id))
                        {
                            $ref_id=$appointment->id;
                            if($request->bulk_update)
                            {
                                $new_cond[]=['is_del',0];
                                $new_cond[]=['parent_id',$repeatApt->parent_id];
                                $new_cond[]=['id','>',$appointment->id];
                                $child_appointments=Appointment::where($new_cond)->get();
                                foreach($child_appointments as $child_appointment)
                                {
                                    $new_rpt_apt=RepeatAppointment::where('appointment_id', $child_appointment->id)->first();
                                    Myhelper::updateBasicInfo($appointment->id,$child_appointment->id);
                                    Myhelper::repeatDates($repeatApt->parent_id,$new_rpt_apt->id,$ref_id);

                                }


                            }
                            if($is_repeat=='N' and $request->is_repeat!='N')
                            {
                                for ($i = 0; $i < $request->no_term; $i++) {
                                    Myhelper::repeatDates($appointment->id);
                                }
                            }

                        }
                    }
                    $event = $this->eventDetail($appointment->id);




                    $pusher_response = [
                        'status' => true,
                        'message' => "Appointment has been set successfully",
                        'data' => ['id' => $appointment->id, 'date' => '','staff_id'=>$staff_id],
                        'is_del' => false,
                    ];

                    $pusher->trigger('my-channel', 'my-event', json_encode($pusher_response));
                }

                $response = [
                    'status' => true,
                    'message' => "Appointment has been set successfully",
                    'data' => array_merge($event),
                    'is_del' => false,
                ];
            }
            else{
                $response = [
                    'status' => false,
                    'message' => "Duplicate Staff Not Allowed",
                    'data' => [],
                    'error'=>["Duplicate Staff Not Allowed"],
                    'is_del' => false,
                ];
            }


        return response()->json($response, 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function listing(Request $request)
    {

        try {
            $setting =\App\Models\Setting::where('id', 1)->first();
            $default_start = Carbon::createFromFormat('H', $setting->start_hour)->format('H:i:s');
            $default_end = Carbon::createFromFormat('H', $setting->end_hour)->format('H:i:s');
            $week_start = Myhelper::weekstart($setting->week_start);
            $default_mode = !empty($request->default_mode) ? $request->default_mode : $setting->default_mode;
            if (empty($request->start_date)) {
                if ($default_mode == 'dayGridMonth') {
                    $request->start_date = Carbon::now()->startOfMonth()->format("Y-m-d");
                    $request->end_date = Carbon::now()->endOfMonth()->format("Y-m-d");
                    $codition[] = ['appointments.customer_id','>',0];
                    $codition[] = ['appointments.customer_id','!=',417];
                    $codition[] = ['appointments.customer_id','!=',418];
                    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, 10, 2024);

                }
                if ($default_mode == 'timeGridWeek') {
                    $request->start_date = Carbon::now()->startOfWeek($week_start)->format("Y-m-d");
                    $request->end_date = Carbon::parse($request->start_date)->endOfWeek()->format("Y-m-d");
                }
                if ($default_mode == 'resourceTimeGrid') {
                    $request->start_date = Carbon::now()->format("Y-m-d");
                    $request->end_date = Carbon::now()->format("Y-m-d");
                }
            } else {
                $now = $request->start_date;
                if ($default_mode == 'dayGridMonth') {
                    $request->start_date = Carbon::parse($now)->startOfMonth()->format("Y-m-d");
                    $request->end_date = Carbon::parse($now)->endOfMonth()->format("Y-m-d");
                    $codition[] = ['appointments.customer_id','>',0];
                    $codition[] = ['appointments.customer_id','!=',417];
                    $codition[] = ['appointments.customer_id','!=',418];

                }
                if ($default_mode == 'timeGridWeek') {
                    $request->start_date = Carbon::parse($now)->startOfWeek($week_start)->format("Y-m-d");
                    $request->end_date = Carbon::parse($now)->endOfWeek()->format("Y-m-d");
                }
                if ($default_mode == 'resourceTimeGrid') {
                    $request->start_date = Carbon::parse($now)->format("Y-m-d");
                    $request->end_date = Carbon::parse($now)->format("Y-m-d");
                }
            }
            if ($default_mode == 'dayGridMonth') {
                $heading = Carbon::parse($request->start_date)->format("M Y");
            }
            if ($default_mode == 'timeGridWeek') {
                $heading = Carbon::parse($request->start_date)->format("M d") . ' - ' . Carbon::parse($request->end_date)->format("M d");
            }
            if ($default_mode == 'resourceTimeGrid') {
                $heading = Carbon::parse($request->start_date)->format("M d, Y");
                $day_name = Carbon::parse($request->start_date)->format("l");
            }
            $codition[] = ['appointments.is_del', 0];
            $days = ['Sunday','Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday','Saturday'];

            if (!empty($request->staff_id)) {
                $codition[] = ['appointments.staff_id', $request->staff_id];
            }

            $codition[] = ['repeat_appointments.start_time', '>=', $request->start_date . ' 00:00:00'];
            $codition[] = ['repeat_appointments.end_time', '<=', $request->end_date . ' 23:59:59'];
            $auth_user_id=\Auth::guard('api')->id();
            $appointments_raw = Appointment::select('appointments.id')
                ->leftJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')
                ->with(['favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])
                ->where($codition);
            if ($default_mode == 'dayGridMonth') {
                if(!empty($request->listing_for))
                {
                    $appointments=$appointments_raw->groupBy('appointments.customer_id','appointments.start_time')
                    ->get();
                }
                else{
                    $appointments=$appointments_raw->where(DB::raw('TIMESTAMPDIFF(MINUTE, appointments.start_time, appointments.end_time)'), '>=', 240)

                    ->groupBy('appointments.customer_id','appointments.start_time')
                    ->get();
                }

            }
            else{
                $appointments=$appointments_raw->get();
            }
            $events = array();
            $resources = [];
            if (!empty($appointments->count())) {

                foreach ($appointments as $appointment) {
                    $mode=$default_mode == 'dayGridMonth'?$default_mode:"";
                    $events[] = $this->eventDetail($appointment->id, $request->start_date,$mode);
                }
            }

            $cond[] = ['is_del', 0];
            $cond[] = ['id', '!=', 1];
            $or_cond[] = ['user_type', 'staff'];
            $or_cond[] = ['user_type', 'admin'];
            $staffs = User::where($cond)->orWhere($or_cond)->select('id', 'name', 'sort_order')->orderBy('sort_order', 'asc')->get();
            $working_hours = [];
            if (($default_mode == 'resourceTimeGrid')) {
                foreach ($staffs as $staff) {

                    $working_hour = \App\Models\Workinghour::where('user_id', $staff->id)
                    ->where('day_name', $day_name)
                    ->where('is_weekday',1)
                    ->first();


                        $businessHours = array('startTime' => !empty($working_hour->start_time) ? $working_hour->start_time :'00:00:00',
                        'endTime' => !empty($working_hour->end_time) ? $working_hour->end_time : '00:00:00','daysOfWeek'=>[array_search($day_name,$days)]

                    );


                    $working_hours[] = ['id' => $staff->id, 'name' => $staff->name, 'title' => $staff->name, 'businessHours' => $businessHours, 'sort_order' => (int) $staff->sort_order];
                }
            } else {
                foreach ($staffs as $staff) {
                    $working_hours[] = ['id' => $staff->id, 'name' => $staff->name, 'title' => $staff->name, 'businessHours' => "", 'sort_order' => (int) $staff->sort_order];
                }
            }
            if($default_mode == 'dayGridMonth' and empty($request->listing_for))
            {
               $events = $this->fillMissingDates($events, date('Y',strtotime($request->start_date)), date('m',strtotime($request->start_date)));
            }

            $result_array = ['events' => !empty($events) ? $events : array(), 'heading' => !empty($heading) ? $heading : '', 'staffs' => !empty($working_hours) ? $working_hours : array()];
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

    public function delete(Request $request)
    {
        try {
            require base_path() . '/vendor/pusher/autoload.php';
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:appointments,id',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            request()->merge(['action' => 'Delete']);
            $request->bulk_delete = $request->bulk_delete === 'true'? true: false;

            $id = $request->id;
            $appointment = Appointment::find($id);
            $repeatApt = RepeatAppointment::where(['appointment_id'=>$appointment->id])->first();
            $appointment->is_del = 1;
            $appointment->save();
            $staff_id=$appointment->staff_id;
            //DB::table('repeat_appointments')->where('appointment_id', $id)->delete();
            $tobe_updated_appointments=[];
            $staffs=[];
            $cond[]=[DB::raw('DATE(start_time)'), '=', date('Y-m-d', strtotime($appointment->start_time))];
            $cond[]=['customer_id',$appointment->customer_id];
            $cond[]=['is_del',0];
            $newsameAppointments=Appointment::select('id','staff_id')
            ->with(['staff' => function ($query) {$query->select('id', 'name');}])
            ->where($cond)->groupBy('staff_id')->get();
            foreach( $newsameAppointments as  $sameAppointment)
            {
                if(\Auth::guard('api')->id()!=$sameAppointment->staff->id and \Auth::guard('api')->id()>1 and count($newsameAppointments)>1)
                {
                    $staffs[]='<span class="text-danger" style="color:#f00;">'.$sameAppointment->staff->name.'</span>';
                }
                else{
                    $staffs[]='<span>'.$sameAppointment->staff->name.'</span>';

                }
                $tobe_updated_appointments[]=$sameAppointment->id;
            }
            $app_id = getenv('PUSHER_APP_ID');
            $app_key = getenv('PUSHER_API_KEY');
            $app_secret = getenv('PUSHER_SECRET_KEY');

            $options = array(
                'cluster' => getenv('PUSHER_CLUSTER'),
                'useTLS' => true
            );

            if($request->bulk_delete)
            {
                $codition[] = ['parent_id',$repeatApt->parent_id];
                $codition[] = ['appointment_id','>',$appointment->id];
                $next_appointments= RepeatAppointment::where($codition)->get();
                foreach($next_appointments as $next_appointment)
                {
                    $appointment = Appointment::find($next_appointment->appointment_id);
                    $appointment->is_del = 1;
                    $appointment->save();

                }
            }
            $pusher = new Pusher\Pusher($app_key, $app_secret, $app_id, $options);

            $response = [
                'status' => true,
                'data' => ['id' => $id,'staff_id'=>$staff_id,'staff_name'=>implode(" + ",$staffs),'appointments'=>$tobe_updated_appointments],
                'is_del' => true,
            ];
            $pusher->trigger('my-channel', 'my-event', json_encode($response));

            return response()->json([
                'status' => true,
                'message' => "Appointment has been Deleted successfully",
                'data'=>['staff_name'=>implode(" + ",$staffs),'appointments'=>$tobe_updated_appointments]

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function dashboard(Request $request)
    {

        try {
            $setting =\App\Models\Setting::where('id', 1)->first();
            $start = !empty($request->start) ? $request->start : 0;
            $end = !empty($request->end) ? $request->end : 100;

            //$week_start=Myhelper::weekstart($setting->week_start);
            $week_start = Carbon::MONDAY;
            $start_date = Carbon::now()->startOfWeek($week_start)->format("Y-m-d");
            $end_date = Carbon::parse($start_date)->endOfWeek()->format("Y-m-d");
            $codition[] = ['appointments.is_del', 0];
            //$codition[]=['repeat_appointments.start_time',">=",$start_date.' 00:00:00'];
            //$codition[]=['repeat_appointments.end_time',"<=",$end_date.' 23:59:59'];
            $select = [
                'appointments.id', 'repeat_appointments.start_time', 'repeat_appointments.end_time', 'appointments.staff_id', 'appointments.customer_id', 'appointments.current_status',
                'appointments.estimate', 'appointments.extra_materials', 'appointments.summary', 'appointments.notes', 'appointments.customerservice_id', 'appointments.time_in', 'appointments.time_out','appointments.project_manager'
            ];
            if (!empty($request->customer_id)) {
                $codition[] = ['appointments.customer_id', $request->customer_id];
            }
            $auth_user_id=\Auth::guard('api')->id();
            $totalCount = Appointment::select($select)
                ->leftJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')

                ->where($codition)->with(['customer' => function ($query) {
                    $query->select('id', 'name');
                }, 'staff' => function ($query) {
                    $query->select('id', 'name');
                }, 'service' => function ($query) {
                    $query->select('id', 'name');
                },'favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])

                ->get()
                ->count();



            $appointments = Appointment::select($select)
                ->leftJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')
                ->offset($start)
                ->limit($end)->where($codition)->with(['customer' => function ($query) {
                    $query->select('id', 'name', 'mobile', 'address','alternate_name','alternate_mobile','notes','primary_contact_name','customer_link');
                }, 'staff' => function ($query) {
                    $query->select('id', 'name');
                },'favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])
                ->orderByDesc('start_time')
                ->get()

                ->map(function ($item, $key) use ($auth_user_id){
                    $end_time = !empty($item->end_time)?Carbon::createFromFormat('Y-m-d H:i:s', $item->end_time):'';

                    $start_time = !empty($item->start_time)?Carbon::createFromFormat('Y-m-d H:i:s', $item->start_time):'';
                    $item->meeting_length =  $end_time?$end_time->diffInMinutes($start_time):0;
                    $item->estimate = (!empty($item->estimate) and $item->estimate != 'null') ? mb_convert_encoding($item->estimate,'UTF-8', 'UTF-8') : '';
                    $item->time_in = !empty($item->time_in) ?mb_convert_encoding($item->time_in,'UTF-8', 'UTF-8') : '';
                    $item->time_out = !empty($item->time_out) ? mb_convert_encoding($item->time_out,'UTF-8', 'UTF-8') : '';
                    $summary=html_entity_decode(htmlentities($item->summary, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
                    $notes=html_entity_decode(htmlentities($item->notes, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');

                    $item->summary=mb_convert_encoding($summary,'UTF-8', 'UTF-8');
                    $item->notes=mb_convert_encoding($notes,'UTF-8', 'UTF-8');

                    $cond[]=[DB::raw('DATE(start_time)'), '=', date('Y-m-d', strtotime($item->start_time))];
                    $sameAppointmets=[];
                    if(!empty($item->customer_id))
                    {
                        $cond[]=['customer_id',$item->customer_id];
                        $cond[]=['is_del',0];
                        $newsameAppointments=Appointment::select('staff_id')->with(['staff' => function ($query) use($auth_user_id){$query->select('id', 'name');},'favourite_dtls' => function ($query1) use($auth_user_id) {
                            $query1->where('user_id', $auth_user_id);
                            $query1->where('is_favourite', '1');
                        }])
                        ->where($cond)->groupBy('staff_id')->get();
                        foreach( $newsameAppointments as  $sameAppointment)
                        {
                            if(\Auth::guard('api')->id()!=$sameAppointment->staff->id and \Auth::guard('api')->id()>1 and count($newsameAppointments)>1)
                            {
                                $sameAppointmets[]='<span class="text-danger" style="color:#f00;">'.$sameAppointment->staff->name.'</span>';
                            }
                            else{
                                $sameAppointmets[]='<span>'.$sameAppointment->staff->name.'</span>';

                            }

                        }
                    }
                    else{
                        $sameAppointmets[]='<span>'.$item->staff->name.'</span>';
                    }
                    $item->all_staff_name=implode(" + ",$sameAppointmets);



                    return $item;
                });
            $response = ['appointments' => !empty($appointments) ? $appointments : array(), 'week_start' => $start_date, 'week_end' => $end_date, 'totalCount' => $totalCount];
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

    public function editAppointment(Request $request)
    {
        try {
            require base_path() . '/vendor/pusher/autoload.php';
            $setting =\App\Models\Setting::where('id', 1)->first();
            request()->merge(['action' => 'Re-Schedule']);
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:appointments,id',
                'start_time' => 'required',
                'end_time' => 'required',
                'staff_id' => 'required',


            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $customer_id=!empty($request->customer_id)?$request->customer_id:'';
            // if(!empty($customer_id) && $customer_id > 0)
            // {
            //     if(empty($request->customer_link))
            //     {
            //         return response()->json([
            //             'status' => false,
            //             'data' => [],
            //             'message' => 'Customer link cannot be empty if customer added.',
            //             'error' => ['Customer link cannot be empty if customer added.']

            //         ], 200);
            //     }
            // }

            $request->bulk_update = $request->bulk_update === 'true'? true: false;
            $appointment = Appointment::find($request->id);
            $staff_id=$request->staff_id;
            $request->start_time = date('Y-m-d H:i:s', strtotime($request->start_time));
            $appointment->start_time = $request->start_time;
            $appointment->end_time = Carbon::createFromFormat('Y-m-d H:i:s', $request->end_time);
            $appointment->staff_id = $request->staff_id;
            //$appointment->customer_link = $request->customer_link;
            $startTime = Carbon::parse($appointment->start_time);
            $endTime = Carbon::parse($appointment->end_time);
            $difference = $startTime->diffInMinutes($endTime);
            $service = \App\Models\Customerservice::where('minutes', $difference)->where('is_del',0)->first();
            if (!empty($service)) {
                $appointment->customerservice_id = $service->id;
            } else {
                $appointment->customerservice_id = 0;
            }

            $appointment->save();
            if($request->is_favourite)
            {
                $fav_data = [
                    'is_favourite' => "1"
                ];
            }
            else
            {
                $fav_data = [
                    'is_favourite' => "0"
                ];
            }
            $auth_user_id=\Auth::guard('api')->id();
            $matchThese = ['user_id'=>$auth_user_id,'appoinment_id'=>$appointment->id];
            Favourite::updateOrCreate($matchThese,$fav_data);
            $repeatApt = RepeatAppointment::where('appointment_id', $appointment->id)->first();
            $repeatApt->start_time = $appointment->start_time;
            $repeatApt->end_time = $appointment->end_time;
            $repeatApt->appointment_id = $appointment->id;
            $repeatApt->save();

            if ($appointment->is_repeat != 'N' and $request->bulk_update) {
            $codition[] = ['parent_id',$repeatApt->parent_id];
            $codition[] = ['appointment_id','>',$appointment->id];
            $ref_id= $appointment->id;
            $next_appointments= RepeatAppointment::where($codition)->get();
            foreach($next_appointments as $next_appointment)
            {
                Myhelper::repeatDates($repeatApt->parent_id,$next_appointment->id,$ref_id);

            }

            }
            $app_id = getenv('PUSHER_APP_ID');
            $app_key = getenv('PUSHER_API_KEY');
            $app_secret = getenv('PUSHER_SECRET_KEY');

            $options = array(
                'cluster' => getenv('PUSHER_CLUSTER'),
                'useTLS' => true
            );

            $pusher = new Pusher\Pusher($app_key, $app_secret, $app_id, $options);


            $response = [
                'status' => true,
                'data' => ['id' => $appointment->id, 'date' => $request->currentDate,'staff_id'=>$staff_id],
                'is_del' => false,
            ];
            $pusher->trigger('my-channel', 'my-event', json_encode($response));
            return response()->json([
                'status' => true,
                'message' => "Appointment has been set successfully",
                'data' => $this->eventDetail($appointment->id, $request->currentDate)

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function statistics(Request $request)
    {
        try {
            $setting =\App\Models\Setting::where('id', 1)->first();
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',


            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $setting =\App\Models\Setting::where('id', 1)->first();
            $week_start = Myhelper::weekstart($setting->week_start);
            $months = [1 => 'Jan', 2 => 'Feb', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

            if (!empty($request->year)) {
                $year = $request->year;
            } else {
                $year = date('Y');
            }
            $pie_start_date = date($year . '-01-01');
            $pie_end_date = date($year . '-12-31');

            $cond[] = [DB::raw('YEAR(start_time)'), '=', $year];
            $cond[] = ['customer_id', $request->customer_id];
            $pie_Points = [];
            $line_Points = [];
            if (!empty($request->month)) {
                $month = $request->month;
                $date = $year . '-' . $request->month . '-01';
                $dt = Carbon::parse($date); //or initialize it any other way
                $start_date = $dt->format("Y-m-d");
                $end_date = $dt->addDays(4)->format("Y-m-d");
                $pie_start_date = $start_date;
                $pie_end_date = $dt->endOfMonth()->format("Y-m-d");
                for ($i = 1; $i <= 6; $i++) {
                    unset($date_cond);
                    $date_cond[] = [DB::raw('DATE(start_time)'), ">=", $start_date];
                    $date_cond[] = [DB::raw('DATE(start_time)'), "<=", $end_date];
                    $appointment = Appointment::where($cond)->where($date_cond)->select(DB::raw('COUNT(id) AS appointment'))->first();
                    $label = date('j', strtotime($start_date)) . '-' . date('j', strtotime($end_date));
                    $line_Points[] = ['label' => $label, 'y' => (int)$appointment->appointment];
                    $dt = Carbon::parse($end_date);
                    $start_date = $dt->addDay()->format("Y-m-d");
                    $end_date = $dt->addDays(4)->format("Y-m-d");
                }
            } else {
                foreach ($months as $key => $month) {

                    $appointment = Appointment::where($cond)->where(DB::raw('MONTH(start_time)'), '=', $key)->select(DB::raw('COUNT(id) AS appointment'))->first();
                    $line_Points[] = ['label' => $month, 'y' => (int)$appointment->appointment];
                }
            }
            $services = \App\Models\Customerservice::where('is_del', 0)->orderBy('id', 'desc')->get();
            $colors = [];
            $appointment_services = Appointment::where('customer_id', $request->customer_id)
                ->where(DB::raw('DATE(start_time)'), ">=", $pie_start_date)
                ->where(DB::raw('DATE(start_time)'), "<=", $pie_end_date)
                ->select(DB::raw('COUNT(id) AS appointment'), 'customerservice_id')->with('service')->groupBy('customerservice_id')->get();

            foreach ($appointment_services as $appointment) {

                $pie_Points[] = ['name' => $appointment->service->name, 'y' => (int)$appointment->appointment, 'color' => $appointment->service->color];
            }
            return response()->json([
                'status' => true,
                'message' => "Appointment has been set successfully",
                'data' => ['line_Points' => $line_Points, 'pie_Points' => $pie_Points]


            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function reSchedule(Request $request)
    {
        try {
            require base_path() . '/vendor/pusher/autoload.php';
            $request->bulk_update = $request->bulk_update === 'true'? true: false;
            request()->merge(['action' => 'Re-Schedule']);
            $appointment = Appointment::find($request->id);
            $duration = Myhelper::getDuration($request->id);
            $end_time = Carbon::parse($request->start_time)
                ->addMinutes($duration)
                ->format('Y-m-d H:i:s');
            $repeatApt = RepeatAppointment::where('appointment_id', $appointment->id)->first();
            $repeatApt->start_time = $request->start_time;
            $repeatApt->end_time = $end_time;
            $repeatApt->appointment_id = $appointment->id;
            $repeatApt->save();
            if($appointment->is_repeat!='N')
            {
                if(!$request->bulk_update)
                {

                    $appointment->no_term=null;
                    $appointment->custom_type=null;
                    $appointment->custom_duration=null;
                    $appointment->is_repeat='N';
                }
                else{
                    $day_names=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

                    $start_time =$repeatApt->start_time;
                    $end_time =$repeatApt->end_time;
                    $last_dayname=Carbon::parse($repeatApt->start_time)->format('l');
                    if(in_array($appointment->is_repeat, $day_names))
                    {
                        $appointment->is_repeat=$last_dayname;
                    }
                    else if($appointment->is_repeat>=1 and $appointment->is_repeat<=31)
                    {
                        $appointment->is_repeat=str_pad(Carbon::parse($repeatApt->start_time)->format('d'),2,"0",STR_PAD_LEFT);
                    }


                }
            }
            $appointment->start_time = $request->start_time;
            $appointment->end_time = $end_time;
            $appointment->staff_id = $request->staff_id;
            $appointment->save();
            $codition[] = ['parent_id',$repeatApt->parent_id];
            $codition[] = ['appointment_id','>',$appointment->id];
            $ref_id= $appointment->id;
            $staff_id=$request->staff_id;
            $next_appointments= RepeatAppointment::where($codition)->get();
            foreach($next_appointments as $next_appointment)
            {
                Myhelper::repeatDates($repeatApt->parent_id,$next_appointment->id,$ref_id);

            }



            $event = $this->eventDetail($appointment->id);
            $app_id = getenv('PUSHER_APP_ID');
            $app_key = getenv('PUSHER_API_KEY');
            $app_secret = getenv('PUSHER_SECRET_KEY');

            $options = array(
                'cluster' => getenv('PUSHER_CLUSTER'),
                'useTLS' => true
            );

            $pusher = new Pusher\Pusher($app_key, $app_secret, $app_id, $options);

            $response = [
                'status' => true,
                'data' => ['id' => $appointment->id, 'date' => '','staff_id'=>$staff_id],
                'is_del' => false,
            ];
            $pusher->trigger('my-channel', 'my-event', json_encode($response));


            return response()->json([
                'status' => true,
                'message' => "Appointment has been rescheduled successfully",
                'data' => $event,

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function viewAppointment(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:appointments,id',

            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $auth_user_id=\Auth::guard('api')->id();
            $appointment = Appointment::with(['staff' => function ($query) {
                $query->select('id', 'name');
            }, 'service', 'customer','favourite_dtls' => function ($query1) use($auth_user_id,$request) {
                $query1->where('user_id', $auth_user_id);
                $query1->where('is_favourite', "1");
                $query1->where('appoinment_id', $request->id);

            }])->find($request->id);
            //echo "<pre>";print_r( $appointment->toArray());exit;
            $startTime = Carbon::parse($appointment->start_time);
            $endTime = Carbon::parse($appointment->end_time);
            $difference = $startTime->diffInMinutes($endTime);
            $duration = $difference;
            $job_completion = html_entity_decode(htmlentities($appointment->job_completion, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
            $extra_materials = html_entity_decode(htmlentities($appointment->extra_materials, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
            $summary = html_entity_decode(htmlentities($appointment->summary, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
            $notes = html_entity_decode(htmlentities($appointment->notes, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
            $ref_repeat=RepeatAppointment::where(['appointment_id'=>$request->id])->first();
            $cond_new[]=['parent_id',$ref_repeat->parent_id];
            $cond_new[]=['id','>',$ref_repeat->id];
            $repeats_appointments=RepeatAppointment::where($cond_new)->count();
            $is_recurring=false;
            if($appointment->is_repeat!='N')
            {
                $is_recurring=true;
            }
            $cond[]=[DB::raw('DATE(start_time)'), '=', date('Y-m-d', strtotime($appointment->start_time))];
            $sameAppointmets=[];
            $to_be_updated=[];
            if(!empty($appointment->customer_id))
            {
                $cond[]=['customer_id',$appointment->customer_id];
                $cond[]=['is_del',0];
                $newsameAppointments=Appointment::select('id','staff_id')->with(['staff' => function ($query) {$query->select('id', 'name');},'favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])
                ->where($cond) ->groupBy('staff_id')->get();

                foreach( $newsameAppointments as  $sameAppointment)
                {
                    if(\Auth::guard('api')->id()!=$sameAppointment->staff->id and \Auth::guard('api')->id()>1 and count($newsameAppointments)>1)
                    {
                        $sameAppointmets[]='<span class="text-danger" style="color:#f00;">'.$sameAppointment->staff->name.'</span>';
                    }
                    else{
                        $sameAppointmets[]='<span>'.$sameAppointment->staff->name.'</span>';

                    }
                    $to_be_updated[]=$sameAppointment->id;
                }
            }
            else{
                $sameAppointmets[]='<span>'.$appointment->staff->name.'</span>';
            }
            $appointmentDtl = [
                'id' => $appointment->id, 'staff_id' => $appointment->staff->id, 'customer_id' => !empty($appointment->customer->id) ? $appointment->customer->id : '',
                'favourite_dtls' => $appointment->favourite_dtls,

                'customerservice_id' => !is_null($appointment->service) ? $appointment->service->id : '', 'service_name' => !is_null($appointment->service) ? mb_convert_encoding($appointment->service->name,'UTF-8', 'UTF-8') : '',

                'duration' => $duration, 'start_time' => $appointment->start_time, 'is_repeat' => $appointment->is_repeat,

                'notes' => !is_null($appointment->notes) ? mb_convert_encoding($notes,'UTF-8', 'UTF-8') : 'N/A', 'current_status' => $appointment->current_status,

                'extra_materials' => mb_convert_encoding($extra_materials,'UTF-8', 'UTF-8'), 'summary' => mb_convert_encoding($summary,'UTF-8', 'UTF-8'), 'no_term' => !empty($appointment->no_term) ? $appointment->no_term : 0,

                'estimate' => (!empty($appointment->estimate) && $appointment->estimate != 'null') ? mb_convert_encoding($appointment->estimate,'UTF-8', 'UTF-8') : '',

                'name' => (!empty($appointment->customer->name) && $appointment->customer->name != 'null') ? mb_convert_encoding($appointment->customer->name,'UTF-8', 'UTF-8') : '',

                'mobile' => (!empty($appointment->customer->mobile) && $appointment->customer->mobile != 'null') ? mb_convert_encoding($appointment->customer->mobile,'UTF-8', 'UTF-8') : '',

                'primary_contact_name' => (!empty($appointment->customer->primary_contact_name) && $appointment->customer->primary_contact_name != 'null') ? mb_convert_encoding($appointment->customer->primary_contact_name,'UTF-8', 'UTF-8') : '',

                'alternate_name' => (!empty($appointment->customer->alternate_name) && $appointment->customer->alternate_name != 'null') ? mb_convert_encoding($appointment->customer->alternate_name,'UTF-8', 'UTF-8') : '',

                'alternate_mobile' => (!empty($appointment->customer->alternate_mobile) && $appointment->customer->alternate_mobile != 'null') ? mb_convert_encoding($appointment->customer->alternate_mobile,'UTF-8', 'UTF-8') : '',

                'address' => (!empty($appointment->customer->address) && $appointment->customer->address != 'null') ? mb_convert_encoding($appointment->customer->address,'UTF-8', 'UTF-8') : '',

                'customer_notes' => (!empty($appointment->customer->notes) && $appointment->customer->notes != 'null') ? mb_convert_encoding($appointment->customer->notes,'UTF-8', 'UTF-8') : '',

                'custom_type' => $appointment->custom_type, 'custom_duration' => $appointment->custom_duration, 'project_manager' => $appointment->project_manager != 'null' ? mb_convert_encoding($appointment->project_manager,'UTF-8', 'UTF-8') : '',
                'time_in' => !empty($appointment->time_in) ? mb_convert_encoding($appointment->time_in,'UTF-8', 'UTF-8') : '',
                'time_out' => !empty($appointment->time_out) ? mb_convert_encoding($appointment->time_out,'UTF-8', 'UTF-8') : '',
                'job_completion'=>!empty($appointment->job_completion) ? mb_convert_encoding($job_completion,'UTF-8', 'UTF-8') : '','is_recurring'=>$is_recurring,
                'next_appointment'=>$repeats_appointments>0?true:false,'customer_link'=>!empty($appointment->customer->customer_link) ? mb_convert_encoding($appointment->customer->customer_link,'UTF-8', 'UTF-8') : '',
                'staff_name'=>!empty($sameAppointmets) ? mb_convert_encoding(implode(" + ",$sameAppointmets),'UTF-8', 'UTF-8') : ''


            ];


            return response()->json([
                'status' => true,
                'message' => "Appointment has been set successfully",
                'data' => $appointmentDtl

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function generateRandomString($length = 25)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function yaerOptions(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:customers,id',


            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $months = [1 => 'Jan', 2 => 'Feb', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

            $year_options = Appointment::where('customer_id', $request->customer_id)->select(DB::raw('YEAR(start_time) as year'))->groupBy(DB::raw('YEAR(start_time)'))->get();

            $month_options = Appointment::where('customer_id', $request->customer_id)
                ->select(DB::raw('MONTH(start_time) as month'))
                ->groupBy(DB::raw('MONTH(start_time)'))->get()
                ->map(
                    function ($item) use ($months) {
                        $item->month_name = $months[$item->month];
                        $item->month_index = $item->month;
                        return $item;
                    }
                );
            return response()->json([
                'status' => true,
                'message' => "Appointment has been set successfully",
                'data' => ['year_options' => $year_options, 'month_options' => $month_options]


            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function eventDetail($id, $date = '',$default_mode='')
    {
        $auth_user_id=\Auth::guard('api')->id();
        $appointment = Appointment::with(['staff' => function ($query) use($auth_user_id){
            $query->select('id', 'name');
        }, 'service', 'customer','favourite_dtls' => function ($query1) use($auth_user_id) {
            $query1->where('user_id', $auth_user_id);
            $query1->where('is_favourite', '1');
        }])->where('id', $id)->first();

        $setting =\App\Models\Setting::where('id', 1)->first();

        $cond[]=[DB::raw('DATE(start_time)'), '=', date('Y-m-d', strtotime($appointment->start_time))];
        $sameAppointmets=[];
        $to_be_updated=[];
        if(!empty($appointment->customer_id))
        {
            $cond[]=['customer_id',$appointment->customer_id];
            $cond[]=['is_del',0];
            $newsameAppointments=Appointment::select('id','staff_id')->with(['staff' => function ($query) {$query->select('id', 'name');},'favourite_dtls' => function ($query1) use($auth_user_id) {
                $query1->where('user_id', $auth_user_id);
                $query1->where('is_favourite', '1');
            }])
            ->where($cond) ->groupBy('staff_id')->get();

            foreach( $newsameAppointments as  $sameAppointment)
            {
                if(\Auth::guard('api')->id()!=$sameAppointment->staff->id and \Auth::guard('api')->id()>1 and count($newsameAppointments)>1)
                {
                    $sameAppointmets[]='<span class="text-danger" style="color:#f00;">'.$sameAppointment->staff->name.'</span>';
                }
                else{
                    $sameAppointmets[]='<span>'.$sameAppointment->staff->name.'</span>';

                }
                $to_be_updated[]=$sameAppointment->id;
            }
        }
        else{
            $sameAppointmets[]='<span>'.$appointment->staff->name.'</span>';
        }

        $repeat = "";
        // if (!empty($date) and (date('Y-m-d', strtotime($appointment->start_time)) < $date)) {
        //     $appointment->start_time = $date . ' ' . date('H:i:s', strtotime($appointment->start_time));
        //     $appointment->end_time = $date . ' ' . date('H:i:s', strtotime($appointment->end_time));
        //     $repeat = "Repeat ";
        //     $is_recurring = true;
        // } else {
        //     $is_recurring = false;
        // }
        $dayName = date('l', strtotime($appointment->start_time));

        $start_date = date('Y-m-d', strtotime($appointment->start_time));
        $start_time = date('H:i:s', strtotime($appointment->start_time));
        $start = $start_date . 'T' . $start_time;
        $to = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->end_time);
        $from = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->start_time);
        $meeting_length = $to->diffInMinutes($from);
        $meeting_end_time = $to;
        $end_date = date('Y-m-d', strtotime($meeting_end_time));
        $end_time = date('H:i:s', strtotime($meeting_end_time));
        $end = $end_date . 'T' . $end_time;
        $startTime = Carbon::parse($appointment->start_time);
        $endTime = Carbon::parse($appointment->end_time);
        $difference = $startTime->diffInMinutes($endTime);
        $duration = $difference;
        if (is_null($appointment->job_completion) or empty($appointment->job_completion)) {
            $job_completion = "N/A";
        } else {
            $job_completion = html_entity_decode(htmlentities($appointment->job_completion, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
        }
        if (is_null($appointment->extra_materials) or empty($appointment->extra_materials)) {
            $extra_materials = "N/A";
        } else {
            $extra_materials = html_entity_decode(htmlentities($appointment->extra_materials, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
        }
        if (is_null($appointment->summary) or empty($appointment->summary)) {
            $summary = "N/A";
        } else {
            $summary = html_entity_decode(htmlentities($appointment->summary, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');

        }
        //$serv_name=$repeat.'Meeting('.$duration.')';
        $is_recurring=false;
        if($appointment->is_repeat!='N')
        {
            $is_recurring=true;
        }
        $ref_repeat=RepeatAppointment::where(['appointment_id'=>$id])->first();

        $cond_new[]=['parent_id',$ref_repeat->parent_id];
        $cond_new[]=['id','>',$ref_repeat->id];
        $repeats_appointments=RepeatAppointment::where($cond_new)->count();


        $serv_name = (!empty($appointment->notes) and $appointment->notes != 'null') ? html_entity_decode(htmlentities($appointment->notes, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15') : '';
        $appointmentDtl = [
            'id' => $appointment->id, 'service_name' => mb_convert_encoding($serv_name,'UTF-8', 'UTF-8'),

            'notes' => !empty($appointment->notes) ? mb_convert_encoding($appointment->notes,'UTF-8', 'UTF-8') : 'N/A',

            'name' => (!empty($appointment->customer->name) and ($appointment->customer->name != 'null')) ? mb_convert_encoding($appointment->customer->name,'UTF-8', 'UTF-8') : 'N/A',
            'primary_contact_name' => (!empty($appointment->customer->primary_contact_name) and ($appointment->customer->primary_contact_name != 'null')) ? mb_convert_encoding($appointment->customer->primary_contact_name,'UTF-8', 'UTF-8') : 'N/A',

            'mobile' => (!empty($appointment->customer->mobile) and $appointment->customer->mobile != 'null') ? mb_convert_encoding($appointment->customer->mobile,'UTF-8', 'UTF-8') : '',

            'email' => (!empty($appointment->customer->email) and $appointment->customer->email != 'null') ?mb_convert_encoding($appointment->customer->email,'UTF-8', 'UTF-8') : '',

            'alternate_name' => (!empty($appointment->customer->alternate_name) and $appointment->customer->alternate_name != 'null') ? mb_convert_encoding($appointment->customer->alternate_name,'UTF-8', 'UTF-8') : '',

            'alternate_mobile' => (!empty($appointment->customer->alternate_mobile) and $appointment->customer->alternate_mobile != 'null') ? mb_convert_encoding($appointment->customer->alternate_mobile,'UTF-8', 'UTF-8') : '',

            'start_time' => $appointment->start_time, 'current_status' => $appointment->current_status, 'project_manager' => $appointment->project_manager != 'null' ? mb_convert_encoding($appointment->project_manager,'UTF-8', 'UTF-8') : '',
            'extra_materials' => mb_convert_encoding($extra_materials,'UTF-8', 'UTF-8'),

            'customer_address' => (!empty($appointment->customer->address) and $appointment->customer->address != 'null') ? mb_convert_encoding($appointment->customer->address,'UTF-8', 'UTF-8') : '',

            'summary' => mb_convert_encoding($summary,'UTF-8', 'UTF-8'), 'is_recurring' => $is_recurring, 'customer_id' => !empty($appointment->customer) ? base64_encode($appointment->customer->id) : 0,
            'customer_notes' => (!empty($appointment->customer->notes) && $appointment->customer->notes != 'null') ? mb_convert_encoding($appointment->customer->notes,'UTF-8', 'UTF-8') : '',
            'time_in' => !empty($appointment->time_in) ? mb_convert_encoding($appointment->time_in,'UTF-8', 'UTF-8') : '',
            'time_out' => !empty($appointment->time_out) ?mb_convert_encoding($appointment->time_out,'UTF-8', 'UTF-8') : '', 'parent_id' => !empty($appointment->parent_id)?$appointment->parent_id:'',
            'next_appointment'=>$repeats_appointments>0?true:false,'estimate'=>!empty($appointment->estimate) ?mb_convert_encoding($appointment->estimate,'UTF-8', 'UTF-8') : '',
            'customer_link' => (!empty($appointment->customer->customer_link) and ($appointment->customer->customer_link != 'null')) ? mb_convert_encoding($appointment->customer->customer_link,'UTF-8', 'UTF-8') : 'N/A',


        ];


        $color = !empty($appointment->service->color) ? $appointment->service->color : $setting->default_color;
        $border_color = !empty($appointment->service->border_color) ? $appointment->service->border_color : $setting->border_color;

        if ($appointment->current_status == 'C') {
            $color = $setting->completion_color;
        }
        $metting_start_at = date('D M d, h:i', strtotime($appointment->start_time)) . ' - ' . date('h:i a', strtotime($appointment->end_time));
        $appointmentDtl['meeting_length'] = $duration;


        if (!empty($appointment)) {
            $monthly_class="";
            if(!empty($default_mode))
            {
                $monthly_class="monthly_view";
            }
            $event = array(
                'title' => (!empty($appointment->customer->name)) ? $appointment->customer->name : '', 'resourceId' => !empty( $appointment->staff)?$appointment->staff->id:'', 'start' => $start, 'end' => $end, 'color' => $color,'border_color'=>$border_color, 'description' => $serv_name, 'id' => $appointment->id,
                'classNames' => ['myclass' . $appointment->id,$monthly_class], 'appointmentDtl' => $appointmentDtl, 'metting_start_at' => $metting_start_at, 'code' => $appointment->code,
                'appointments'=>$to_be_updated,
                'staff_name' =>!empty($sameAppointmets)?implode(" + ",$sameAppointmets):'',
                'favourite_dtls' => [
                    "is_favourite" =>  !empty($appointment->favourite_dtls) ? $appointment->favourite_dtls->is_favourite : "0"
                ],
            );
        } else {
            $event = [];
        }

        return (mb_convert_encoding($event,'UTF-8', 'UTF-8'));
    }

    public function currentAppointment(Request $request)
    {
        try {
            return response()->json([
                'status' => true,
                'message' => "Appointment has been set successfully",
                'data' => $this->eventDetail($request->id, $request->currentDate)

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }


    function convertToEmoji($matches){
        $newStr = $matches[0];
        $newStr = str_replace("&#", '', $newStr);
        $newStr = str_replace(";", '##', $newStr);
        $myEmoji = explode("##", $newStr);
        $newStr = dechex($myEmoji[0]) . dechex($myEmoji[1]);
        $newStr = hex2bin($newStr);
        return iconv("UTF-16BE", "UTF-8", $newStr);
    }
    public function myFavouriteList(Request $request)
    {
        // try {
        //     $auth_user_id=\Auth::guard('api')->id();
        //     $list = Favourite::with('appoinment_dtls')->where('user_id',$auth_user_id)->where('is_favourite','1')->get();
        //     return response()->json([
        //         'status' => true,
        //         'message' => "Favourite List fetch successfully",
        //         'data' => $list

        //     ], 200);
        // } catch (Exception $ex) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $ex->getMessage(),
        //         'error' => ['error']

        //     ], 200);
        // }
        try {
            $setting =\App\Models\Setting::where('id', 1)->first();
            $start = !empty($request->start) ? $request->start : 0;
            $end = !empty($request->end) ? $request->end : 100;

            //$week_start=Myhelper::weekstart($setting->week_start);
            $week_start = Carbon::MONDAY;
            $start_date = Carbon::now()->startOfWeek($week_start)->format("Y-m-d");
            $end_date = Carbon::parse($start_date)->endOfWeek()->format("Y-m-d");
            $codition[] = ['appointments.is_del', 0];
            //$codition[]=['repeat_appointments.start_time',">=",$start_date.' 00:00:00'];
            //$codition[]=['repeat_appointments.end_time',"<=",$end_date.' 23:59:59'];
            $select = [
                'appointments.id', 'repeat_appointments.start_time', 'repeat_appointments.end_time', 'appointments.staff_id', 'appointments.customer_id', 'appointments.current_status',
                'appointments.estimate', 'appointments.extra_materials', 'appointments.summary', 'appointments.notes', 'appointments.customerservice_id', 'appointments.time_in', 'appointments.time_out','appointments.project_manager'
            ];
            if (!empty($request->customer_id)) {
                $codition[] = ['appointments.customer_id', $request->customer_id];
            }
            $auth_user_id=\Auth::guard('api')->id();
            $totalCount = Appointment::select($select)
            ->whereHas('favourite_dtls' , function ($query) use($auth_user_id) {
                $query->where('user_id', $auth_user_id);
                $query->where('is_favourite', '1');
            })
                ->leftJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')

                ->where($codition)->with(['customer' => function ($query) {
                    $query->select('id', 'name');
                }, 'staff' => function ($query) {
                    $query->select('id', 'name');
                }, 'service' => function ($query) {
                    $query->select('id', 'name');
                },'favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])

                ->get()
                ->count();



            $appointments = Appointment::select($select)
                ->whereHas('favourite_dtls',function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                })
                ->leftJoin('repeat_appointments', 'repeat_appointments.appointment_id', '=', 'appointments.id')
                ->offset($start)
                ->limit($end)->where($codition)->with(['customer' => function ($query) {
                    $query->select('id', 'name', 'mobile', 'address','alternate_name','alternate_mobile','notes','primary_contact_name','customer_link');
                }, 'staff' => function ($query) {
                    $query->select('id', 'name');
                },'favourite_dtls' => function ($query) use($auth_user_id) {
                    $query->where('user_id', $auth_user_id);
                    $query->where('is_favourite', '1');
                }])
                ->orderByDesc('start_time')
                ->get()

                ->map(function ($item, $key) use ($auth_user_id){
                    $end_time = !empty($item->end_time)?Carbon::createFromFormat('Y-m-d H:i:s', $item->end_time):'';

                    $start_time = !empty($item->start_time)?Carbon::createFromFormat('Y-m-d H:i:s', $item->start_time):'';
                    $item->meeting_length =  $end_time?$end_time->diffInMinutes($start_time):0;
                    $item->estimate = (!empty($item->estimate) and $item->estimate != 'null') ? mb_convert_encoding($item->estimate,'UTF-8', 'UTF-8') : '';
                    $item->time_in = !empty($item->time_in) ?mb_convert_encoding($item->time_in,'UTF-8', 'UTF-8') : '';
                    $item->time_out = !empty($item->time_out) ? mb_convert_encoding($item->time_out,'UTF-8', 'UTF-8') : '';
                    $summary=html_entity_decode(htmlentities($item->summary, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');
                    $notes=html_entity_decode(htmlentities($item->notes, ENT_QUOTES, 'UTF-8'), ENT_QUOTES , 'ISO-8859-15');

                    $item->summary=mb_convert_encoding($summary,'UTF-8', 'UTF-8');
                    $item->notes=mb_convert_encoding($notes,'UTF-8', 'UTF-8');

                    $cond[]=[DB::raw('DATE(start_time)'), '=', date('Y-m-d', strtotime($item->start_time))];
                    $sameAppointmets=[];
                    if(!empty($item->customer_id))
                    {
                        $cond[]=['customer_id',$item->customer_id];
                        $cond[]=['is_del',0];
                        $newsameAppointments=Appointment::select('staff_id')->whereHas('favourite_dtls',function ($query) use($auth_user_id) {
                            $query->where('user_id', $auth_user_id);
                            $query->where('is_favourite', '1');
                        })->with(['staff' => function ($query) use($auth_user_id){$query->select('id', 'name');},'favourite_dtls' => function ($query1) use($auth_user_id) {
                            $query1->where('user_id', $auth_user_id);
                            $query1->where('is_favourite', '1');
                        }])
                        ->where($cond)->groupBy('staff_id')->get();
                        foreach( $newsameAppointments as  $sameAppointment)
                        {
                            if(\Auth::guard('api')->id()!=$sameAppointment->staff->id and \Auth::guard('api')->id()>1 and count($newsameAppointments)>1)
                            {
                                $sameAppointmets[]='<span class="text-danger" style="color:#f00;">'.$sameAppointment->staff->name.'</span>';
                            }
                            else{
                                $sameAppointmets[]='<span>'.$sameAppointment->staff->name.'</span>';

                            }

                        }
                    }
                    else{
                        $sameAppointmets[]='<span>'.$item->staff->name.'</span>';
                    }
                    $item->all_staff_name=implode(" + ",$sameAppointmets);



                    return $item;
                });
            $response = ['appointments' => !empty($appointments) ? $appointments : array(), 'week_start' => $start_date, 'week_end' => $end_date, 'totalCount' => $totalCount];
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
    public function myFavouriteAdd(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'appoinment_id' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $auth_user_id=\Auth::guard('api')->id();
            $details = Favourite::where('appoinment_id',$request->appoinment_id)->where('user_id',$auth_user_id)->first();

            if($details)
            {
                if($details->is_favourite == "0")
                {
                    $fav_data = [
                        'is_favourite' => "1"
                    ];
                    $msg = 'Added to favourites';
                }
                else
                {
                    $fav_data = [
                        'is_favourite' => "0"
                    ];
                    $msg = 'Removed from favourites';
                }

            }
            else
            {
                $fav_data = [
                    'is_favourite' => "1"
                ];
                $msg = 'Added to favourites';
            }

            $matchThese = ['appoinment_id'=>$request->appoinment_id,'user_id'=>$auth_user_id];
            Favourite::updateOrCreate($matchThese,$fav_data);
            return response()->json([
                'status' => true,
                'message' => $msg,
                'data' => $details

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }


    public function fillMissingDates($events, $year, $month)
{
    $startDate = Carbon::createFromDate($year, $month, 1);
    $endDate = $startDate->copy()->endOfMonth();

    // Create an array of all days in the month
    $allDates = [];
    for ($date = $startDate->copy(); $date->lessThanOrEqualTo($endDate); $date->addDay()) {
        $allDates[] = $date->format('Y-m-d');
    }

    // Extract existing event dates
    $existingEventDates = [];
    foreach ($events as $event) {
        $existingEventDates[] = Carbon::parse($event['start'])->format('Y-m-d');
    }

    // Prepare the array for default events
    $defaultEvents = [];
    foreach ($allDates as $date) {
        if (!in_array($date, $existingEventDates)) {
            $defaultEvents[] = [
                'title' => '',
                'resourceId' => null, // Set appropriate resource ID if needed
                'start' => $date . 'T08:30:00',
                'end' => $date . 'T09:00:00',
                'color' => '',
                'border_color' => '',
                'description' => '',
                'id' => null, // Generate or set appropriate ID
                'classNames' => ['noEvent'],
                'appointmentDtl'=>null
            ];
        }
    }

    // Merge existing events with default events
    return array_merge($events, $defaultEvents);
}

}
