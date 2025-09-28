<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Timetable;
use App\Models\Device;
use App\Models\Year;
use App\Models\Course;
use App\Models\Department;
use App\Models\Period;
use App\Models\Subjects;
use App\Models\LunchBreak;
use App\Models\Action;
use App\Models\Student;
use App\Components\Common;
use DataTables;

class TimetableController extends Controller{
	
	public function index(){
		return view('timetable.index');
	}
	
	public function add(Request $request){
		$device=Device::where('status',1)->get();
		$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('status',1)->get();
		$post=Period::where('period_id',$request->period_id)->first();

		return view('timetable.add',['device'=>$device,'year'=>$year,'theoryprac'=>$theoryprac,'period'=>$period,'post'=>$post,'weekday'=>$request->day]);
	}

	public function store(Request $request){

		$request->validate([
				'course'=>'required',
				'department'=>'required',
				'year'=>'required',
				'subject'=>'required',
				'timeslot'=>'required',
				'theory_practical'=>'required',
				'weekday'=>'required',
			],
			[
				'course.required'=>'Course field is required.',
				'department.required'=>'Department field is required.',
				'year.required'=>'Year field is required.',
				'subject.required'=>'Subject field is required.',
				'timeslot.required'=>'Time Slot field is required.',
				'theory_practical.required'=>'Theory / Practical field is required.',
				'weekday.required'=>'Day field is required.'

			]
		);

		$timetable=new Timetable;
		$input=$request->all();
		$timeslot=Period::where('period_id',$request->timeslot)->first();
		$input['from_time']=$timeslot->from_time;
		$input['to_time']=$timeslot->to_time;
		$input['subject']=implode(",",$request->subject);
		if($request->combained_periods)
			$input['combained_periods']=$request->combained_periods;
		$timetable->create($input);

		$exist=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('weekday',$request->weekday)->where('lunchbreak',1)->where('status',1)->first();
		if(!$exist){
			$timetable=new Timetable;
			$timeslot=Period::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('name',15)->first();
			$input['from_time']=$timeslot->from_time;
			$input['to_time']=$timeslot->to_time;
			$input['lunchbreak']=1;
			$input['weekday']=	$request->weekday;
			$input['timeslot']=	$timeslot->period_id;
			$timetable->create($input);
		}



		$qry=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('weekday',$request->weekday)->where('status',1)->orderBy('from_time')->get();
		$inc=1;
		foreach($qry as $Data){
			$Data->period=$inc;
			$Data->update();
			$inc++;
			$tableid=$Data->timetable_id;
		}	
		//dd($timetable);

	/*	$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('status',1)->get();

		foreach($year as $key=>$yearData){
			$time_table=new Timetable;
			$time_table->department=$request->department;
			$time_table->date=DATE('Y-m-d',strtotime($request->date));
			$time_table->course=$request->course;
			$time_table->year=$yearData->year_id;
			foreach($period as $periodData){
			   //echo "device_".$yearData->year_id.'_'.$periodData."<br>";
				$field=$periodData->field;
				$value="device_".$yearData->year_id.'_'.$periodData->name;
				$time_table->$field=$request->$value;
				
				$type_field="type_".$periodData->field;
				$type_value="tp_".$yearData->year_id.'_'.$periodData->name;
				$time_table->$type_field=$request->$type_value;
			}
			$time_table->save();
		}
		*/



		/*$course=new Timetable;
		$course->name=$request->name;
		$course->status=1;
		$course->created_at=DATE('Y-m-d H:i:s');
		$course->updated_at=DATE('Y-m-d H:i:s');
		$course->save();*/

		return redirect()->route('timetable.view',['id'=>$request->timeslot])->with('success','Timetable  details successfully saved !');;;
	}

	public function details(Request $request){

	$qry = " 1";

    if($request->course)
        $qry .= ' AND course=' . $request->course;
    if($request->department)
       $qry .= ' AND department=' . $request->department;
    if($request->year)
       $qry .= ' AND year=' . $request->year;

	 $data=Period::where('status','!=',2)->whereRaw($qry)->groupBy('course')->groupBy('department')->groupBy('year')->orderBy('period_id','DESC'); //
	  return DataTables::of($data)
	  ->addColumn('action', function ($data) {
			if(Action::chkaccess('timetable.view'))
			   return '<a href="'.route('timetable.view',['id'=>$data->period_id]).'" class="btn btn-xs btn-warning" title="View Time Table" data-id="' . $data->period_id . '"><i class="fa fa-info-circle"></i></a>';
	   })
	  ->editColumn('updated_at',function($data){
		if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
			return DATE('d-m-Y h:i A',strtotime($data->updated_at));
	  }) 
	 
        ->editColumn('course',function($data){
            $course=Course::where('course_id',$data->course)->first();
            return $course->name;
        }) 
     	 ->editColumn('status',function($data){
			if($data->status==1)
				return "Active";
			else
				return "In Active";
		  })
        ->editColumn('department',function($data){
        	$department=Department::where('department_id',$data->department)->first();
            if($department)
            	return $department->name;
        })
        ->editColumn('year',function($data){
            $year=Year::where('year_id',$data->year)->first();
            return $year->name;
        })
     /*   ->editColumn('weekday',function($data){
            return Common::weekdays($data->weekday);
        })*/
        
	  ->rawColumns(['action'])
	  ->make(true);
	}

