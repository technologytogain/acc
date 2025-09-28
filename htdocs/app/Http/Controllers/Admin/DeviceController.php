<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\ClassRoom;
use App\Models\Action;
use App\Components\DeviceConfig;
use DataTables;
use App\Components\Common;

class DeviceController extends Controller{

    public function index(){
        return view('device.index');
    }
    
    public function add(Request $request){
        return view('device.add');
    }
    public function store(Request $request){

        $request->validate([
                'name'=>'required',
                'ip'=>'required',
                'room'=>'required',
                //'type'=>'required',
                'protocol'=>'required',
                'port'=>'required',
                'username'=>'required',
                'password'=>'required'
            ],
            [
                'name.required'=>'Name field is required.',
               // 'type.required'=>'Device Type field is required.',
                'ip.required'=>'IP field is required.',
                'protocol.required'=>'Protocol field is required.',
                'port.required'=>'Port field is required.',
                'username.required'=>'Username field is required.',
                'password.required'=>'Password field is required.',
                'room.required'=>'Class Room field is required.'

            ]
        );


        $input = $request->all();

        try {

            // $data=DeviceConfig::addDevice($request);           
            // $deviceInfo             = $data->SearchResult->MatchList[0]->Device;
            // $input['model']         = $deviceInfo->devMode;
            // $input['device_status'] = $deviceInfo->devStatus;
            // $input['devIndex'] = $deviceInfo->devIndex;
            // if($input['device_status']=="online")
            //     $input['verification_status'] = 1;


            $input['model']         = " ";
            $input['device_status'] = "online";
            $input['devIndex'] = " ";
            if($input['device_status']=="online")
                $input['verification_status'] = 1;

            $input['created_at'] = DATE('Y-m-d H:i:s');
            $input['updated_at'] = DATE('Y-m-d H:i:s');
            $device=Device::create($input);

            return  redirect()->route('device')->with('success', 'Devices successfully saved.');
        }catch(\Exception $e) {
            //dd($e);
            return \redirect()->back()->with('error','Device not found or Authentication Failed');
        }

        
    }

    public function details(){

     $data=Device::where('status',1)->orderBy('device_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            $return="";
       
            if($data->device_status=="online" && Action::chkaccess('device.edit'))    
                $return.='<a href="'.route('device.edit',['id'=>$data->device_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->device_id . '"><i class="fa fa-edit"></i></a>';
            
            if($data->device_status=="online" && Action::chkaccess('device.delete'))    
                $return.='<a href="#" class="btn btn-xs btn-danger" title="Delete" data-id="' . $data->device_id . '"><i class="fa fa-trash"></i></a>';
       
            // if($data->device_status=="online" && Action::chkaccess('access.controll'))
            //     $return.=' <a href="'.route('access.controll',['id'=>$data->device_id]).'" class="btn btn-xs btn-warning" title="Access Controll" data-id="' . $data->device_id . '"><i class="fa fa-check-square-o"></i></a>';
       
            // if($data->device_status=="online" && Action::chkaccess('clone.form'))
            //     $return.=' <a href="'.route('clone.form',['id'=>$data->device_id]).'" class="btn btn-xs btn-info" title="Student Clone to Another Device" data-id="' . $data->device_id . '"><i class="fa fa-clone"></i></a>';

            // if($data->device_status=="online" && Action::chkaccess('import.store'))
            //     $return.=' <a href="'.route('import.store',['id'=>$data->device_id]).'" class="btn btn-xs btn-danger" title="Import Student Details" data-id="' . $data->device_id . '"><i class="fa fa-download"></i></a>';
           return $return;
       })
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })  
      ->editColumn('room',function($data){
         return ClassRoom::where('room_id',$data->room)->first()->name;
      }) 
      ->editColumn('updated_at',function($data){
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      })
      ->rawColumns(['action'])
      ->make(true);
    }

    public function edit(Request $request){
        $device=Device::find($request->id);
        return view('device.edit',['post'=>$device]);
    }

    public function update(Request $request){
           $request->validate([
                'name'=>'required',
                'ip'=>'required',
                'protocol'=>'required',
                'room'=>'required',
                //'type'=>'required',
                'port'=>'required',
                'status'=>'required',
                'username'=>'required',
                'password'=>'required'
            ],
            [
                'name.required'=>'Name field is required.',
                //'type.required'=>'Device Type field is required.',
                'ip.required'=>'IP field is required.',
                'protocol.required'=>'Protocol field is required.',
                'port.required'=>'Port field is required.',
                'status.required'=>'Status field is required',
                'username.required'=>'Username field is required',
                'password.required'=>'Password field is required',
                'room.required'=>'Class Room field is required.'

            ]
        );

        $device = Device::findOrFail($_GET['id']);
        $input = $request->all();
        try {
            
            $input['devIndex']      = $device->devIndex;
            // $data=DeviceConfig::editDevice($input);           
            // $deviceInfo             = $data->SearchResult->MatchList[0]->Device;
            // $input['model']         = $deviceInfo->devMode;
            // $input['device_status'] = $deviceInfo->devStatus;
            $input['updated_at'] = DATE('Y-m-d H:i:s');
            $device->update($input);

            return  redirect()->route('device')->with('success', 'Devices successfully updated.');
        }catch(\Exception $e) {
            dd($e);
            return \redirect()->back()->with('error','Something went wrong !');
        }
    }


    public function refresh(){

        $check_device=DeviceConfig::restartdevice();
        $check_device=json_decode($check_device);
        if($check_device->status=="all_offline_check_cable"){
            return redirect()->back()->with('error',$check_device->msg);
        }elseif(isset($check_device->offline_device) && $check_device->offline_device){
            return redirect()->back()->with('error',$check_device->msg);
        }else{
            return redirect()->back()->with('success', 'Devices status refreshed successfully ! ');
        }
    }


    
}
