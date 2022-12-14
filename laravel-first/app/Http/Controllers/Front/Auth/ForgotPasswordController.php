<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function showForgetPasswordForm()
    {
        return view('front.auth.forget_password');
    }

    public function submitForgetPasswordForm( Request $request )
    {
        $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => now(),
        ]);

        Mail::to($request->email)->send(new PasswordResetMail($token));

        return back()->with('message', 'We have e-mailed your password reset link!');
    }

    public function showResetPasswordForm( $token )
    {
        return view('front.auth.forget_password_link', [ 'token' => $token ]);
    }

    public function submitResetPasswordForm( Request $request )
    {
        $request->validate([
            'token'                 => 'required|email|exists:password_resets',
            'email'                 => 'required|email|exists:users',
            'password'              => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required',
        ]);

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token,
            ])
            ->first();

        if ( ! $updatePassword ) {
            return back()->withInput()->with('error', 'Invalid token!');
        }

        $user = User::query()->where('email', $request->email)
            ->update([ 'password' => Hash::make($request->password) ]);

        DB::table('password_resets')->where([ 'email' => $request->email ])->delete();

        return redirect('/login')->with('message', 'Your password has been changed!');
    }
}
