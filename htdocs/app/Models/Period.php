<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    use HasFactory;

    protected $table="m_period";
    protected $primaryKey = 'period_id';

     public function listData(){
        $period=Period::where('status',1)->orderBy('from_time','ASC')->get();
        $set=[];
        foreach($period as $key => $Data) {
           $set[$Data->period_id]=DATE('h:i A',strtotime($Data->from_time))."-".DATE('h:i A',strtotime($Data->to_time));
        }

        return $set;
    }

    public function filterData($course,$department,$year){
        $period=Period::where('status',1)->where('course',$course)->where('department',$department)->where('year',$year)->orderBy('from_time','ASC')->get();
        $set=[];
        foreach($period as $key => $Data) {
           $set[$Data->period_id]=DATE('h:i A',strtotime($Data->from_time))."-".DATE('h:i A',strtotime($Data->to_time));
        }

        return $set;
    }

}
