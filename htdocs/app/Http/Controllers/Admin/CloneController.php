<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\AccessControl;
use App\Models\ImportClone;
use App\Models\Cron;
use App\Models\Department;
use App\Models\Course;
use DataTables;
use App\Components\Common;

class CloneController extends Controller{

    public function index(Request $request){
        return view('clone.index');
    }
    
    public function form(Request $request){
            $device         = Device::where('device_id',$request->id)->first();
            $linked_devices = AccessControl::get(); //where('status','!=',2)->groupBy('device')->
            $list           = [];
            foreach ($linked_devices as $Device) {
                $list[] = $Device->device;
            }
            $map_device = Device::whereNotIn('device_id', $list)->where('verification_status', '=', 1)->where('status', '!=', 2)->get();
            return view('clone.form', ['device' => $device, 'map_device' => $map_device]);
        }

    public function details(Request $request){

        $data=ImportClone::where('type',1)->where('status',1)->orderBy('iclone_id','DESC');//whereRaw('pending_update=0 AND pending_delete=0')->
        return DataTables::of($data)
          ->addColumn('action', function ($data) {
                $return='';
               $device=Device::where('device_id',$data->to_device)->first();
               //if($device->device_status=="online")
                $return=' <a href="'.route('import.form',['id'=>$data->to_device,'import_id'=>$data->iclone_id,'back'=>'import']).'" class="btn btn-xs btn-warning" title="Edit" data-id="' . $data->device . '"><i class="fa fa-pencil"></i></a>';
                return $return;
            })
            ->editColumn('from_device',function($data){
                $device=Device::where('device_id',$data->from_device)->first();
                return $device->name;
            })
            ->editColumn('created_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->created_at));
            })
            ->editColumn('updated_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->updated_at));
            })
            ->editColumn('to_device',function($data){
                $expl=explode(",",$data->to_device);
                $device=[];
                foreach($expl as $Data){
                    $device[]=Device::where('device_id',$Data)->first()->name;
                }
                return implode(", ",$device);
            })
          
         ->rawColumns(['action'])
        ->make(true);
    }

    public function pending(Request $request){
        return view('clone.pending');
    }

        public function pendingdetails(Request $request){

        $data=ImportClone::where('type',1)->where('status',0)->orderBy('iclone_id','DESC');//whereRaw('pending_update=0 AND pending_delete=0')->
        return DataTables::of($data)
           ->addColumn('action', function ($data) {
                $return='';
               $device=Device::where('device_id',$data->to_device)->first();
               //if($device->device_status=="online")
                $return=' <a href="'.route('import.form',['id'=>$data->to_device,'import_id'=>$data->iclone_id,'back'=>'import']).'" class="btn btn-xs btn-warning" title="Edit" data-id="' . $data->device . '"><i class="fa fa-pencil"></i></a>';
                return $return;
            })
            ->editColumn('from_device',function($data){
                $device=Device::where('device_id',$data->from_device)->first();
                return $device->name;
            })
           /* ->editColumn('course',function($data){
                $course=Course::where('course_id',$data->course)->first();
                if($course)
                    return $course->name;
            })*/
          /*  ->editColumn('department',function($data){
                $course=Department::where('department_id',$data->department)->first();
                if($course)
                    return $course->name;
            })*/
            ->editColumn('created_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->created_at));
            })
            ->editColumn('updated_at',function($data){
                return DATE('d-m-Y h:i A',strtotime($data->updated_at));
            })
            ->editColumn('to_device',function($data){
                $expl=explode(",",$data->to_device);
                $device=[];
                foreach($expl as $Data){
                    $device[]=Device::where('device_id',$Data)->first()->name;
                }
                return implode(", ",$device);
            })
          
         ->rawColumns(['action'])
        ->make(true);
    }

     public function store(Request $request){
        $clone=new ImportClone;
        $clone->from_device=$request->device;
        $clone->to_device=implode(",",$request->device_ids);
        $clone->type=1;
        $clone->save();
        return redirect()->route('clone.pending')->with('success', 'Clone student details successfully saved.Started will soon...');
    }

    public function cron(Request $request){
        return view('clone.cron');
    }

     public function crondetails(){

        $data=Cron::whereRaw('cron_type=5')->orderBy('cron_id','DESC');
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
