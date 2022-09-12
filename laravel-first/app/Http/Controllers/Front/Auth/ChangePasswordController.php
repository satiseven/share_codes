<?php

namespace App\Http\Controllers\Front\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Front\Auth\ChangePasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('front.auth.change_password');
    }

    public function change_password( ChangePasswordRequest $request )
    {
        User::query()->find(auth()->id())->update([ User::PASSWORD => Hash::make($request->password) ]);

        return redirect()->back()->with('success', 'Changed password successfully.');
    }
}
