<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use App\Models\Department;
use App\Models\Course;
use App\Models\Year;
use App\Models\Student;
use App\Models\User;
use App\Models\Action;
use App\Models\NotificationHistory;
use App\Models\AcademicYear;
use App\Components\Common;
use DataTables;

class NotificationController extends Controller{
    
    public function index(){
    	return view('notification.index');
    }
    
    public function add(Request $request){
    	return view('notification.add');
    }
    public function store(Request $request){

        set_time_limit(0);

    	$request->validate([
                'subject'=>'required',
                'content'=>'required|regex:/^[a-zA-Z0-9&)(.:\-\'\/, ]+$/u',
               
               /* 'student'=>'required',
                'template'=>'required',
                'scheduled'=>'required',
                'scheduled_at'=>'required',
                'status'=>'required',
                'sent_status'=>'required',
                'sent_response'=>'required'*/
            ],
            [
                'name.required'=>'Date field is required.',
                'content.required'=>'Content field is required.',
                'content.regex'=>'Some variables found in this field. Please replace the dynamic variable',

            ]
        );
        $input=$request->all();
        $notification=new Notification;
        $input['year']=$request->year;
        $input['scheduled_at']=DATE('Y-m-d H:i:00',strtotime($request->scheduled_at));
        $input['created_at']=DATE('Y-m-d H:i:s');
        $input['updated_at']=DATE('Y-m-d H:i:s');
        //dd($request->all());
        $notification->create($input);

        $notifyID=\DB::getPdo()->lastInsertId();
        
        
        if($request->scheduled==1){
        
            $qry=" 1";
            if($request->course)
                $qry.=" AND course=".$request->course;
            if($request->department)
                $qry.=" AND department=".$request->department;
            if($request->year)
                $qry.=" AND current_year=".$request->year;
            if($request->student)
                $qry.=" AND stud_id=".$request->student;


            $student=Student::where('status','!=',2)->where('upgrade',0)->where('user','!=',0)->whereRaw($qry)->chunk(1, function ($Data)use($request,$notifyID){

                foreach($Data as $key => $studentData){
                    $user=User::where('user_id',$studentData->user)->where('device_token','!=',NULL)->first();
                    if($user)
                        Common::sendNotification($user->user_id,$studentData->stud_id,$notifyID,$user->device_token,$request->subject,$request->content,2);
                }
            });
        }


        return redirect()->route('notification')->with('success','Notification details successfully saved !');
    }

    public function details(){

     $data=Notification::where('status','!=',2)->orderBy('notify_id','DESC');
      return DataTables::of($data)
          ->addColumn('action', function ($data) {
                    $hist=NotificationHistory::where('notification',$data->notify_id)->count();
                    if($hist && Action::chkaccess('notification.inner'))
                        return '<a href="'.route('notification.inner',['id'=>$data->notify_id]).'" class="btn btn-xs btn-primary" title="Sent Details" data-id="' . $data->notify_id . '"><i class="fa fa-eye"></i></a>';
                    else
                        return '';
           })
          ->editColumn('updated_at',function($data){
            if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
                return DATE('d-m-Y h:i A',strtotime($data->updated_at));
          }) 
          ->editColumn('status',function($data){
            if($data->status==1)
                return "Active";
            else
                return "In Active";
          })
        ->editColumn('course',function($data){
            if(!$data->course || is_null($data->course))
                return "All";

            $course=Course::where('course_id',$data->course)->first();
            return $course->name;
        })
        ->editColumn('department',function($data){
            if(!$data->department || is_null($data->department))
                return "All";

            $course=Department::where('department_id',$data->department)->first();
            return $course->name;
        })
        ->editColumn('year',function($data){
            if(!$data->year || is_null($data->year))
                return "All";

            $year=Year::where('year_id',$data->year)->first();
            return $year->name;
        })
        ->editColumn('academic_year',function($data){
            if(!$data->academic_year || is_null($data->academic_year))
                return "All";

            $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
            return $academic_year->name;
        })
      ->editColumn('date',function($data){
            return DATE('d-m-Y',strtotime($data->date));
      })
      ->editColumn('scheduled',function($data){
            return ($data->scheduled==1) ? 'Sent Instant' : 'scheduled at ( '.DATE('d-m-Y h:i A',strtotime($data->scheduled_at)).' )';
      })
      ->rawColumns(['action'])
      ->make(true);
    }

    public function edit(Request $request){
        $notification=Notification::find($request->id);
        return view('notification.edit',['post'=>$notification]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'remarks'=>'required',
                'status'=>'required'
            ],
            [
                'name.required'=>'Date field is required.',
                'remarks.required'=>'Remarks field is required.',
                'status.required'=>'Status field is required.'
            ]
        );
        $input=$request->all();
        $holiday=Notification::find($request->id);
        $holiday->update();

        return redirect()->route('notification')->with('success','Notification details successfully updated !');;
    }

    public function inner(){
        return view('notification.innerdetails');
    }

    public function innerdetails(Request $request){

     $data=NotificationHistory::where('notification',$request->notify_id)->where('type',2)->orderBy('notifyh_id','DESC');
      return DataTables::of($data)
          ->addColumn('reg_no', function ($data) {
                return Student::find($data->student)->register_no;
           })
          ->editColumn('student', function ($data) {
                return Student::find($data->student)->name;
           }) 
          ->addColumn('parent', function ($data) {
                return Student::find($data->student)->father_name;
           })
          ->addColumn('contact_no', function ($data) {
                $user=User::find($data->user);
                return $user->email;
           })
          ->addColumn('response', function ($data) {
                return "<span data-content='$data->fcm_response' class='response'>Click Here</span>";
           })
     ->rawColumns(['response'])
      ->make(true);
    }

    public function attendance(){
        return view('notification.attendance');
    }


    public function attendancedetails(){

         $data=NotificationHistory::where('type',0)->orderBy('notifyh_id','DESC');
          return DataTables::of($data)
              ->addColumn('reg_no', function ($data) {
                    return Student::find($data->student)->register_no;
               })
              ->editColumn('student', function ($data) {
                    return Student::find($data->student)->name;
               }) 
              ->addColumn('parent', function ($data) {
                    return Student::find($data->student)->father_name;
               })
              ->addColumn('contact_no', function ($data) {
                    $user=User::find($data->user);
                    return $user->email;
               }) 
              ->addColumn('date', function ($data) {
                    return DATE('d-m-Y',strtotime($data->created_at));
               })
              ->editColumn('created_at', function ($data) {
                    return DATE('d-m-Y h:i A',strtotime($data->created_at));
               })
              ->addColumn('response', function ($data) {
                    return "<span data-content='$data->fcm_response' class='response'>Click Here</span>";
               })
         ->rawColumns(['response'])
          ->make(true);
    }

}
