<?php

namespace App\Mail;

use App\User;
use DateTime;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\ResetPassword as ModelResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    protected $email;
    public function __construct($email)
    {
        $this->email = $email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {



        $user = ModelResetPassword::where('email_user',$this->email)->first();
        $token = str_random(6);

        if($user == null)
        {
            $resetpassword = new ModelResetPassword();
            $resetpassword->email_user = $this->email;
            $resetpassword->token = $token;
            $resetpassword->save();
        }
        else{
            $user->token = $token;
            $user->update();
        }
        $user = User::where('email',$this->email)->first();
        return $this->from('acv12345.email@gmail.com')->subject('Reset Password')->view('email.token',['token' => $token,'email','username' => $user->username]);
    }
}
