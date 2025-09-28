<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Http\Controllers\Controller;

class DashboardController extends Controller{

    public function index(){

        //$datetime="2023-04-10 08:49:00";
        //$timetable=\App\Models\Timetable::whereRaw('SUBTIME(from_time,"0:10:0") <= "'.DATE("H:i:s",strtotime($datetime)).'" ')->where('to_time','>',DATE("H:i:s",strtotime($datetime)))->where('course',1)->where('department',2)->where('year',1)->where('weekday',DATE('N',strtotime($datetime)))->first();

        //dd($timetable);

        $male=$female=0;
        
        $male=Student::where('status',1)->where('gender',1)->count();  
        $female=Student::where('status',1)->where('gender',2)->count(); 
        
        return view('dashboard',compact('male','female'));
    }
}
