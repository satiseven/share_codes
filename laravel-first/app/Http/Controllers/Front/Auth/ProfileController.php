<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;

class ProfileController extends Controller
{
    public function __invoke()
    {
        return view('front.auth.profile');
    }
}
