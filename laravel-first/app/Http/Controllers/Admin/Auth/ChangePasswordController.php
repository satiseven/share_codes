<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ChangePasswordRequest;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class ChangePasswordController extends Controller
{
    public function show()
    {
        return view('admin.auth.change_password');
    }

    public function change_password( ChangePasswordRequest $request )
    {
        Admin::query()->find(auth()->guard('admin')->id())->update([ Admin::PASSWORD => Hash::make($request->password) ]);

        return redirect()->back()->with('success', 'Changed password successfully.');
    }
}
