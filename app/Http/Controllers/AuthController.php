<?php

namespace App\Http\Controllers;

use App\Mail\ResetPassword;
use App\Models\OauthAccessTokens;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User as ModelUser;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use App\ResetPassword as ModelResetPassword;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        if(Auth::check())
        {
            return ["status" => 1];
        }
        if(Auth::attempt(["email" => $request->email,"password" => $request->password]))
        {
            $user = Auth::user();
            $id_user = $user->id;

            OauthAccessTokens::where('user_id',$id_user)->delete();
            $token = $user->createToken('AppChat')->accessToken;

            return ['token' => $token,'status' => 1];
        }
        if($request->has('token_message_facebook'))
        {
            $user = User::where('token_message_facebook',$request->token_message_facebook)->first();
//            return $user;

            if($user != null)
            {
                Auth::login($user);
                $user = Auth::user();
                $id_user = $user->id;
                OauthAccessTokens::where('user_id',$id_user)->delete();
                $token = $user->createToken('AppChat')->accessToken;
                return ['token' => $token,"status" => 1];
            }

        }
        return response()->json(['message' => 'Login that bai','status' => 0],401);
    }
    public function getinfo()
    {
        return Auth::user();
    }
    public function register(Request $request)
    {
        if($request->has('token_message_facebook') && $request->has('email'))
        {
            $user = User::where('email',$request->email)->first();
            if($user != null)
            {
                $user->token_message_facebook = $request->token_message_facebook;
                $user->update();
                Auth::login($user);
            }
            else{
                $user = new ModelUser();
                $user->email = $request->email;
                $user->token_message_facebook = $request->token_message_facebook;
                $user->username = $request->username;
                $user->sex = $request->sex;

                $user->save();

                Auth::login($user);

            }
            // Không cần tài khoản và mật khẩu
            $user = Auth::user();
            $token = $user->createToken('AppChat')->accessToken;
            return ['token' => $token,"status" => 1];
        }
        if($request->has('email') && $request->has('password'))
        {
            $check_user = User::where('email',$request->email)->first();
            if($check_user != null)
            {
                return response()->json(['message' => 'Email da ton tai','status' => 0],403);
            }
            $user = new User();
            $user->email = strtolower($request->email);
            $user->password = Hash::make($request->password);
            $user->username = $request->username;
            $user->sex = $request->sex;
            $user->save();

            Auth::login($user);
            $user = Auth::user();
            $token = $user->createToken('AppChat')->accessToken;
            return ['token' => $token,"status" => 1];
        }
        return response()->json(['message' => 'Đăng ký thất bại','status' => 0],401);
    }
    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'dang xuat thanh cong','status' => 1],200);
    }
    public function updateinfo(Request $request){
        $user = Auth::user();

        if($request->has('email'))
        {
            $checkEmail = User::where('email',$request->email)->count();
            if($checkEmail > 1)
            {
                return response()->json(["status" => 0,"message" => "email da ton tai"],200);
            }
            $user->email = $request->email;

        }
        if($request->has('username'))
        {
            $user->username = $request->username;
        }
        if($request->has('sex'))
        {
            $user->sex = $request->sex;
        }
        if($request->has('password') && $request->has('old_password'))
        {


            if(Hash::check($request->old_password,$user->password))
            {
                $user->password = Hash::make($request->password);
            }
            else{
                return response()->json(["status" => 0,"message" => "Mat khau cu khong dung"],200);
            }
        }
        $user->update();
        return ["status" => 1, "message" => "Update thanh cong"];
    }

    public function request_resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required'
        ],[
            'email.required' => 'Email không có',

        ]);

        if($validator->fails())
        {
            return response()->json($validator->errors(),406);
        }
        if(User::where('email',$request->email)->count() < 1)
        {
            return ['message' => 'Mã xác thực đã được gửi đến Email của bạn',
                'status' => 1];
        }
        if(Mail::to( $request->email)->send(new ResetPassword($request->email)))
        {
            return ['message' => 'Mã xác thực đã được gửi đến Email của bạn',
                'status' => 1];
        }
    }
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required',
            'token' => 'required',
            'password' => 'required',
            'rep_password' => 'required|same:password'
        ],[
            'email.required' => 'Email không có',
            'token.required' => 'Token sai',
            'password.required' => 'Mật khẩu không có',
            'rep_password.required' => 'Không có nhập lại mật khẩu',
            'rep_password.same' => 'Nhập lại mật khẩu không đúng'
        ]);
        if($validator->fails())
        {
            return response()->json([
                'message' => $validator->errors(),
                'status' => 0
            ],406);
        }
        $user_reset = ModelResetPassword::where('email_user',$request->email)->where('token',$request->token)->first();
        if($user_reset == null)
        {
            return response()->json([
                'message' => 'Sai email hoặc token',
                'status' => 0
            ],406);
        }

        if((strtotime(date('Y-m-d H:i:m')) - strtotime($user_reset->created_at)) > 900)
        {
            return response()->json([
                'message' => 'Sai email hoặc token',
                'status' => 0
            ],406);
        }
        $user = User::where('email',$user_reset->email_user)->first();

        $user->password = Hash::make($request->password);

        return response()->json([
            'message' => 'Đổi mật khẩu thành công',
            'status' => 1
        ],200);

    }
}
