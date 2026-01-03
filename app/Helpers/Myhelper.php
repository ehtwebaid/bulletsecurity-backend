<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\RepeatAppointment;
use App\Models\Appointment;
use DB;
class Myhelper
{
    public static function uploaded_asset($image_name)
{
    $site_link = getenv('APP_URL');
    return $site_link.'uploads/'.$image_name;

}
public static function customerrors($errors) {
        $errors = implode(',',$errors->all());
        $errorconverttoArray = explode(',',$errors);
        return $errorconverttoArray;
    }
public static function weekstart($week) {
    if($week==1)
        {
            $week_start=Carbon::SUNDAY;
        }
        if($week==2)
        {
            $week_start=Carbon::MONDAY;
        }
        if($week==3)
        {
            $week_start=Carbon::TUESDAY;
        }
        if($week==4)
        {
            $week_start=Carbon::WEDNESDAY;
        }
        if($week==5)
        {
            $week_start=Carbon::THURSDAY;
        }
        if($week==6)
        {
            $week_start=Carbon::FRIDAY;
        }
        if($week==7)
        {
            $week_start=Carbon::SATURDAY;
        }

        return $week_start;
    }
    public static function repeatDates($appointment_id,$id=0,$ref_id=0)
    {
        $day_names=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $working_days=['Monday','Tuesday','Wednesday','Thursday','Friday'];
        if(!empty($ref_id))
        {
            $appointment=\App\Models\Appointment::find($ref_id);
            $staff_id=$appointment->staff_id;
            $no_term=$appointment->no_term;
            $custom_type=$appointment->custom_type;
            $custom_duration=$appointment->custom_duration;
            $is_repeat=$appointment->is_repeat;
        }
        else{
            $appointment=\App\Models\Appointment::find($appointment_id);

        }
        if(empty($id))
        {
            $new_appointment=DB::table("repeat_appointments")->where('parent_id',$appointment_id)->whereNotNull('start_time')->orderBy('id','desc')->first();

        }
        else{

            $new_appointment = RepeatAppointment::where('parent_id', $ref_id)
            ->where('id', '<', $id)
            ->orderBy('id', 'desc') // get the closest smaller ID
            ->first();
        }
        $current_date=date('Y-m-d H:i:s');
        $day_name= Carbon::parse($current_date)->format('l');
        $start_time =$new_appointment->start_time;
        $end_time =$new_appointment->end_time;
        $last_dayname=Carbon::parse($new_appointment->start_time)->format('l');
        if($appointment->is_repeat=='D')
        {

            if($last_dayname=='Friday')
            {
                $start_time = Carbon::parse($start_time)->addDays(3)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($end_time)->addDays(3)->format('Y-m-d H:i:s');
            }
            else{
                $start_time = Carbon::parse($start_time)->addDays(1)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($end_time)->addDays(1)->format('Y-m-d H:i:s');
            }
                if(empty($id))
                {
                    $last_appointment = new RepeatAppointment();
                    $last_new_appointment = Appointment::find($appointment->id);
                    $newAppointment = $last_new_appointment->replicate();
                }
                else{
                    $last_appointment =RepeatAppointment::find($id);
                    $newAppointment = Appointment::where(['id'=>$last_appointment->appointment_id])->first();
                    $newAppointment->staff_id=$staff_id;
                    $newAppointment->no_term=$no_term;
                    $newAppointment->custom_type=$custom_type;
                    $newAppointment->custom_duration=$custom_duration;
                    $newAppointment->is_repeat=$is_repeat;


                }

                $newAppointment->start_time=$start_time;
                $newAppointment->end_time=$end_time;
                $newAppointment->parent_id=$appointment->id;
                $newAppointment->save();
                $last_appointment->appointment_id = $newAppointment->id; // the new project_id
                $last_appointment->start_time=$start_time;
                $last_appointment->end_time=$end_time;
                $last_appointment->parent_id=$appointment_id;
                $last_appointment->save();


        }
        else if(in_array($appointment->is_repeat, $day_names))
        {

                $start_time = Carbon::parse($start_time)->addDays(7)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($end_time)->addDays(7)->format('Y-m-d H:i:s');
                if(empty($id))
                {
                    $last_appointment = new RepeatAppointment();
                    $last_new_appointment = Appointment::find($appointment->id);
                    $newAppointment = $last_new_appointment->replicate();
                    $newAppointment->parent_id=$appointment->id;

                }
                else{
                    $last_appointment =RepeatAppointment::find($id);
                    $newAppointment = Appointment::where(['id'=>$last_appointment->appointment_id])->first();
                    $newAppointment->staff_id=$staff_id;
                    $newAppointment->no_term=$no_term;
                    $newAppointment->custom_type=$custom_type;
                    $newAppointment->custom_duration=$custom_duration;
                    $newAppointment->is_repeat=$is_repeat;

                }

                $newAppointment->start_time=$start_time;
                $newAppointment->end_time=$end_time;
                $newAppointment->save();

                $last_appointment->appointment_id = $newAppointment->id; // the new project_id
                $last_appointment->start_time=$start_time;
                $last_appointment->end_time=$end_time;
                $last_appointment->parent_id=$appointment_id;
                $last_appointment->save();
        }
        else if($appointment->is_repeat>=1 and $appointment->is_repeat<=31)
        {

                $start_time = Carbon::parse($start_time)->addMonths(1)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($end_time)->addMonths(1)->format('Y-m-d H:i:s');
                if(empty($id))
                {
                    $last_appointment = new RepeatAppointment();
                    $last_new_appointment = Appointment::find($appointment->id);
                    $newAppointment = $last_new_appointment->replicate();
                    $newAppointment->parent_id=$appointment->id;

                }
                else{
                    $last_appointment =RepeatAppointment::find($id);
                    $newAppointment = Appointment::where(['id'=>$last_appointment->appointment_id])->first();
                    $newAppointment->staff_id=$staff_id;
                    $newAppointment->no_term=$no_term;
                    $newAppointment->custom_type=$custom_type;
                    $newAppointment->custom_duration=$custom_duration;
                    $newAppointment->is_repeat=$is_repeat;
                }

                $newAppointment->start_time=$start_time;
                $newAppointment->end_time=$end_time;
                $newAppointment->save();

                $last_appointment->appointment_id = $newAppointment->id; // the new project_id
                $last_appointment->start_time=$start_time;
                $last_appointment->end_time=$end_time;
                $last_appointment->parent_id=$appointment_id;
                $last_appointment->save();
        }


        else if($appointment->is_repeat=='C')
        {
            if(!empty($new_appointment))
        {
            if($appointment->custom_type=='D')
                {
                $start_time = Carbon::parse($new_appointment->start_time)->addDays($appointment->custom_duration)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($new_appointment->end_time)->addDays($appointment->custom_duration)->format('Y-m-d H:i:s');
                }
                else if($appointment->custom_type=='W')
                {
                $start_time = Carbon::parse($new_appointment->start_time)->addWeeks($appointment->custom_duration)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($new_appointment->end_time)->addWeeks($appointment->custom_duration)->format('Y-m-d H:i:s');
                }
                else if($appointment->custom_type=='M')
                {
                $start_time = Carbon::parse($new_appointment->start_time)->addMonths($appointment->custom_duration)->format('Y-m-d H:i:s');
                $end_time = Carbon::parse($new_appointment->end_time)->addMonths($appointment->custom_duration)->format('Y-m-d H:i:s');
                }

        }
        else{
        if($appointment->custom_type=='D')
            {
            $start_time = Carbon::parse($appointment->start_time)->addDays($appointment->custom_duration)->format('Y-m-d H:i:s');
            $end_time = Carbon::parse($appointment->end_time)->addDays($appointment->custom_duration)->format('Y-m-d H:i:s');
            }
            else if($appointment->custom_type=='W')
            {
            $start_time = Carbon::parse($appointment->start_time)->addWeeks($appointment->custom_duration)->format('Y-m-d H:i:s');
            $end_time = Carbon::parse($appointment->end_time)->addWeeks($appointment->custom_duration)->format('Y-m-d H:i:s');
            }
            else if($appointment->custom_type=='M')
            {
            $start_time = Carbon::parse($appointment->start_time)->addMonths($appointment->custom_duration)->format('Y-m-d H:i:s');
            $end_time = Carbon::parse($appointment->end_time)->addMonths($appointment->custom_duration)->format('Y-m-d H:i:s');
            }

        }
        // replicate (duplicate) the data
        if(empty($id))
        {
            $last_appointment = new RepeatAppointment();
            $last_new_appointment = Appointment::find($appointment->id);
            $newAppointment = $last_new_appointment->replicate();
            $newAppointment->parent_id=$appointment->id;

        }
        else{
            $last_appointment =RepeatAppointment::find($id);
            $newAppointment = Appointment::where(['id'=>$last_appointment->appointment_id])->first();
            $newAppointment->staff_id=$staff_id;
            $newAppointment->no_term=$no_term;
            $newAppointment->custom_type=$custom_type;
            $newAppointment->custom_duration=$custom_duration;
            $newAppointment->is_repeat=$is_repeat;

        }

        $newAppointment->start_time=$start_time;
        $newAppointment->end_time=$end_time;
        $newAppointment->save();

        $last_appointment->appointment_id = $newAppointment->id; // the new project_id
        $last_appointment->start_time=$start_time;
        $last_appointment->end_time=$end_time;
        $last_appointment->parent_id=$appointment_id;
        $last_appointment->save();

        // make into array for mass assign.
        }


}
public static function updaterepeatDate($appointment_id,$start_time,$end_time)
{
    $last_appointment = RepeatAppointment::find(['appointment_id'=>$appointment_id])->first();
    $last_new_appointment = Appointment::find($appointment_id);
    $last_appointment->start_time=$start_time;
    $last_appointment->end_time=$end_time;
    $last_new_appointment->start_time=$start_time;
    $last_new_appointment->end_time=$end_time;
    $last_appointment->save();
    $last_new_appointment->save();

}
public static function getDuration($id)
{
    $appointment=\App\Models\Appointment::select('start_time','end_time')->where('id',$id)->first();
    $to = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->end_time);
    $from = Carbon::createFromFormat('Y-m-d H:i:s', $appointment->start_time);
    $meeting_length=$to->diffInMinutes($from);
    return $meeting_length;
}
public static function convert_from_latin1_to_utf8_recursively($dat)
{
   if (is_string($dat)) {

      return utf8_encode($dat);
   } elseif (is_array($dat)) {

      $ret = [];
      foreach ($dat as $i => $d) {
        $ret[ $i ] = self::convert_from_latin1_to_utf8_recursively($d);
      }

      return $ret;
   } elseif (is_object($dat)) {

      foreach ($dat as $i => $d) $dat->$i = self::convert_from_latin1_to_utf8_recursively($d);

      return $dat;
   } else {
      return $dat;
   }
}
public static function updateBasicInfo($ref_id,$id)
{
  $select_field=['staff_id','customer_id','is_repeat','notes','job_completion',
  'extra_materials','summary','price','customerservice_id','current_status',
  'no_term','custom_type','custom_duration','estimate','project_manager','time_in','time_out'];
  $appointment=Appointment::where(['id'=>$ref_id])->select($select_field)->first();
  DB::table('appointments')
  ->where('id', $id)
  ->update($appointment->toArray());

}

}
