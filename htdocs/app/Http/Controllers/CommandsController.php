<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Department;
use App\Models\Course;
use App\Models\Year;
use App\Models\Student;
use App\Models\User;
use App\Models\NotificationHistory;
use App\Components\Common;
use DataTables;

class CommandsController extends Controller{
    
    public function index(){
    	return view('commands.index');
    }

    public function addstudent(){
        \Artisan::call('addstudent');
        return redirect()->route('commands')->with('success', 'Add Student command run successfully !');
    }
    public function deletestudent(){
        \Artisan::call('deletestudent');
        return redirect()->route('commands')->with('success', 'Delete Student command run successfully !');
    }
    public function importstudent(){
        \Artisan::call('importstudent:device');
        return redirect()->route('commands')->with('success', 'Import Student command run successfully !');
    }
    public function clonedevice(){
        \Artisan::call('clone:device');
        return redirect()->route('commands')->with('success', 'Clone Device command run successfully !');
    }
    public function attendance(Request $request){
        //dd($request->att_datetime);
        \Artisan::call('attendance',['--datetime'=>$request->att_datetime]);
        //return redirect()->route('commands')->with('success', 'Attendance command run successfully !');
    }
}
