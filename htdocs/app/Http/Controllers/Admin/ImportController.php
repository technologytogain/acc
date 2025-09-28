<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\AccessControl;
use App\Models\Year;
use App\Models\Course;
use App\Models\Department;
use App\Models\Student;
use App\Models\Cron;
use App\Models\ImportClone;
use App\Components\DeviceConfig;
use DataTables;
use App\Components\Common;

class ImportController extends Controller{

	public function index(Request $request){
		return view('import.index');
	}

	public function details(Request $request){

		$data=ImportClone::where('type',2)->where('status',1)->orderBy('iclone_id','DESC');//whereRaw('pending_update=0 AND pending_delete=0')->
		return DataTables::of($data)
		  ->addColumn('action', function ($data) {
				$return='';
			   $student=Student::where('course','')->orwhereNull('course')->count();
			   $last_record=ImportClone::where('type',2)->where('status',1)->orderBy('iclone_id','DESC')->first();

			   if($student && ($last_record->iclone_id==$data->iclone_id))
					$return=' <a href="'.route('import.form',['id'=>$data->to_device,'back'=>'import']).'" class="btn btn-xs btn-warning" title="Student Allocation" data-id="' . $data->device . '"><i class="fa fa-address-book-o"></i></a>';
				return $return;
			})
			->editColumn('to_device',function($data){
				$device=Device::where('device_id',$data->to_device)->first();
				return $device->name;
			})
			->editColumn('created_at',function($data){
				return DATE('d-m-Y h:i A',strtotime($data->created_at));
			})
			->editColumn('updated_at',function($data){
				return DATE('d-m-Y h:i A',strtotime($data->updated_at));
			})
		->rawColumns(['action'])
		->make(true);
	}

	public function pending(Request $request){
		return view('import.pending');
	}

		public function pendingdetails(Request $request){

		$data=ImportClone::where('type',2)->where('status',0)->orderBy('iclone_id','DESC');//whereRaw('pending_update=0 AND pending_delete=0')->
		return DataTables::of($data)
		  ->addColumn('action', function ($data) {
				$return='';
			   $device=Device::where('device_id',$data->to_device)->first();
			   //if($device->device_status=="online")
				$return=' <a href="'.route('import.form',['id'=>$data->to_device,'import_id'=>$data->iclone_id,'back'=>'import']).'" class="btn btn-xs btn-warning" title="Edit" data-id="' . $data->device . '"><i class="fa fa-pencil"></i></a>';
				return $return;
			})
			->editColumn('to_device',function($data){
				$device=Device::where('device_id',$data->to_device)->first();
				return $device->name;
			})
			->editColumn('created_at',function($data){
				return DATE('d-m-Y h:i A',strtotime($data->created_at));
			})
			->editColumn('updated_at',function($data){
				return DATE('d-m-Y h:i A',strtotime($data->updated_at));
			})
		/*	->editColumn('years',function($data){
				$expl=explode(",",$data->years);
				$year=[];
				foreach($expl as $Data){
					$year[]=Year::where('year_id',$Data)->first()->name;
				}
				return implode(", ",$year);
			})*/
		  
		 ->rawColumns(['action'])
		->make(true);
	}

	public function form(Request $request){
		$device         = Device::where('device_id',$request->id)->first();
		$year         = Year::where('status',1)->get();
		$students=Student::where('course','')->orwhereNull('course')->where('device',$request->id)->get();

		return view('import.form', ['device' => $device,'year'=>$year,'students'=>$students]);
	}

	public function store(Request $request){
		
		/*$import=ImportClone::where('to_device',$request->device_id)->where('department',$request->department)->where('course',$request->course)->where('type',2)->first();
		if(!$import)
			$import=new ImportClone;
		
		$import->from_device=0;
		$import->to_device=$request->device_id;
		$import->type=2;
		$import->years=implode(",",$request->year_id);
		$import->status=0;
		$import->course=$request->course;
		$import->department=$request->department;
		$import->created_at=DATE('Y-m-d H:i:s');
		$import->save();*/

		$import=ImportClone::where('to_device',$request->id)->where('type',2)->where('status',0)->first();
		if($import)
			return redirect()->route('import.pending')->with('error', 'Request already pending for this device !. Please check the below list');	

		$import=new ImportClone;
		$import->from_device=0;
		$import->to_device=$request->id;
		$import->type=2;
		$import->save();
		

		return redirect()->route('import.pending')->with('success', 'Import student details successfully saved.Started will soon...');
	}

	public function yearlist(Request $request){
		$output='';
		$years=[];
		$year=Year::where('status',1)->get();
		$exist=ImportClone::where('to_device',$request->to_device)->where('department',$request->department)->where('course',$request->course)->where('type',2)->first();
		if($exist){
			$years=explode(",",$exist->years);
		}
		$output.='<div class="form-group">
					<div class="col-md-12" style="border:1px solid lightgrey;min-height: 250px;">';
						foreach($year as $yearinfo){
							if(in_array($yearinfo->year_id,$years))
								$output.='<div class="col-md-2">
											<div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$yearinfo->year_id.'" checked="" name="year_id[]" value='.$yearinfo->year_id.'>
												<label for="inlineCheckbox'.$yearinfo->year_id.'">'.$yearinfo->name.'</label></div>											
										</div>';
							else{
								$output.='<div class="col-md-2">
											<div class="checkbox checkbox-info"><input class="inputCheckbox" type="checkbox" id="inlineCheckbox'.$yearinfo->year_id.'" name="year_id[]" value='.$yearinfo->year_id.'>
												<label for="inlineCheckbox'.$yearinfo->year_id.'">'.$yearinfo->name.'</label></div>
											
										</div>';
							}
						}
					$output.='</div>
				</div>';
		return $output;

	}

	public function studentallocation(Request $request){


		$request->validate([
				'course'=>'required',
				'department'=>'required',
				'current_year'=>'required',
				'stud_id'=>'required'
			],[
				'course.required'=>'Course field is required.',
				'department.required'=>'Department field is required.',
				'current_year.required'=>'Current Year field is required.',
				'stud_id.required'=>'Student\'s field is required.',
			]
		);

		foreach($request->stud_id as $Data){
			$student=Student::whereRaw('stud_id="' . $Data . '"')->first();
			$access_chk = AccessControl::where('device_student_id', $student->device_uniqueid)->where('device', $request->id)->first();
			if (!$access_chk) {
				
				$acc_ins                     = new AccessControl;
				$acc_ins->student            = $student->stud_id;
				$acc_ins->device_student_id  = $student->device_uniqueid;
				$acc_ins->department         = $request->department;
				$acc_ins->device             = $request->device_id;
				$acc_ins->device_update      = 1;
				$acc_ins->course         	 = $request->course;
				$acc_ins->current_year       = $request->current_year;
				$acc_ins->department         = $request->department;
				$acc_ins->status             = 1;
				$acc_ins->save();
			
				$student->course=$request->course;
				$student->department=$request->department;
				$student->current_year=$request->current_year;
				$student->save();
			}
		}
		return redirect()->back()->with('success', 'Student Allocation successfully saved.');

	}

	 public function cron(Request $request){
        return view('import.cron');
    }

     public function crondetails(){

        $data=Cron::whereRaw('cron_type=4')->orderBy('cron_id','DESC');
        return DataTables::of($data)
           /* ->editColumn('device',function($data){
                $device=Device::where('device_id',$data->device)->first();
                return $device->name;
            })
            ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                return $course->name;
            })*/
          
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
