<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Mail\OTPMail;
use App\Helper\JWTToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function UserRegistration(Request $request){
        try{
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'password' => 'required',
                'mobile' => 'required',
            ]);

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
                'mobile' => $request->input('mobile'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user
            ]);
        }catch(Exception $e){
            return response()->json([
                'status' => 'fail',
                'message' => $e->getMessage()
            ]);
        }
    }//end method

    public function UserLogin(Request $request){
        $count = User::where('email', $request->input('email'))->where('password', $request->input('password'))->select('id')->first();

        if($count !== null){
            // User login -> JWT token issue
            $token = JWTToken::CreateToken($request->input('email'),$count->id);

            return response()->json([
                'status' => 'success',
                'message' => 'User login successfully',
                'token' => $token
            ],200)->cookie('token', $token, 60 * 24 * 30);
        }else{
            return response()->json([
                'status' => 'failed',
                'message' => 'unauthorized'
            ],200);
        }

    }//end method


    public function DashboardPage(Request $request){
        $user = $request->header('email');
        return response()->json([
            'status' => 'success',
            'message' => 'User login successfully',
            'user' => $user
        ],200);
    }//end method

    public function UserLogout(Request $request){
        return response()->json([
            'status' => 'success',
            'message' => 'User logout successfully',
        ],200)->cookie('token', '', -1);
    }//end method

    public function SendOTPCode(Request $request){
        $email = $request->input('email');
        $otp = rand(1000,9999);

        $count = User::where('email',$email)->count();

        if($count == 1){
            Mail::to($email)->send(new OTPMail($otp));
            User::where('email', $email)->update(['otp' => $otp]);
            return response()->json([
                'status' => 'success',
                'message' => "4 Digit {$otp} OTP send successfully",
            ],200);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'unauthorized'
            ]);
        }
    }//end method

    public function VerifyOTP(Request $request){
        $email = $request->input('email');
        $otp = $request->input('otp');

        $count = User::where('email', $email)->where('otp', $otp)->count();

        if($count == 1){
            User::where('email', $email)->update(['otp' => 0]);
            $token = JWTToken::CreateTokenForSetPassword($request->input('email'));


            return response()->json([
                'status' => 'success',
                'message' => 'OTP verification successfully',
            ],200)->cookie('token', $token, 60 * 24 * 30);
        }else{
            return response()->json([
                'status' => 'fail',
                'message' => 'unauthorized'
            ]);
        }
    }//end method

    public function ResetPassword(Request $request){
        try{
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email', $email)->update(['password' => $password]);
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset successfully',
            ],200);

        }catch(Exception $e){
            return response()->json([
                'status' => 'fail',
                'message' => 'somthing went wrong'
            ]);
        }
    }//end method
}