	public function edit(Request $request){
		$timetable=Timetable::find($request->id);
		$device=Device::where('status',1)->get();
		$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('status',1)->get();
		return view('timetable.edit',['device'=>$device,'year'=>$year,'theoryprac'=>$theoryprac,'period'=>$period,'post'=>$timetable]);
	}

	public function update(Request $request){
		
		$request->validate([
				'course'=>'required',
				'department'=>'required',
				'year'=>'required',
				'subject'=>'required',
				'timeslot'=>'required',
				'theory_practical'=>'required',
				'weekday'=>'required',
			],
			[
				'course.required'=>'Course field is required.',
				'department.required'=>'Department field is required.',
				'year.required'=>'Year field is required.',
				'subject.required'=>'Subject field is required.',
				'timeslot.required'=>'Time Slot field is required.',
				'theory_practical.required'=>'Theory / Practical field is required.',
				'weekday.required'=>'Day field is required.'

			]
		);


		$timetable=Timetable::where('timetable_id',$request->id)->first();
		$input=$request->all();
		$timeslot=Period::where('period_id',$request->timeslot)->first();
		$input['from_time']=$timeslot->from_time;
		$input['to_time']=$timeslot->to_time;
		$input['subject']=implode(",",$request->subject);
		if($request->combained_periods)
			$input['combained_periods']=$request->combained_periods;

		$timetable->update($input);

		/*$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('status',1)->get();

		$timetable=Timetable::where('timetable_id',$request->id)->first();

		foreach($year as $key=>$yearData){
			$time_table=Timetable::where('course',$timetable->course)->where('department',$timetable->department)->where('year',$yearData->year_id)->first();
			$time_table->department=$request->department;
			$time_table->date=DATE('Y-m-d',strtotime($request->date));
			$time_table->course=$request->course;
			$time_table->year=$yearData->year_id;
			foreach($period as $periodData){
			   //echo "device_".$yearData->year_id.'_'.$periodData."<br>";
				$field=$periodData->field;
				$value="device_".$yearData->year_id.'_'.$periodData->name;
				$time_table->$field=$request->$value;
				
				$type_field="type_".$periodData->field;
				$type_value="tp_".$yearData->year_id.'_'.$periodData->name;
				$time_table->$type_field=$request->$type_value;
			}
			$time_table->save();
		}*/
		$weekdays=Common::weekdays();
		foreach($weekdays as $key=>$day){
			$qry=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('weekday',$key)->where('status',1)->orderBy('from_time')->get();
			$inc=1;
			foreach($qry as $Data){
				$Data->period=$inc;
				$Data->update();
				$inc++;
			}
		}

		return redirect()->route('timetable.view',['id'=>$request->timeslot])->with('success','Timetable details successfully updated !');;
	}


	public function view(Request $request){
		$period_qry=Period::find($request->id);
		$device=Device::where('status',1)->get();
		$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('course',$period_qry->course)->where('department',$period_qry->department)->where('year',$period_qry->year)->where('status',1)->orderBy('from_time','ASC')->get();
		return view('timetable.view',['device'=>$device,'year'=>$year,'theoryprac'=>$theoryprac,'period'=>$period,'post'=>$period_qry]);
	}

	public function innerdetails(){
		return view('timetable.innerdetails');
	}

	public function innerlist(Request $request){

	$timetable=Timetable::where('timetable_id',$request->timetable_id)->first();
	 $data=Timetable::where('status','!=',2)->where('weekday',$timetable->weekday)->where('course',$timetable->course)->where('department',$timetable->department)->where('year',$timetable->year)->orderBy('from_time','DESC'); //->groupBy('course')->groupBy('department')
	  return DataTables::of($data)
	  ->addColumn('action', function ($data) {
			   return '
		   <a href="'.route('timetable.edit',['id'=>$data->timetable_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->timetable_id . '"><i class="fa fa-edit"></i></a>
		   <a href="'.route('timetable.view',['id'=>$data->timetable_id]).'" class="btn btn-xs btn-warning" title="Edit" data-id="' . $data->timetable_id . '"><i class="fa fa-info-circle"></i></a>';
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
            $course=Course::where('course_id',$data->course)->first();
            return $course->name;
        }) 
        ->editColumn('from_time',function($data){
            return DATE('h:i A',strtotime($data->from_time))." - ".DATE('h:i A',strtotime($data->to_time));
        })
        ->editColumn('department',function($data){
            $course=Department::where('department_id',$data->department)->first();
            return $course->name;
        })
        ->editColumn('year',function($data){
            $year=Year::where('year_id',$data->year)->first();
            return $year->name;
        })
        ->editColumn('weekday',function($data){
            return Common::weekdays($data->weekday);
        })
        ->editColumn('subject',function($data){
        	if($data->subject){
	        	$expl=explode(",",$data->subject);
	            $set=[];
	            foreach($expl as $key => $Data) {
	            	$set[]=Subjects::where('subject_id',$Data)->first()->name;
	            }
	            return implode(",", $set);
        	}elseif($data->lunchbreak){
        		return "<b>Lunch Break</b>";
        	}
        })
	  ->rawColumns(['action','subject'])
	  ->make(true);
	}

