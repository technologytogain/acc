<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings;
use App\Models\Student;
use App\Models\AccessControl;
use App\Models\AccessControlInfo;
use App\Models\AccessLogs;
use App\Models\Attendance;
use App\Models\YearUpgrade;
use App\Models\Department;

class SettingsController extends Controller{
    
    public function index(){
        $post=Settings::where('settings_id',1)->first();
        return view('settings',['post'=>$post]);
    }

    public function store(Request $request){

         $request->validate([
                'start_time'=>'required',
                'late_min'=>'required',
                'attendance_interval'=>'required'
        ]);

         Settings::where('settings_id',1)->update(['start_time'=>$request->start_time,'late_min'=>$request->late_min,'grace_min'=>$request->grace_min,'attendance_interval'=>$request->attendance_interval]);


        return redirect()->back()->with('success', 'Settings successfully updated.');
    }

    public function yearupgrade(Request $request){

        \set_time_limit(0);

        $department=Department::where('status',1)->get();

        foreach ($department as $key => $Data) {
            
            $failure=Student::where('course',$request->course)->where('department',$Data->department_id)->where('failure',1)->get();
            $failure_student=[];
            foreach($failure as $failureData){
                $failure_student[]=$failureData->stud_id;
            }

            Student::where('course',$request->course)->where('department',$Data->department_id)->where('current_year','=',$Data->max_year)->whereNotIn('stud_id',$failure_student)->update(['upgrade' =>1]);
            Student::where('course',$request->course)->where('department',$Data->department_id)->where('current_year','<',$Data->max_year)->whereNotIn('stud_id',$failure_student)->update(['current_year' => \DB::raw('current_year + 1')]);
            
        }       

        $year=new YearUpgrade;
        $year->course=$request->course;
        $year->save();

    }


}
