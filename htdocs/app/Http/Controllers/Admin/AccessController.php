<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Device;
use App\Models\AccessLogs;
use App\Models\Cron;
use App\Models\Department;
use App\Models\Course;
use App\Models\Year;
use App\Models\Action;
use App\Models\AccessControl;
use App\Models\AccessControlInfo;
use App\Models\AcademicYear;
use DataTables;
use App\Components\Common;
use Auth;

class AccessController extends Controller{

    public function index(Request $request){
        $device=Device::findOrFail($request->id);
        $student=Student::where('photo', '!=', '')->where('status',1)->get();
        $access_control_info='';
        if($request->access_id)
            $access_control_info=AccessControl::where('access_id',$request->access_id)->first();

        return view('access.form', ['device' => $device, 'student' => $student,'access_control_info'=>$access_control_info]);
    }


     public function store(Request $request)
    {

        set_time_limit(0);

        $request->validate([
            'name' => 'required',
            'course' => 'required',
            'department' => 'required',
            'academic_year' => 'required',
            'stud_id' => 'required'
        ]);

        $input = $request->all();

        $exist_acc = AccessControl::filter($request->all())->pluck('student')->toArray();

        $diff = array_diff($request->stud_id, $exist_acc);

        //dd($request->stud_id);
        $auth_user = Auth::user()->id;
        foreach ($request->stud_id as $Data) {

            $student = Student::where('stud_id', $Data)->first();
            $current_qry = AccessControl::filter($request->all())->where('student', $student->stud_id)->first();
            AccessControl::filter($request->all())->where('device_student_id',$student->device_uniqueid)->where('student','!=',$Data)->update(['status'=>2,'device_update'=>1]);
            if ($student && !$current_qry) {
                /*$input['student']=$student->stud_id;
                $input['device_student_id']=$Data;
                $input['status']=1;
                $input['created_by']=$auth_user;
                $input['updated_by']=$auth_user;
                $input['device_update']=0;*/
                //AccessControl::create($input);
                $bulk_rec = ['academic_year'=>$request->academic_year,'register_no'=>$student->register_no,'student' => $student->stud_id, 'device_student_id' => $student->device_uniqueid, 'status' => 1, 'created_by' => $auth_user, 'updated_by' => $auth_user, 'device_update' => 0, 'course' => $request->course, 'department' => $request->department, 'device' => $request->device, 'current_year' => $student->current_year];
                AccessControl::create($bulk_rec);
            } elseif ($current_qry && ($current_qry->status == 2)) {
                $current_qry->status = 1;
                $current_qry->device_update = 0;
                $current_qry->deleted_at = "0000-00-00 00:00:00";
                $current_qry->save();
            }
        }
        //AccessControl::insert($bulk_rec);

        $exist_acc = AccessControl::filter($request->all())->status(1)->pluck('student')->toArray();

        $diff = array_diff($exist_acc, $request->stud_id);
        //dd($exist_acc,$request->stud_id,$diff);
        foreach ($diff as $Data) {
            $dlt_qry = AccessControl::filter($request->all())->where('student', $Data)->first();
            $dlt_qry->status = 2;
            $dlt_qry->device_update = 0;
            $dlt_qry->save($input);
        }

        /*$info = AccessControlInfo::filter($request->all())->first();
        if (!$info)
            $info = new AccessControlInfo;


        $update_count = AccessControl::filter($request->all())->status(1)->where('device_update', 0)->count();
        $dlt_count = AccessControl::filter($request->all())->status(2)->where('deleted_at', '0000-00-00 00:00:00')->where('device_update', 0)->count();

        $info->department = $request->department;
        $info->device = $request->device;
        $info->course = $request->course;
        $info->current_year = $request->current_year;
        $info->pending_update = $update_count;
        $info->pending_delete = $dlt_count;

        if ($dlt_count ||  $update_count) {
            $info->device_update = 0;
        }
        $info->save();*/

        return redirect()->back()->with('success', 'Student allow access successfully saved.');
    }


