<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subjects;
use App\Models\Student;
use DataTables;

class ExpeController extends Controller{
    
    public function index(Request $request){
        set_time_limit(0);
        
        //$obj=\Artisan::call('sync:log');

        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET','https://sms.messagewall.in/api/v2/sms/send', [
        'query'=>['access_token'=>'b70a509da9c88b0ca98cdf855d1d5e4c','to'=>'8807446505','message'=>'Your kid syed has boarded the school bus at 10:23 on 25-12-2022 Onmessage','service'=>'T','sender'=>'ONMSGG','template_id'=>'1707165958782884789']
        ]);
         $statusCode=$response->getStatusCode();
        $content=$response->getBody()->getContents();
        dd($content);
      //  $WshShell = new \COM("WScript.Shell");
        //$obj = $WshShell->Run("cscript C:\AutoScriptRunner\synclog.vbs", 0, true); 

        var_dump($obj);
     /*   Student::where('photo','')->update(['photo'=>'20220917130943.jpg']);

      $inc=10000; 
       for ($i=10007; $i < 10020; $i++) { 
            $insert=new Student;
            $insert->first_name="Student";
            $insert->last_name=$i;
            $insert->name=$insert->first_name." ".$insert->last_name;
            $insert->father_name="";
            $insert->dob="0000-00-00";
            $insert->blood_group=rand(1,2);
            $insert->register_no="MDS".str_pad($inc,6,0,STR_PAD_LEFT);
            $insert->contact_no=rand(6666666666,9999999999);
            $insert->academic_year="2022-23";
            $insert->photo="";
            $insert->email="";
            $insert->state=1;
            $insert->address="Trichy";
            $insert->course=1;
            $insert->department=2;
            $insert->current_year=3;
            $insert->gender=rand(0,1);
            $insert->device=8;
            $insert->save();
            $inc++;
        }*/
    }
}
