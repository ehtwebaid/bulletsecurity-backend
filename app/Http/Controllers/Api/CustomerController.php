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
use App\Helpers\Myhelper;
use App\Models\Customer;
use App\Models\Appointment;
use App\Imports\ImportCustomers;
use DB;

class CustomerController extends Controller
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

    public function listCustomers(Request $request)
    {
        try {
            $cond[] = ['is_del', 0];
            if (!empty($request->id) and is_numeric($request->id)) {
                $cond[] = ['id', $request->id];
            }
            if (!empty($request->name)) {
                $cond[] = ['name', 'LIKE', '%' . $request->name . '%'];
            }
            if (empty($request->page_no)) {
                $request->page_no = 0;
            }
            if (empty($request->itemsPerPage)) {
                $request->itemsPerPage = 15;
            }
            $cutomers = Customer::where($cond)->orderBy('name', 'asc')->offset($request->page_no)->limit($request->itemsPerPage)->get()->map(function ($item, $key) {
                $item->name = is_null($item->name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->name);
                $item->primary_contact_name = is_null($item->primary_contact_name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->primary_contact_name);
                $item->mobile = is_null($item->mobile) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->mobile);
                $item->alternate_name = is_null($item->alternate_name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_name);
                $item->alternate_mobile = is_null($item->alternate_mobile) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_mobile);
                $item->address = is_null($item->address) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->address);
                $item->city = is_null($item->city) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->city);
                $item->province = is_null($item->province) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->province);
                $item->postal_code = is_null($item->postal_code) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->postal_code);
                $item->customer_link = is_null($item->customer_link) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->customer_link);

                return $item;
            });
            $rowsTotal = Customer::where($cond)->count();
            $result_array = ['status' => true, 'data' => !empty($cutomers) ? $cutomers : array()];
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

    public function allCustomers(Request $request)
    {
        try {
            $cond[] = ['is_del', 0];
            if (!empty($request->id) and is_numeric($request->id)) {
                $cond[] = ['id', $request->id];
            }
            if (!empty($request->name)) {
                $cond[] = ['name', 'LIKE', '%' . $request->name . '%'];
            }

            $cutomers = Customer::where($cond)->orderBy('name', 'asc')->get()->map(function ($item, $key) {
                $item->name = is_null($item->name) ? '' : $item->name;
                $item->primary_contact_name = is_null($item->primary_contact_name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->primary_contact_name);
                $item->mobile = is_null($item->mobile) ? '' : $item->mobile;
                $item->alternate_name = is_null($item->alternate_name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_name);
                $item->alternate_mobile = is_null($item->alternate_mobile) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_mobile);
                $item->address = is_null($item->address) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->address);
                $item->city = is_null($item->city) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->city);
                $item->province = is_null($item->province) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->province);
                $item->postal_code = is_null($item->postal_code) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->postal_code);
                $item->customer_link = is_null($item->customer_link) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->customer_link);
                return $item;
            });
            $rowsTotal = Customer::where($cond)->count();
            $result_array = ['status' => true, 'data' => !empty($cutomers) ? $cutomers : array()];
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


    public function dailyCustomers(Request $request)
    {
        try {
            $cond[] = ['is_del', 0];
            if (!empty($request->name)) {
                $cond[] = ['name', 'LIKE', '%' . $request->name . '%'];
            }
            if (!empty($request->id) and is_numeric($request->id)) {
                $cond[] = ['id', $request->id];
            }
            if (!empty($request->offset)) {
                $request->offset = 0;
            }
            if (!empty($request->limit)) {
                $request->limit = 10;
            }
            $cutomers = Customer::where($cond)->select(['id', 'name', 'primary_contact_name', 'mobile', 'alternate_name', 'alternate_mobile', 'address', 'notes','customer_link'])->orderBy('name', 'asc')->offset($request->offset)->limit($request->limit)->get()->map(function ($item, $key) {
                $item->c_id = $item->id;
                $item->name = is_null($item->name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->name);
                $item->primary_contact_name = (!empty($item->primary_contact_name) and $item->primary_contact_name!='null') ? Myhelper::convert_from_latin1_to_utf8_recursively($item->primary_contact_name) :'' ;
                $item->mobile = is_null($item->mobile) ? '' : $item->mobile;
                $item->alternate_name = is_null($item->alternate_name) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_name);
                $item->alternate_mobile = is_null($item->alternate_mobile) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->alternate_mobile);
                $item->address = is_null($item->address) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->address);
                $item->customer_notes = is_null($item->notes) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->notes);
                $item->customer_link = is_null($item->customer_link) ? '' : Myhelper::convert_from_latin1_to_utf8_recursively($item->customer_link);

                unset($item->notes);
                unset($item->id);
                return $item;
            });
            $result_array = ['status' => true, 'data' => !empty($cutomers) ? $cutomers : array()];
            return response()->json([
                'message' => "",
                'status' => true,
                'data' => Myhelper::convert_from_latin1_to_utf8_recursively($result_array),
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


    public function addCustomer(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [

                /* 'name' => 'regex:/^[\pL\s\-]+$/u|max:255',
            'mobile' => 'nullable|digits:10', */

            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            if (!empty($request->id)) {
                $cutomer = Customer::find($request->id);
            } else {
                $cutomer = new Customer();
            }
            $cutomer->name = !is_null($request->name)?$request->name:$request->primary_contact_name;
            $cutomer->mobile = $request->mobile;
            $cutomer->primary_contact_name = $request->primary_contact_name;
            $cutomer->alternate_name = $request->alternate_name;
            $cutomer->alternate_mobile = $request->alternate_mobile;
            $cutomer->address = $request->address;
            $cutomer->city = $request->city;
            $cutomer->province = $request->province;
            $cutomer->postal_code = $request->postal_code;
            $cutomer->notes = $request->notes;
            $cutomer->customer_link = !empty($request->customer_link)?$request->customer_link:'';

            $cutomer->save();
            return response()->json([
                'status' => true,
                'message' => "Customer has been saved successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }

    public function deleteCustomer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $request->ids = explode(",", $request->ids);
            foreach ($request->ids as $id) {
                $cutomer = Customer::find($id);
                $cutomer->is_del = 1;
                $cutomer->save();

            }
            return response()->json([
                'status' => true,
                'message' => "Customer has been Deleted successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function editNote(Request $request)
    {
        try {


            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'notes' => 'required',

            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $cutomer = Customer::find($request->id);

            $cutomer->notes = $request->notes;

            $cutomer->save();
            return response()->json([
                'status' => true,
                'message' => "Customer has been saved successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function importCustomer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'import_file' => 'required',

        ]);
        if ($validator->fails()) {
            $errors = Myhelper::customerrors($validator->errors());
            return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
        }
        config(['excel.import.startRow' => 1]);
        Excel::import(new ImportCustomers, request()->file('import_file'));

        return response()->json([
            'status' => true,
            'message' => "Imported successfully",

        ], 200);
    }
    public function submitMerge(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
                'primary_contact_name' => 'required'
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $request->ids = explode(",", $request->ids);
            $cutomer = Customer::find($request->ids[0]);
            $cutomer->name = $request->name;
            $cutomer->primary_contact_name = $request->primary_contact_name;
            $cutomer->mobile = $request->mobile;
            $cutomer->alternate_name = $request->alternate_name;
            $cutomer->alternate_mobile = $request->alternate_mobile;
            $cutomer->address = $request->address;
            $cutomer->save();
            foreach ($request->ids as $key => $id) {
                if ($key > 0) {

                    \App\Models\Appointment::where('customer_id', $id)
                        ->update([
                            'customer_id' => $request->ids[0]
                        ]);

                    Customer::where('id', $id)
                        ->update([
                            'is_del' => 1
                        ]);
                }
            }
            return response()->json([
                'status' => true,
                'message' => "Merged successfully",

            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error']

            ], 200);
        }
    }
    public function mergeCustomer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required',
            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());
                return response()->json(['status' => false, 'error' => $errors, 'data' => [], "code" => 200], 200);
            }
            $request->ids = explode(",", $request->ids);
            foreach ($request->ids as $id) {
                $cutomer = Customer::where('id', $id)->select(
                    "id",
                    "name",
                    "mobile",
                    "primary_contact_name",
                    "alternate_name",
                    "alternate_mobile",
                    "address"
                )->first();
                if (!empty($cutomer->name) and $cutomer->name != 'null') {
                    $names[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->name);
                }
                if (!empty($cutomer->mobile) and $cutomer->mobile != 'null') {
                    $mobiles[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->mobile);
                }
                if (!empty($cutomer->primary_contact_name) and $cutomer->primary_contact_name != 'null') {
                    $primary_contacts[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->primary_contact_name);
                }
                if (!empty($cutomer->alternate_name) and $cutomer->alternate_name != 'null') {
                    $alternate_names[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->alternate_name);
                }
                if (!empty($cutomer->alternate_mobile) and $cutomer->alternate_mobile != 'null') {
                    $alternate_mobiles[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->alternate_mobile);
                }
                if (!empty($cutomer->address) and $cutomer->address != 'null') {
                    $addresses[] = Myhelper::convert_from_latin1_to_utf8_recursively($cutomer->address);
                }
            }
            $result_array = [
                'names' => !empty($names) ? $names : array(),
                'mobiles' => !empty($mobiles) ? $mobiles : array(),
                'primary_contacts' => !empty($primary_contacts) ? $primary_contacts : array(),
                'alternate_names' => !empty($alternate_names) ? $alternate_names : array(),
                'alternate_mobiles' => !empty($alternate_mobiles) ? $alternate_mobiles : array(),
                'addresses' => !empty($addresses) ? $addresses : array()
            ];
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
}
