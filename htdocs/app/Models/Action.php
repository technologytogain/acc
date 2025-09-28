<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Action extends Model{
    use HasFactory;

    protected $table="role_action";
    protected $primaryKey = 'act_id';
    protected $fillable=['act_role'];

    public static function access($groupID){
        if(\Auth::user()->role_id==1)
            return true;
        else
            return Action::where('act_role',\Auth::user()->role_id)->where('act_group',$groupID)->count();
    }

    public static function chkaccess($routName){
        $masterID=ActionMaster::whereRaw('actmas_action_name="'.$routName.'" OR  FIND_IN_SET(actmas_action_name2,"'.$routName.'")  ')->first();
        if($masterID){
            $masterID=$masterID->actmas_id;
            if(\Auth::user()->role_id==1 && $routName !="principal.dashboard")
                return 1;
            else
                return Action::where('act_role',\Auth::user()->role_id)->where('act_masid',$masterID)->count();
        }else{
            return 0;
        }      
    }

    public static function menu($groupID){
        $menu='';

        if(\Auth::user()->role_id==1){
            $exclude_menu=['principal.dashboard'];
            $masData=ActionMaster::where('actmas_group_id',$groupID)->where('order','!=',0)->whereNotIn('actmas_action_name',$exclude_menu)->orderBy('order','ASC')->get();
            foreach($masData as $key => $Data) {
               $menu.='<li><a href='.route($Data->actmas_action_name).'><i class="fa fa-circle-o"></i> '.$Data->menu_name.'</a></li>';    
            }
       }else{

            $action=Action::where('act_role',\Auth::user()->role_id)->where('act_group',$groupID)->where('order','!=',0)->orderBy('order','ASC')->get();
            foreach($action as $key => $Data) {
                $masData=ActionMaster::find($Data->act_masid);
                if($masData)
                    $menu.='<li><a href='.route($masData->actmas_action_name).'><i class="fa fa-circle-o"></i> '.$masData->menu_name.'</a></li>';    
            }
        }

        return $menu;

        
    }

}
