<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Period;
use App\Models\Course;
use App\Models\Department;
use App\Models\Year;
use App\Models\Timetable;
use App\Models\Action;
use App\Components\Common;
use DataTables;

class PeriodController extends Controller{
    
    public function index(){
    	return view('period.index');
    }
    
    public function add(Request $request){
    	return view('period.add');
    }
    public function store(Request $request){

    	$request->validate([
                'from_time'=>'required',
                'to_time'=>'required',
            ],
            [
                'from_time.required'=>'From Time field is required.',
                'to_time.required'=>'To Time field is required.',
            ]
        );
        $period=new Period;
        $period->name=$request->name;
        $period->course=$request->course;
        $period->department=$request->department;
        $period->year=$request->year;
        $period->from_time=DATE('H:i:00',strtotime($request->from_time));
        $period->to_time=DATE('H:i:00',strtotime($request->to_time));
        $period->status=1;
        $period->created_at=DATE('Y-m-d H:i:s');
        $period->updated_at=DATE('Y-m-d H:i:s');
        $period->save();

        $qry=Period::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('status',1)->orderBy('from_time')->get();
        $inc=1;
        foreach($qry as $Data){
            $Data->order=$inc;
            $Data->update();
            $inc++;
        }   

        return redirect()->route('period')->with('success','Period details successfully saved !');;;
    }

    public function details(Request $request){

    $qry = " 1";

    if($request->course)
        $qry .= ' AND course=' . $request->course;
    if($request->department)
       $qry .= ' AND department=' . $request->department;
    if($request->year)
       $qry .= ' AND year=' . $request->year;

     $data=Period::where('status','!=',2)->whereRaw($qry)->orderBy('course','ASC')->orderBy('department','ASC')->where('status',1)->orderBy('year','ASC')->orderBy('from_time','ASC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('period.edit'))   
               return '<a href="'.route('period.edit',['id'=>$data->period_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->period_id . '"><i class="fa fa-edit"></i></a>
                &nbsp;<a href="#" class="btn btn-xs btn-danger period-dlt" title="Delete" data-id="' . $data->period_id . '"><i class="fa fa-trash"></i></a>';
       })
      ->editColumn('course',function($data){
            $course=Course::where('course_id',$data->course)->first();
            return $course->name;
        }) 
        ->editColumn('department',function($data){
            $course=Department::where('department_id',$data->department)->first();
            return $course->name;
        })
        ->editColumn('year',function($data){
            $year=Year::where('year_id',$data->year)->first();
            return $year->name;
        })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      })
      ->editColumn('from_time',function($data){
            return DATE('h:i A',strtotime($data->from_time));
      })
      ->editColumn('to_time',function($data){
            return DATE('h:i A',strtotime($data->to_time));
      }) 
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })
      ->rawColumns(['action'])
      ->make(true);
    }

    public function edit(Request $request){
        $period=Period::find($request->id);
        return view('period.edit',['post'=>$period]);
    }

    public function update(Request $request){
            $request->validate([
                'status'=>'required'
            ],
            [
                'status.required'=>'Status field is required.',
                'from_time.required'=>'From Time field is required.',
                'to_time.required'=>'To Time field is required.',
            ]
        );

        $period=Period::find($request->id);
        $period->name=$request->name;
        $period->from_time=DATE('H:i:00',strtotime($request->from_time));
        $period->to_time=DATE('H:i:00',strtotime($request->to_time));
        $period->status=$request->status;
        $period->updated_at=DATE('Y-m-d H:i:s');
        $period->save();
        
        $qry=Period::where('course',$request->course)->where('department',$request->department)->where('year',$request->year)->where('status',1)->orderBy('from_time')->get();
        $inc=1;
        foreach($qry as $Data){
            $Data->order=$inc;
            $Data->update();
            $inc++;
        }   

        Timetable::where('timeslot',$period->period_id)->update(['from_time'=>$period->from_time,'to_time'=>$period->to_time]);

        return redirect()->route('period')->with('success','Period details successfully updated !');;
    }

    public function delete(Request $request){

        $p_dlt=Period::where('period_id',$request->id)->first();
        $p_dlt->status=2;
        $p_dlt->update();

        $dlt=Timetable::where('timeslot',$p_dlt->period_id)->update(['status'=>2]);
        
        $qry=Period::where('course',$p_dlt->course)->where('department',$p_dlt->department)->where('year',$p_dlt->year)->where('status',1)->orderBy('from_time')->get();
        $inc=1;
        foreach($qry as $Data){
            $Data->order=$inc;
            $Data->update();
            $inc++;
        }

        $weekdays=Common::weekdays();
        foreach($weekdays as $key=>$day){
            $qry=Timetable::where('course',$p_dlt->course)->where('department',$p_dlt->department)->where('year',$p_dlt->year)->where('weekday',$key)->where('status',1)->orderBy('from_time')->get();
            $inc=1;
            foreach($qry as $Data){
                $Data->period=$inc;
                $Data->update();
                $inc++;
            }   
        }
        
        echo "deleted";
        exit;
    }

}
