<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Hash;

class LoginController extends Controller{


    public function index(Request $request){
        return view('login');
    }


    public function login(Request $request){
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);


        $credentials = $request->only('email','password');
        if(Auth::attempt($credentials)) {
            return redirect()->intended('admin/dashboard')->withSuccess('You have Successfully loggedin');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request) {
      Auth::logout();
      return redirect('/');
    }
    
}
