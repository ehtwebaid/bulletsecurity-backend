<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Myhelper;
use App\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mail;
use Validator;

class AuthController extends Controller
{
    //
    public function __construct() {}

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'error' => $validator->errors(),
                    'message' => 'Validation error',
                ], 200);
            }

            $user = User::where([
                'email' => $request->email,
                'is_del' => 0,
            ])->first();

            if (! $user || ! Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'User name or Password not matched',
                    'data' => (object) [],
                ], 200);
            }

            if ($user->current_status !== 'A') {
                return response()->json([
                    'status' => false,
                    'message' => 'Your Account is not activated',
                    'data' => (object) [],
                ], 200);
            }

            $user->profile_photo = Myhelper::uploaded_asset($user->profile_photo);

            // Laravel 11 Token
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Logged in Successfully',
                'data' => [
                    'user' => $user,
                    'token' => 'Bearer '.$token,
                ],
            ], 200);

        } catch (\Throwable $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
            ], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|exists:users,email',
        ]);
        if ($validator->fails()) {
            $errors = Myhelper::customerrors($validator->errors());

            return response()->json(['status' => false, 'error' => $errors, 'data' => [], 'code' => 200], 200);
        }
        $email = $request->email;
        $user = \App\Models\User::select('id', 'email')->where('email', $email)->where('is_del', 0)->first();

        Mail::send('emails.forgot', ['staff_id' => $user->id, 'staff_name' => $user->name], function ($message) use ($email) {
            $message->to($email)->subject('Forgot Password:-bulletsecurity.net');
            $message->from(getenv('MAIL_FROM_ADDRESS'));
        });

        return response()->json(['status' => true, 'error' => [], 'message' => $message, 'data' => []]);

    }

    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:6|confirmed',
                'password_confirmation' => 'required|min:6|string',

            ]);
            if ($validator->fails()) {
                $errors = Myhelper::customerrors($validator->errors());

                return response()->json(['status' => false, 'error' => $errors, 'message' => 'error', 'code' => 200], 200);
            }
            $userdata = Auth::user();
            $user = \App\Models\User::find($userdata->id);
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json(['status' => true, 'message' => 'Password has been changed successfully', 'error' => [], 'data' => []]);

        } catch (\Exception $ex) {
            return response()->json(['status' => false, 'message' => $ex->getMessage(), 'error' => [], 'data' => []]);
        }

    }

    public function logOut()
    {
        $user = Auth::user()->token();
        $user->revoke();

        return response()->json(['status' => true, 'message' => 'Logout Successfully', 'error' => [], 'data' => []]);

    }

    public function fileUpload(Request $request)
    {
        try {
            $image = $request->file('file');
            $imageName = Carbon::now()->timestamp.'.'.$image->getClientOriginalExtension();
            $image->move(public_path('uploads'), $imageName);
            $SITE_URL = getenv('APP_URL');

            return response()->json([
                'status' => true,
                'url' => $SITE_URL.'public/uploads/'.$imageName,

            ], 200);

        } catch (Exception $ex) {
            return response()->json([
                'status' => false,
                'message' => $ex->getMessage(),
                'error' => ['error'],

            ], 200);
        }
    }
}