	public function addlunchbreak(Request $request){
		$device=Device::where('status',1)->get();
		$year=Year::where('status',1)->get();
		$theoryprac=Common::TheoryPractical();
		$period=Period::where('status',1)->get();
		$post=Timetable::where('timetable_id',$request->id)->first();

		return view('timetable.lunchbreakadd',['device'=>$device,'year'=>$year,'theoryprac'=>$theoryprac,'period'=>$period,'post'=>$post]);
	}

	public function lunchbreak(){
		return view('timetable.lunchbreak');
	}	

	public function lunchbreakdetails(){

	 $data=LunchBreak::where('status','!=',2)->orderBy('lunch_id','DESC'); //
	  return DataTables::of($data)
	  ->addColumn('action', function ($data) {
			   return '
		   <a href="'.route('timetable.add',['id'=>$data->lunch_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->lunch_id . '"><i class="fa fa-pencil"></i></a>';
	   })
	  ->editColumn('updated_at',function($data){
		if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
			return DATE('d-m-Y h:i A',strtotime($data->updated_at));
	  }) 
	 
        ->editColumn('course',function($data){
            $course=Course::where('course_id',$data->course)->first();
            return $course->name;
        }) 
     	 ->editColumn('status',function($data){
			if($data->status==1)
				return "Active";
			else
				return "In Active";
		  })
        ->editColumn('department',function($data){
            $course=Department::where('department_id',$data->department)->first();
            return $course->name;
        })
        ->editColumn('year',function($data){
            $year=Year::where('year_id',$data->year)->first();
            return $year->name;
        })
        ->editColumn('timeslot',function($data){
            $timeslot=Period::where('period_id',$data->timeslot)->first();
            return DATE('h:i A',strtotime($timeslot->from_time))." - ".DATE('h:i A',strtotime($timeslot->to_time));
        })
        
	  ->rawColumns(['action'])
	  ->make(true);
	}

	public function storelunchbreak(Request $request){

		$request->validate([
				'course'=>'required',
				'department'=>'required',
				'year'=>'required',
				'timeslot'=>'required',
			],[
				'course.required'=>'Course field is required.',
				'department.required'=>'Department field is required.',
				'year.required'=>'Year field is required.',
				'timeslot.required'=>'Time Slot field is required.',
			]
		);

		$input=$request->all();

		$lunch=new LunchBreak;
		$lunch->create($input);

		$weekdays=Common::weekdays();
		foreach($weekdays as $key=>$day){
			$exist=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('weekday',$key)->where('status',1)->where('lunchbreak',1)->first();
			if(!$exist){
				$timetable=new Timetable;
				$timeslot=Period::where('period_id',$request->timeslot)->first();
				$input['from_time']=$timeslot->from_time;
				$input['to_time']=$timeslot->to_time;
				$input['lunchbreak']=1;
				$input['weekday']=	$key;
				$timetable->create($input);
			}
		}

		foreach($weekdays as $key=>$day){
			$qry=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('weekday',$key)->where('status',1)->orderBy('from_time')->get();
			$inc=1;
			foreach($qry as $Data){
				$Data->period=$inc;
				$Data->update();
				$inc++;
			}	
		}


		return redirect()->route('lunchbreak')->with('success','Lunch Break details successfully saved !');;;
	}

	 public function list(Request $request){
	 	$year=Common::getyear($request->academic_year);
	 	$timetable_qry=Timetable::where('course',$request->course)->where('department',$request->department)->where('year',$year)->where('status',1)->orderBy('period','ASC')->groupBy('from_time')->get();
        $set='';
        foreach($timetable_qry as $key => $Data) {
        	if($Data->lunchbreak){
            	if($request->sel==$Data->timetable_id)
            		$set.="<option selected value=".$Data->timetable_id.">LUNCHBREAK</option>";
            	else
            		$set.="<option value=".$Data->timetable_id.">LUNCHBREAK</option>";
        	}else{
        		if($request->sel==$Data->timetable_id)
            		$set.="<option selected value=".$Data->timetable_id.">".DATE('h:i A',strtotime($Data->from_time))." ".DATE('h:i A',strtotime($Data->to_time))."</option>";
            	else
            		$set.="<option value=".$Data->timetable_id.">".DATE('h:i A',strtotime($Data->from_time))." ".DATE('h:i A',strtotime($Data->to_time))."</option>";
            }
        }
        echo $set;
        exit;
    } 

    public function crontimings(){
		return view('timetable.cron');
	}

}
