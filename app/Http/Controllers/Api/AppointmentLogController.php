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
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\AppointmentLog;
use App\Models\User;
use Storage;
use DB;
class AppointmentLogController extends Controller
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

    public function listLog(Request $request)
    {
        try {
            if (empty($request->page_no)) {
                $request->page_no = 0;
            }
            if (empty($request->itemsPerPage)) {
                $request->itemsPerPage = 150;
            }
            $customerId = !empty($request->customer_id)?$request->customer_id:0; // Assuming customer_id comes from a request
            $query = AppointmentLog
                ::with('staff')
                ->leftJoin('appointments', 'appointment_logs.appointment_id', '=', 'appointments.id')
                ->leftJoin('customers', 'appointments.customer_id', '=', 'customers.id')
                ->select('appointment_logs.*','appointments.code',"customers.name")
                ->orderBy('appointment_logs.id', 'desc')
                ->groupBy('appointment_logs.appointment_id')
                ;

            // Apply customer_id filter if provided
            if ($customerId) {
                $query->where('appointments.customer_id', $customerId);
            }
            $rowsTotal =$query->count();
            $logs=$query->offset($request->page_no)->limit($request->itemsPerPage)->get();



            $result_array = ['status' => true, 'data' => !empty($logs) ? $logs : array()];
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => $result_array,
                'rowsTotal' => (int)$rowsTotal,
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


    public function viewLog(Request $request)
    {
        try {

            $customerId = (!empty($request->customer_id) and $request->customer_id!='null')?$request->customer_id:0; // Assuming customer_id comes from a request
            $staffId = (!empty($request->staff_id) and $request->staff_id!='null')?$request->staff_id:0; // Assuming customer_id comes from a request
            $code = (!empty($request->code) and $request->code!='null')?$request->code:""; // Assuming customer_id comes from a request

            if (empty($request->page_no)) {
                $request->page_no = 0;
            }
            if (empty($request->itemsPerPage)) {
                $request->itemsPerPage = 150;
            }
            $headers=['staff'=>'Staff','customer'=>'Customer','start_time'=>'Start Time','end_time'=>'End Time',

          'current_status'=>'Job Complete','notes'=>'Install Notes','extra_materials'=>'Extra Materials','summary'=>'Summary',
          'estimate'=>'Estimate','project_manager'=>'Project Manager','time_in'=>'Time In','time_out'=>'Time Out','repeat'=>'Repeat'
];
            $after=[];
            $before=[];
            $query =AppointmentLog::with('staff')
            ->leftJoin('appointments', 'appointment_logs.appointment_id', '=', 'appointments.id')
            ->leftJoin('customers', 'appointments.customer_id', '=', 'customers.id')
            ->orderBy('appointment_logs.id', 'desc')
            ->select('appointment_logs.*','appointments.code',"customers.name",DB::raw("customers.id as `customer_id`"))
            ->groupBy('appointment_logs.appointment_id','appointment_logs.id');
            if ($customerId) {
                $query->where('appointments.customer_id', $customerId);
            }
            if ($staffId) {
                $query->where('appointment_logs.user_id', $staffId);
            }
            if ($code) {
                $query->where('appointments.code','LIKE', '%'.$code.'%');
            }
            if (!empty($request->start_date) and !empty($request->end_date)) {
                $start_date = date('Y-m-d',strtotime($request->start_date));
                $end_date =  date('Y-m-d',strtotime($request->end_date));
                $query->whereDate('appointment_logs.created_at', '>=', $request->start_date);
                $query->whereDate('appointment_logs.created_at', '<=', $request->end_date);
            }
            $rowsTotal = $query->getQuery()->getConnection()
            ->table(DB::raw("({$query->toSql()}) as subquery"))
            ->mergeBindings($query->getQuery())
            ->count();
            $logs=$query->offset($request->page_no)->limit($request->itemsPerPage)->get()
            ->map(function ($item, $key) use ($headers,$after,$before){
                $changesArray = $item->changes;
                // Check if 'before' contains 'staff_id'
                if (!empty($changesArray['before']['staff_id'])) {
                    // Add 'staff' to the 'before' array
                    $changesArray['before']['staff'] =User::where('id',$changesArray['before']['staff_id'])->pluck('name')->first() ;// Static value or dynamic data
                }
                if (!empty($changesArray['after']['staff_id'])) {
                    // Add 'staff' to the 'before' array
                    $changesArray['after']['staff'] =User::where('id',$changesArray['after']['staff_id'])->pluck('name')->first() ;// Static value or dynamic data
                }
                if (!empty($changesArray['before']['customer_id'])) {
                    // Add 'staff' to the 'before' array
                    $changesArray['before']['customer'] =Customer::where('id',$changesArray['before']['customer_id'])->pluck('name')->first() ;// Static value or dynamic data
                }
                if (!empty($changesArray['after']['customer_id'])) {
                    // Add 'staff' to the 'before' array
                    $changesArray['after']['customer'] =Customer::where('id',$changesArray['after']['customer_id'])->pluck('name')->first() ;// Static value or dynamic data
                    if(empty($changesArray['before']['customer']) && $item->action!='Add')
                    {
                        $changesArray['before']['customer']="N/A";
                    }
                }
                if (!empty($changesArray['before']['is_repeat']))
                {
                    if($changesArray['before']['is_repeat']=='N')
                    {
                        $changesArray['before']['repeat']="No";
                    }
                    else if($changesArray['before']['is_repeat']=='D')
                    {
                        $changesArray['before']['repeat']="Every Working Day for ".$changesArray['before']['no_term']." term(s) ";
                    }
                    else if($changesArray['before']['is_repeat']=='C')
                    {
                        if($changesArray['before']['custom_type']=='D')
                        {
                        $mode="Day";
                        }
                        else if($changesArray['before']['custom_type']=='W')
                        {
                        $mode="Week";
                        }
                        else if($changesArray['before']['custom_type']=='M')
                        {
                        $mode="Month";
                        }
                        $changesArray['before']['repeat']="After Every ".$changesArray['before']['custom_duration']." ".$mode." for ".$changesArray['before']['no_term']."  term(s) ";
                    }
                }


                if (!empty($changesArray['after']['is_repeat'])) {
                    if($changesArray['after']['is_repeat']=='N')
                    {
                     $changesArray['after']['repeat']="No";
                    }
                    else if($changesArray['after']['is_repeat']=='D')
                    {
                     $changesArray['after']['repeat']="Every Working Day for ".$changesArray['after']['no_term']." term(s) ";
                    }
                    else if($changesArray['after']['is_repeat']=='C')
                    {
                     if($changesArray['after']['custom_type']=='D')
                     {
                        $mode="Day";
                     }
                    else if($changesArray['after']['custom_type']=='W')
                     {
                        $mode="Week";
                     }
                     else if($changesArray['after']['custom_type']=='M')
                     {
                        $mode="Month";
                     }
                     $changesArray['after']['repeat']="After Every ".$changesArray['after']['custom_duration']." ".$mode." for ".$changesArray['after']['no_term']."  term(s) ";
                    }
                 }

                 if (!empty($changesArray['after']['current_status'])) {

                    $changesArray['after']['current_status']= $changesArray['after']['current_status']=='P'?'Pending':'Complete';

                 }
                 if (!empty($changesArray['before']['current_status'])) {

                    $changesArray['before']['current_status']= $changesArray['before']['current_status']=='P'?'Pending':'Complete';

                 }



                // Assign the modified changes back to the model
                $item->changes = $changesArray;


                if(!empty($changesArray['before']))
                {

                    foreach($changesArray['before'] as $key => $value)
                    {
                        if(array_key_exists($key,$headers) )
                        {
                            $label=$headers[$key];
                            $before[]=['label'=>$label,'value'=>$value];

                        }

                    }
                    $item->before = $before;
                }

                if(!empty($changesArray['after']))
                {

                    foreach($changesArray['after'] as $key => $value)
                    {
                        if(array_key_exists($key,$headers) and !empty($value))
                        {
                            $label=$headers[$key];
                            $after[]=['label'=>$label,'value'=>$value];

                        }

                    }
                    $item->after = $after;
                }
                unset($item->changes);
                return $item;

            });



            $result_array = ['status' => true, 'data' => !empty($logs) ? $logs : array()];
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => $result_array,
                'rowsTotal' => (int)$rowsTotal,
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
