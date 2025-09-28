<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AcademicYear;
use App\Models\Action;
use App\Models\ActionMaster;
use App\Models\GroupMaster;
use DataTables;

class RoleController extends Controller{
	
	public function index(){
		return view('role.index');
	}
	
	public function add(Request $request){
		return view('role.form');
	}

	public function store(Request $request){
		
		$role_id = $request->role;
		$remove_access = Action::where('act_role',$role_id)->where('act_usertype',0)->delete();

		$actionMaster=GroupMaster::orderBy('rogroup_master_id','ASC')->get();

		foreach($actionMaster as $key => $value){
			$tdata="act_".str_replace(' ','',strtolower($value->rogroup_master_name));
			//dd($request->$tdata);
			if(is_array($request->$tdata)){
				foreach($request->$tdata as $key){
					$common1[]=$key;
					$vendor_c = new Action;
					$vendor_c->act_masid = $key;
					$actionid = ActionMaster::find($key);
					$vendor_c->act_group = $actionid->actmas_group_id;
					$vendor_c->act_role =$role_id;
					$vendor_c->order=$actionid->order;
					$vendor_c->save();
				}
			}
		}

		return \redirect()->back()->with('success','Role Successfully updated !');

	}

}