    public function pending(){
        return view('access.pending');
    }

    public function pendingdetails(Request $request){

        $qry = " 1";

        if($request->course)
               $qry .= ' AND course=' . $request->course;
        if($request->department)
           $qry .= ' AND department=' . $request->department;
        if($request->year)
           $qry .= ' AND current_year=' . $request->year;
        if($request->academic_year)
           $qry .= ' AND academic_year=' . $request->academic_year;
        
        //$data = AccessControlInfo::whereRaw('pending_update!=0 OR pending_delete!=0')->whereRaw($qry)->orderBy('access_info_id', 'DESC');;
        $data = AccessControl::whereRaw($qry)->where('academic_year','!=',0)->whereRaw(' ( ( status=2 AND deleted_at="0000-00-00 00:00:00" ) OR  device_update=0 )')->groupBy('course')->groupBy('department')->groupBy('academic_year')->groupBy('device');
        return DataTables::of($data)
            /* ->addColumn('action', function ($data) {
                $return='';
               $device=Device::where('device_id',$data->device)->first();
               //if($device->device_status=="online")
                $return=' <a href="'.route('access.controll',['id'=>$data->device,'access_id'=>$data->access_info_id,'back'=>'access.details']).'" class="btn btn-xs btn-warning" title="Access Controll" data-id="' . $data->device . '"><i class="fa fa-check-square-o"></i></a>';
                return $return;
            })*/
            ->editColumn('device', function ($data) {
                $device = Device::where('device_id', $data->device)->first();
                return $device->name;
            })
            ->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                return $course->name;
            })
            ->editColumn('department', function ($data) {
                $course = Department::where('department_id', $data->department)->first();
                return $course->name;
            })
            ->editColumn('current_year', function ($data) {
                $year = Year::where('year_id', $data->current_year)->first();
                return $year->name;
            })
             ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                return $academic_year->name;
            })
            ->addColumn('pending_update', function ($data) {
                $input['device'] = $data->device;
                $input['course'] = $data->course;
                $input['department'] = $data->department;
               // $input['current_year'] = $data->current_year;
                $input['academic_year'] = $data->academic_year;
                return AccessControl::filter($input)->whereRaw('device_update=0')->count();
            }) 
            ->addColumn('pending_delete', function ($data) {
                $input['device'] = $data->device;
                $input['course'] = $data->course;
                $input['department'] = $data->department;
                //$input['current_year'] = $data->current_year;
                $input['academic_year'] = $data->academic_year;
                return AccessControl::filter($input)->whereRaw(' ( status=2 AND deleted_at="0000-00-00 00:00:00" )')->count();
            })



            //->rawColumns(['action'])
            ->make(true);
    }

    public function details(){
        return view('access.details');
    }

    public function detailslist(Request $request){

        $qry = " 1";

        if($request->course)
            $qry .= ' AND course=' . $request->course;
        if($request->department)
           $qry .= ' AND department=' . $request->department;
        if($request->year)
           $qry .= ' AND current_year=' . $request->year;
       if($request->academic_year)
           $qry .= ' AND academic_year=' . $request->academic_year;

        $data=AccessControl::where('course','!=',NULL)->where('academic_year','!=',0)->whereRaw($qry)->groupBy('course')->groupBy('department')->groupBy('academic_year')->groupBy('device');//whereRaw('pending_update=0 AND pending_delete=0')->
        return DataTables::of($data)
          ->addColumn('action', function ($data) {
                $return='';
               $device=Device::where('device_id',$data->device)->first();
               if($device->device_status=="online"  && Action::chkaccess('access.controll'))
                $return=' <a href="'.route('access.controll',['id'=>$data->device,'access_id'=>$data->access_id,'back'=>'access.details']).'" class="btn btn-xs btn-warning" title="Access Controll" data-id="' . $data->device . '"><i class="fa fa-check-square-o"></i></a>';
                return $return;
            })
            ->editColumn('device',function($data){
                $device=Device::where('device_id',$data->device)->first();
                return $device->name;
            })
            ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                return $course->name;
            })
            ->editColumn('department',function($data){
                $course=Department::where('department_id',$data->department)->first();
                return $course->name;
            })
            ->editColumn('current_year',function($data){
                $year=Year::where('year_id',$data->current_year)->first();
                return $year->name;
            })
            ->editColumn('academic_year',function($data){
                $academic_year=AcademicYear::where('academic_id',$data->academic_year)->first();
                return $academic_year->name;
            })
            ->addColumn('total_access',function($data){
                $input['device']=$data->device;
                $input['course']=$data->course;
                $input['department']=$data->department;
                //$input['current_year']=$data->current_year;
                $input['academic_year']=$data->academic_year;
                return AccessControl::filter($input)->where('device_update',1)->status(1)->count();
                
            })
            ->addColumn('pending_access',function($data){
                $input['device']=$data->device;
                $input['course']=$data->course;
                $input['department']=$data->department;
               // $input['current_year']=$data->current_year;
                $input['academic_year']=$data->academic_year;
                return AccessControl::filter($input)->whereRaw(' ( ( status=2 AND deleted_at="0000-00-00 00:00:00" ) OR  device_update=0 )')->count();
                
            }) 
             ->addColumn('total_student',function($data){
                $input['device']=$data->device;
                $input['course']=$data->course;
                $input['department']=$data->department;
                $input['academic_year']=$data->academic_year;
                $input['year']=$data->current_year;
                return Student::filter($input)->status(1)->count();
                
            })
         ->rawColumns(['action','total_student'])
        ->make(true);
    }

    public function studentlist(Request $request){
        $student=Student::filter($request->all())->status(1)->where('photo', '!=', '')->get();
        $device=Device::findOrFail($request->device_id);
        $output='<div class="form-group">
                            <div class="col-md-12" style="border:1px solid lightgrey;min-height: 250px;">';
                                foreach($student as $Data){
                                    $access=AccessControl::where('student',$Data->stud_id)->where('device',$request->device_id)->where('status',1)->first();
                                    if($access){
                                        $output.='<div class="col-md-3">
                                                    <div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$Data->device_uniqueid.'" checked="" name="stud_id[]" value='.$Data->stud_id.'>
                                                        <label for="inlineCheckbox"'.$Data->device_uniqueid.'">'.$Data->name.' ( '.$Data->device_uniqueid.' )</label></div>
                                                </div>';
                                    }else{
                                        $output.='<div class="col-md-3">
                                                    <div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$Data->device_uniqueid.'" name="stud_id[]" value='.$Data->stud_id.'>
                                                        <label for="inlineCheckbox'.$Data->device_uniqueid.'">'.$Data->name.' ( '.$Data->device_uniqueid.' )</label></div>
                                                </div>';
                                    }
                                }
                            $output.='</div>
                        </div>';
        echo $output;
        exit;
    }

    public function cron(Request $request){
        return view('access.cron');
    }

     public function crondetails(){

        $data=Cron::whereRaw('cron_type=2 OR cron_type=3')->orderBy('cron_id','DESC');
        return DataTables::of($data)
           /* ->editColumn('device',function($data){
                $device=Device::where('device_id',$data->device)->first();
                return $device->name;
            })
            ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                return $course->name;
            })*/
            ->editColumn('cron_type',function($data){
                if($data->cron_type==2)
                    return 'Student Update';
                elseif($data->cron_type==3)
                    return 'Student Delete';
            })
            ->editColumn('process_status',function($data){
                if($data->process_status==1)
                    return 'Done';
                else
                    return 'Running';
            })
            ->editColumn('created_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->created_at));
            })
            ->editColumn('updated_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->updated_at));
            })
            
           
            
         //->rawColumns(['action'])
        ->make(true);
    } 

    
}
