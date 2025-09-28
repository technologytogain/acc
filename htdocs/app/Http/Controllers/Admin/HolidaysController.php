<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Holidays;
use App\Models\Action;
use App\Models\Year;
use DataTables;

class HolidaysController extends Controller{
    
    public function index(){
    	return view('holidays.index');
    }
    
    public function add(Request $request){
    	return view('holidays.add');
    }
    public function store(Request $request){

    	$request->validate([
                'name'=>'required',
                'remarks'=>'required',
                'year'=>'required'
            ],
            [
                'name.required'=>'Date field is required.',
                'remarks.required'=>'Remarks field is required.',
                'year.required'=>'Current Year field is required.',

            ]
        );

        $chk=Holidays::where('date',DATE('Y-m-d',strtotime($request->name)))->where('status',1)->count();
        if($chk){
            return back()->withInput($request->input())->withErrors(['name' => 'Date already exists you can update only !']);
        }

        $holiday=new Holidays;
        $holiday->date=DATE('Y-m-d',strtotime($request->name));
        $holiday->remarks=$request->remarks;
        if(in_array("777",$request->year))
            $holiday->year=777;
        elseif(is_array($request->year))
            $holiday->year=implode(",",$request->year);
        $holiday->status=1;
        $holiday->created_at=DATE('Y-m-d H:i:s');
        $holiday->updated_at=DATE('Y-m-d H:i:s');
        $holiday->save();

        return redirect()->route('holidays')->with('success','Holidays details successfully saved !');;;
    }

    public function details(Request $request){


    $qry = " 1";

    if($request->from_date && $request->to_date)
       $qry .= ' AND ( date >="' . DATE('Y-m-d',strtotime($request->from_date)).'" AND date <="' . DATE('Y-m-d',strtotime($request->to_date)).'")';
   if($request->from_date && !$request->to_date)
       $qry .= ' AND date >="' . DATE('Y-m-d',strtotime($request->from_date)).'"';
   if(!$request->from_date && $request->to_date)
       $qry .= ' AND date <="' . DATE('Y-m-d',strtotime($request->to_date)).'"';
   if($request->year)
       $qry .= ' AND FIND_IN_SET("'.$request->year.'",year)';


     $data=Holidays::where('status','!=',2)->whereRaw($qry)->orderBy('holiday_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            $btn="";
            if(Action::chkaccess('holidays.edit'))
               $btn.='<a href="'.route('holidays.edit',['id'=>$data->holiday_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->holiday_id . '"><i class="fa fa-edit"></i></a>';
           if(Action::chkaccess('holidays.delete'))
                $btn.=' &nbsp;<a href="Javascript:void(0);" class="btn btn-xs btn-danger holiday-dlt" title="Delete" data-id="' . $data->holiday_id . '"><i class="fa fa-trash"></i></a>';
      
            return $btn;
       })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      }) 
      ->editColumn('year',function($data){
        if($data->year ){
            if($data->year=="777")
                return "All";
            else{
                $set=[];
                $expl=explode(",",$data->year);
                foreach ($expl as $key => $data){
                    $set[]=Year::find($data)->name;
                }
                return implode(", ",$set);
            }
        }

      }) 
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })
      ->editColumn('date',function($data){
            return DATE('d-m-Y',strtotime($data->date));
      })
      ->rawColumns(['action'])
      ->make(true);
    }

    public function edit(Request $request){
        $holiday=Holidays::find($request->id);
        return view('holidays.edit',['post'=>$holiday]);
    }

    public function update(Request $request){
            $request->validate([
                'name'=>'required',
                'remarks'=>'required',
                'status'=>'required',
                'year'=>'required'
            ],
            [
                'name.required'=>'Date field is required.',
                'remarks.required'=>'Remarks field is required.',
                'status.required'=>'Status field is required.',
                'year.required'=>'Current Year field is required.'
            ]
        );

        $holiday=Holidays::find($request->id);
        $holiday->date=DATE('Y-m-d',strtotime($request->name));
        $holiday->remarks=$request->remarks;
        $holiday->status=$request->status;
        $holiday->updated_at=DATE('Y-m-d H:i:s');
        if(in_array("777",$request->year))
            $holiday->year=777;
        elseif(is_array($request->year))
            $holiday->year=implode(",",$request->year);
        $holiday->save();         

        return redirect()->route('holidays')->with('success','Holiday details successfully updated !');
    }

     public function destroy(Request $request){
        Holidays::find($request->id)->update(['status'=>2]);
        return redirect()->route('holidays')->with('success','Holiday details successfully deleted !');
    }

}
