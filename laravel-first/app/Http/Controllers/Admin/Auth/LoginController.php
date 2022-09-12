<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Enums\Auth\GuardEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function show()
    {
        return view('admin.auth.login');
    }

    public function login( LoginRequest $request )
    {
        $credentials = $request->validated();
        if ( ! Auth::guard(GuardEnum::ADMIN)->validate($credentials) ):
            return redirect()->to('login')
                ->withErrors(trans('auth.failed'));
        endif;

        $admin = Auth::guard(GuardEnum::ADMIN)->getProvider()->retrieveByCredentials($credentials);

        Auth::guard(GuardEnum::ADMIN)->login($admin, $request->remember);

        return redirect()->route('admin.dashboard');
    }
}
