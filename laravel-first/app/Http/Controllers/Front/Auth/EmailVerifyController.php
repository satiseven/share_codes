<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerifyController extends Controller
{
    public function notice()
    {
        return view('front.auth.email_verify');
    }

    public function verify( EmailVerificationRequest  $request )
    {
        $request->fulfill();

        return redirect()->route('dashboard');
    }

    public function resend( Request $request )
    {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent!');
    }
}
