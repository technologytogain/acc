<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Department;
use App\Models\Holidays;
use App\Models\Year;
use App\Models\Student;
use App\Models\AcademicYear;
use App\Exports\AttendanceExport;
use App\Exports\DailyAbsenceExport;
use App\Exports\PunchExport;
use App\Exports\MonthlyExport;
use App\Exports\ContinuousAbsenceExport;
use App\Components\Common;
use DataTables;

class ReportController extends Controller{

    public function punch(){
        return view('reports.punch');
    }


    public function punchdetails(Request $request){

        $qry=' 1';
        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;
        
        $data = Attendance::filter($request->all())->where('upgrade',0)->whereRaw('('.$qry.')')->orderBy('date', 'DESC');
        return DataTables::of($data)
            ->editColumn('student', function ($data){
                $student = Student::where('stud_id', $data->student)->first();
                if ($student) {
                    return $student->name;
                }

            })
            ->editColumn('date', function ($data){
                return DATE('d-m-Y',strtotime($data->date));
            })
            ->editColumn('p_one_time', function ($data){
                return Common::atttime($data,$data->p_one_time,$data->p_one,1);

            })
           ->editColumn('p_two_time', function ($data){
                return Common::atttime($data,$data->p_two_time,$data->p_two,2);
            })
           ->editColumn('p_three_time', function ($data){
                return Common::atttime($data,$data->p_three_time,$data->p_three,3);

            })
            ->editColumn('p_four_time', function ($data){
                return Common::atttime($data,$data->p_four_time,$data->p_four,4);

            })
            ->editColumn('p_five_time', function ($data){
                return Common::atttime($data,$data->p_five_time,$data->p_five,5);

            })
            ->editColumn('p_six_time', function ($data){
                return Common::atttime($data,$data->p_six_time,$data->p_six,6);

            })
            ->editColumn('p_seven_time', function ($data){
                return Common::atttime($data,$data->p_seven_time,$data->p_seven,7);

            })
            ->editColumn('p_eight_time', function ($data){
                return Common::atttime($data,$data->p_eight_time,$data->p_eight,8);

            })
            /*->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('year', function ($data) {
                $year = Year::where('year_id', $data->year)->first();
                if ($year) {
                    return $year->name;
                }

            }) */
            /*->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })*/
            //->rawColumns(['action','photo'])
            ->make(true);
    }

    public function punchdownload(Request $request){        

        \set_time_limit(0);

        $qry=' 1';

        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;

        $attendance=Attendance::whereRaw('('.$qry.')')->where('upgrade',0)->get();
        $dataSet=[];
        $inc=1;
        foreach($attendance as $key => $Data) {
            $student_name=Student::where('stud_id',$Data->student)->first()->name;
            //$course=Course::where('course_id',$Data->course)->first()->name;
            $one=$two=$three=$four=$five=$six=$seven=$eight="";
            $one=Common::atttime($Data,$Data->p_one_time,$Data->p_one,1);
            $two=Common::atttime($Data,$Data->p_two_time,$Data->p_two,2);
            $three=Common::atttime($Data,$Data->p_three_time,$Data->p_three,3);
            $four=Common::atttime($Data,$Data->p_four_time,$Data->p_four,4);
            $five=Common::atttime($Data,$Data->p_five_time,$Data->p_five,5);
            $six=Common::atttime($Data,$Data->p_six_time,$Data->p_six,6);
            $seven=Common::atttime($Data,$Data->p_seven_time,$Data->p_eight,7);
            $eight=Common::atttime($Data,$Data->p_nine_time,$Data->p_nine,8);
            $dataSet[]=[$inc,DATE('d-m-Y',strtotime($Data->date)),$Data->device_uniqueid,$Data->register_no,$student_name,$one,$two,$three,$four,$five,$six,$seven,$eight];
            
            $inc++;
        }
        
        if($request->course)
            $course=Course::where('course_id',$request->course)->first()->name;
        else
            $course="All";
        if($request->year)
            $year=Year::where('year_id',$request->year)->first()->name;
        else
            $year="All";
        if($request->academic_year)
            $academic_year=AcademicYear::where('academic_id',$request->academic_year)->first()->name;
        else
            $academic_year="All";
        if($request->department)
            $department=Department::where('department_id',$request->department)->first()->name;
        else
            $department="All";
        $extraData=['subtitle2'=>'Daily Punch Report - '.$request->date.' To : '.$request->to_date.' ','subtitle3'=>''.$course." - ".$department." - ".$academic_year.''];
        return \Excel::download(new PunchExport($dataSet,$extraData), 'dailypunch'.DATE('d-m-Y').'.xlsx');
    }



    public function present(){
        return view('reports.present');
    }


    public function presentdetails(Request $request){

        $qry=' 1';
        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;
        
        $data = Attendance::filter($request->all())->where('upgrade',0)->whereRaw('('.$qry.')')->whereRaw(' ( p_one=1 OR p_one=2 )')->orderBy('date', 'DESC');
        return DataTables::of($data)
            ->editColumn('student', function ($data){
                $student = Student::where('stud_id', $data->student)->first();
                if ($student) {
                    return $student->name;
                }

            })
            ->editColumn('date', function ($data){
                return DATE('d-m-Y',strtotime($data->date));
            })
            ->editColumn('p_one_time', function ($data){
                return Common::atttime($data,$data->p_one_time,$data->p_one,1);

            })
           ->editColumn('p_two_time', function ($data){
                return Common::atttime($data,$data->p_two_time,$data->p_two,2);
            })
           ->editColumn('p_three_time', function ($data){
                return Common::atttime($data,$data->p_three_time,$data->p_three,3);

            })
            ->editColumn('p_four_time', function ($data){
                return Common::atttime($data,$data->p_four_time,$data->p_four,4);

            })
            ->editColumn('p_five_time', function ($data){
                return Common::atttime($data,$data->p_five_time,$data->p_five,5);

            })
            ->editColumn('p_six_time', function ($data){
                return Common::atttime($data,$data->p_six_time,$data->p_six,6);

            })
            ->editColumn('p_seven_time', function ($data){
                return Common::atttime($data,$data->p_seven_time,$data->p_seven,7);

            })
            ->editColumn('p_eight_time', function ($data){
                return Common::atttime($data,$data->p_eight_time,$data->p_eight,8);

            })
            /*->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('year', function ($data) {
                $year = Year::where('year_id', $data->year)->first();
                if ($year) {
                    return $year->name;
                }

            }) */
            /*->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })*/
            //->rawColumns(['action','photo'])
            ->make(true);
    }

    public function presentdownload(Request $request){        

        \set_time_limit(0);

        $qry=' 1';

        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;

        $attendance=Attendance::whereRaw('('.$qry.')')->whereRaw(' ( p_one=1 OR p_one=2 ) ')->where('upgrade',0)->get();
        $dataSet=[];
        $inc=1;
        foreach($attendance as $key => $Data) {
            $student_name=Student::where('stud_id',$Data->student)->first()->name;
            //$course=Course::where('course_id',$Data->course)->first()->name;
            $one=$two=$three=$four=$five=$six=$seven=$eight="";
            $one=Common::atttime($Data,$Data->p_one_time,$Data->p_one,1);
            $two=Common::atttime($Data,$Data->p_two_time,$Data->p_two,2);
            $three=Common::atttime($Data,$Data->p_three_time,$Data->p_three,3);
            $four=Common::atttime($Data,$Data->p_four_time,$Data->p_four,4);
            $five=Common::atttime($Data,$Data->p_five_time,$Data->p_five,5);
            $six=Common::atttime($Data,$Data->p_six_time,$Data->p_six,6);
            $seven=Common::atttime($Data,$Data->p_seven_time,$Data->p_eight,7);
            $eight=Common::atttime($Data,$Data->p_nine_time,$Data->p_nine,8);
            $dataSet[]=[$inc,DATE('d-m-Y',strtotime($Data->date)),$Data->device_uniqueid,$Data->register_no,$student_name,$one,$two,$three,$four,$five,$six,$seven,$eight];
            
            $inc++;
        }

        //dd($dataSet);
        
        if($request->course)
            $course=Course::where('course_id',$request->course)->first()->name;
        else
            $course="All";
        if($request->year)
            $year=Year::where('year_id',$request->year)->first()->name;
        else
            $year="All";
        if($request->academic_year)
            $academic_year=AcademicYear::where('academic_id',$request->academic_year)->first()->name;
        else
            $academic_year="All";
        if($request->department)
            $department=Department::where('department_id',$request->department)->first()->name;
        else
            $department="All";
        $extraData=['subtitle2'=>'Daily Punch Report - '.$request->date.' To : '.$request->to_date.' ','subtitle3'=>''.$course." - ".$department." - ".$academic_year.''];
        return \Excel::download(new PunchExport($dataSet,$extraData), 'dailypunch'.DATE('d-m-Y').'.xlsx');
    }


    public function dailyabsence(){
        return view('reports.dailyabsence');
    }

    public function dailyabsencedetails(Request $request){

        $qry=' 1';

        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;

        $data = Attendance::filter($request->all())->whereRaw('('.$qry.')')->where('upgrade',0)->whereRaw('( p_one=0 )')->orderBy('attendance_id', 'DESC');
        return DataTables::of($data)
            ->editColumn('student', function ($data){
                $student = Student::where('stud_id', $data->student)->first();
                if ($student) {
                    return $student->name;
                }

            })
            ->editColumn('date', function ($data){
                return DATE('d-m-Y',strtotime($data->date));
            })
            ->editColumn('p_one_time', function ($data){
                return Common::atttime($data,$data->p_one_time,$data->p_one,1);

            })
           ->editColumn('p_two_time', function ($data){
                return Common::atttime($data,$data->p_two_time,$data->p_two,2);
            })
           ->editColumn('p_three_time', function ($data){
                return Common::atttime($data,$data->p_three_time,$data->p_three,3);

            })
            ->editColumn('p_four_time', function ($data){
                return Common::atttime($data,$data->p_four_time,$data->p_four,4);

            })
            ->editColumn('p_five_time', function ($data){
                return Common::atttime($data,$data->p_five_time,$data->p_five,5);

            })
            ->editColumn('p_six_time', function ($data){
                return Common::atttime($data,$data->p_six_time,$data->p_six,6);

            })
            ->editColumn('p_seven_time', function ($data){
                return Common::atttime($data,$data->p_seven_time,$data->p_seven,7);

            })
            ->editColumn('p_eight_time', function ($data){
                return Common::atttime($data,$data->p_eight_time,$data->p_eight,8);

            })
            /*->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('year', function ($data) {
                $year = Year::where('year_id', $data->year)->first();
                if ($year) {
                    return $year->name;
                }

            }) */
            /*->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })*/
            //->rawColumns(['action','photo'])
            ->make(true);
    }

    public function dailyabsencedownload(Request $request){

        \set_time_limit(0);

        $qry=' 1';

        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;

       $attendance=Attendance::where('upgrade',0)->whereRaw('('.$qry.')')->whereRaw(' ( p_one=0 ) ')->get();
        $dataSet=[];
        $inc=1;
        foreach($attendance as $key => $Data) {
            $student_name=Student::where('stud_id',$Data->student)->first()->name;
            //$course=Course::where('course_id',$Data->course)->first()->name;
            $one=$status="";
            $one=$two=$three=$four=$five=$six=$seven=$eight="";
            $one=Common::atttime($Data,$Data->p_one_time,$Data->p_one,1);
            $two=Common::atttime($Data,$Data->p_two_time,$Data->p_two,2);
            $three=Common::atttime($Data,$Data->p_three_time,$Data->p_three,3);
            $four=Common::atttime($Data,$Data->p_four_time,$Data->p_four,4);
            $five=Common::atttime($Data,$Data->p_five_time,$Data->p_five,5);
            $six=Common::atttime($Data,$Data->p_six_time,$Data->p_six,6);
            $seven=Common::atttime($Data,$Data->p_seven_time,$Data->p_eight,7);
            $eight=Common::atttime($Data,$Data->p_nine_time,$Data->p_nine,8);
            $dataSet[]=[$inc,DATE('d-m-Y',strtotime($Data->date)),$Data->device_uniqueid,$Data->register_no,$student_name,$one,$two,$three,$four,$five,$six,$seven,$eight];
            $inc++;
        }

        if($request->course)
            $course=Course::where('course_id',$request->course)->first()->name;
        else
            $course="All";
        if($request->year)
            $year=Year::where('year_id',$request->year)->first()->name;
        else
            $year="All";
        if($request->academic_year)
            $academic_year=AcademicYear::where('academic_id',$request->academic_year)->first()->name;
        else
            $academic_year="All";
        if($request->department)
            $department=Department::where('department_id',$request->department)->first()->name;
        else
            $department="All";

        $extraData=['subtitle2'=>'Daily Absence Report - '.$request->date.' To : '.$request->to_date.' ','subtitle3'=>''.$course." - ".$department." - ".$academic_year.' '];
        return \Excel::download(new DailyAbsenceExport($dataSet,$extraData), 'dailyabsence'.DATE('d-m-Y').'.xlsx');

    }

    public function continuousabsence(){
        return view('reports.continuousabsence');
    }

    public function continuousabsencedetails(Request $request){

         $qry=" 1";

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;
        if($request->from_date && !$request->to_date)
            $qry.= " AND date >= '".DATE('Y-m-d',strtotime($request->from_date))."'"; 
        if(!$request->from_date && $request->to_date)
            $qry.= " AND date =< '".DATE('Y-m-d',strtotime($request->to_date))."'";
        if($request->from_date && $request->to_date)
            $qry.= " AND  ( date >= '".DATE('Y-m-d',strtotime($request->from_date))."' AND date <= '".DATE('Y-m-d',strtotime($request->to_date))."' ) "; 

        $data = Attendance::whereRaw("($qry)")->where('upgrade',0)->whereRaw(' ( p_one=0 ) ')->orderBy('student','ASC')->orderBy('date','ASC');
        return DataTables::of($data)
            ->editColumn('student', function ($data){
                $student = Student::where('stud_id', $data->student)->first();
                if ($student) {
                    return $student->name;
                }

            })
            ->editColumn('date', function ($data){
                return DATE('d-m-Y',strtotime($data->date));
            })  
            ->editColumn('p_one', function ($data){
                if($data->p_one==0) 
                    return "Absent";
                elseif($data->p_one==2) 
                    return "Absent ( Late )";

            })
            ->editColumn('p_one_time', function ($data){
                return ($data->p_one_time && $data->p_one_time !='00:00:00') ? DATE('h:i A',strtotime($data->p_one_time)) : '';

            })
            ->make(true);
    }

    public function continuousabsencedownload(Request $request){

        \set_time_limit(0);

        $qry=" 1";

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;
        if($request->from_date && !$request->to_date)
            $qry.= " AND date >= '".DATE('Y-m-d',strtotime($request->from_date))."'"; 
        if(!$request->from_date && $request->to_date)
            $qry.= " AND date =< '".DATE('Y-m-d',strtotime($request->to_date))."'";
        if($request->from_date && $request->to_date)
            $qry.= " AND  ( date >= '".DATE('Y-m-d',strtotime($request->from_date))."' AND date <= '".DATE('Y-m-d',strtotime($request->to_date))."' ) "; 

       $attendance=Attendance::whereRaw("($qry)")->where('upgrade',0)->whereRaw(' ( p_one=0 ) ')->orderBy('student','ASC')->orderBy('date','ASC')->get();
        $dataSet=[];
        $exst=""; $inc=1;
        foreach($attendance as $key => $Data) {
            $student_name=Student::where('stud_id',$Data->student)->first()->name;
            //$course=Course::where('course_id',$Data->course)->first()->name;
            $one=$status=$date="";
            if($Data->date !="00:00:00") $date=DATE('d-m-Y',strtotime($Data->date));
            if($Data->p_one_time && $Data->p_one_time !="00:00:00") $one=DATE('h:i A',strtotime($Data->p_one_time));
            if($Data->p_one==0) $status="Absent"; elseif($Data->p_one==2)  $status="Absent ( Late )";

            if($exst==$Data->student){
                $stud_name="";
                $reg_no="";
            }else{
                $stud_name=$student_name;
                $reg_no=$Data->register_no;
            }

            $dataSet[]=[$inc,$reg_no,$stud_name,$date,$one,$status];
            
            $exst=$Data->student;
            $inc++;
        }

         if($request->course)
            $course=Course::where('course_id',$request->course)->first()->name;
        else
            $course="All";
        if($request->year)
            $year=Year::where('year_id',$request->year)->first()->name;
        else
            $year="All";
        if($request->academic_year)
            $academic_year=AcademicYear::where('academic_id',$request->academic_year)->first()->name;
        else
            $academic_year="All";
        if($request->department)
            $department=Department::where('department_id',$request->department)->first()->name;
        else
            $department="All";

        $extraData=['subtitle2'=>'Continuous Absence Report - '.$request->from_date.' To : '.$request->to_date.' ','subtitle3'=>''.$course." - ".$department." - ".$academic_year.' '];
        return \Excel::download(new ContinuousAbsenceExport($dataSet,$extraData), 'continuousabsence'.DATE('d-m-Y').'.xlsx');

    }


     public function monthly(Request $request){

        $qry=' 1';
        if($request->student)
            $qry.=' AND student='.$request->student;
        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
        if($request->year)
            $qry.=' AND year='.$request->year;

        $attendance=Attendance::select('student')->whereRaw('DATE_FORMAT(date,"%m-%Y") ="'.$request->monthyear.'" ')->whereRaw('('.$qry.')')->groupBy('student')->paginate(10);
        return view('reports.monthly',compact('attendance'));
    }


    public function monthlydetails(Request $request){

        $qry=' 1';
        if($request->date && $request->to_date){
            $qry='date  BETWEEN "'.DATE('Y-m-d',strtotime($request->date)).'" AND "'.DATE('Y-m-d',strtotime($request->to_date)).'" ';
        }elseif($request->date && !$request->to_date){
            $qry='date  >= "'.DATE('Y-m-d',strtotime($request->date)).'"';
        }elseif(!$request->date && $request->to_date){
            $qry='date  <= "'.DATE('Y-m-d',strtotime($request->to_date)).'"';
        }
        
        $data = Attendance::filter($request->all())->where('upgrade',0)->whereRaw('('.$qry.')')->orderBy('date', 'DESC');
        return DataTables::of($data)
            ->editColumn('student', function ($data){
                $student = Student::where('stud_id', $data->student)->first();
                if ($student) {
                    return $student->name;
                }

            })
            ->editColumn('date', function ($data){
                return DATE('d-m-Y',strtotime($data->date));
            })
            ->editColumn('p_one_time', function ($data){
                return Common::atttime($data,$data->p_one_time,$data->p_one,1);

            })
           ->editColumn('p_two_time', function ($data){
                return Common::atttime($data,$data->p_two_time,$data->p_two,2);
            })
           ->editColumn('p_three_time', function ($data){
                return Common::atttime($data,$data->p_three_time,$data->p_three,3);

            })
            ->editColumn('p_four_time', function ($data){
                return Common::atttime($data,$data->p_four_time,$data->p_four,4);

            })
            ->editColumn('p_five_time', function ($data){
                return Common::atttime($data,$data->p_five_time,$data->p_five,5);

            })
            ->editColumn('p_six_time', function ($data){
                return Common::atttime($data,$data->p_six_time,$data->p_six,6);

            })
            ->editColumn('p_seven_time', function ($data){
                return Common::atttime($data,$data->p_seven_time,$data->p_seven,7);

            })
            ->editColumn('p_eight_time', function ($data){
                return Common::atttime($data,$data->p_eight_time,$data->p_eight,8);

            })
            /*->editColumn('course', function ($data) {
                $course = Course::where('course_id', $data->course)->first();
                if ($course) {
                    return $course->name;
                }

            })
            ->editColumn('department', function ($data) {
                $department = Department::where('department_id', $data->department)->first();
                if ($department) {
                    return $department->name;
                }

            })
            ->editColumn('year', function ($data) {
                $year = Year::where('year_id', $data->year)->first();
                if ($year) {
                    return $year->name;
                }

            }) */
            /*->editColumn('photo', function ($data) {
                if($data->photo)
                    return '<img src="../uploads/studentphoto/'.$data->photo.'" style="width:50px;">';

            })*/
            //->rawColumns(['action','photo'])
            ->make(true);
    }

    public function monthlydownload(Request $request){        

        \set_time_limit(0);

        $qry=' 1';

        if($request->course)
            $qry.=' AND course='.$request->course;
        if($request->department)
            $qry.=' AND department='.$request->department;
        if($request->academic_year)
            $qry.=' AND academic_year='.$request->academic_year;
         if($request->student)
            $qry.=' AND student='.$request->student;
        if($request->year)
            $qry.=' AND year='.$request->year;

        $title_set=[];
        $title_set=['Sr.No','Device UID','Register No','Student Name'];
            $total_day=DATE('t',strtotime("01-".$_GET['monthyear']));
            for($day=1;$day<=$total_day;$day++){
                    $title_set[]=$day;
            }
        $title_set[]='Holiday';
        $title_set[]='Present Days';
        $title_set[]='Half Days';
        $title_set[]='Absent Days';
        $title_set[]='Working Days';
        $title_set[]='Percentage (%)';

        $attendance=Attendance::select('student')->whereRaw('DATE_FORMAT(date,"%m-%Y") ="'.$request->monthyear.'" ')->where('upgrade',0)->whereRaw($qry)->groupBy('student')->get();

        $dataSet=$bottom=$title=$put=[];
        $inc=1;
        $total_day=DATE('t',strtotime("01-".$_GET['monthyear']));
        foreach($attendance as $Data){
            $student=Student::select('name','register_no','device_uniqueid','current_year')->find($Data->student);
                    array_push($put,$inc);
                    array_push($put,$student->device_uniqueid);
                    array_push($put,$student->register_no);
                    array_push($put,$student->name);
                    $present=$absent=$half_present=$holiday=$sunday=0;  
                    for($day=1;$day<=$total_day;$day++){
                            $date=DATE('Y-m-d',strtotime($day."-".$_GET['monthyear']));
                            $att=Attendance::select('attendance')->where('date',$date)->where('student',$Data->student)->first();
                            $holiday_qry=Holidays::where('date',$date)->where('status',1)->first();
                            $word="A";
                            if(DATE('N',strtotime($date))==7){
                                $word="S";
                                $sunday++;
                            }elseif($holiday_qry && $holiday_qry->year=="777"){
                                $word="H";
                                $holiday++;
                            }elseif($holiday_qry && in_array($student->current_year, explode(",",$holiday_qry->year))){
                                $word="H";
                                $holiday++;
                            }elseif($att){
                                if($att->attendance == 1 ){
                                    $word="HP";
                                    $half_present++;
                                }elseif($att->attendance > 1){
                                    $word="P";
                                    $present++;
                               }elseif(strtotime($date) <= strtotime(DATE('Y-m-d')) ){
                                    $word="A";
                                    $absent++;
                                }else{
                                    $word="-";
                                }
                            }else{
                                if(strtotime($date) <= strtotime(DATE('Y-m-d')) )
                                    $absent++;
                                else
                                    $word="-";
                            }
                            array_push($put,$word);
                    }
                $tot_present=$present+($half_present/2);
                $tot_holidays=$holiday+$sunday;
                $working_days=$total_day-$tot_holidays;
                $percentage=($tot_present/$working_days)*100;
                array_push($put,$holiday);
                array_push($put,$present);
                array_push($put,$half_present);
                array_push($put,$absent);
                array_push($put,$working_days);
                array_push($put,round($percentage,2));
            $inc++;
            $dataSet[]=$put;
            $put=[];
        }
        //dd($dataSet);

        
        if($request->course)
            $course=Course::where('course_id',$request->course)->first()->name;
        else
            $course="All";
        if($request->year)
            $year=Year::where('year_id',$request->year)->first()->name;
        else
            $year="All";
        if($request->academic_year)
            $academic_year=AcademicYear::where('academic_id',$request->academic_year)->first()->name;
        else
            $academic_year="All";
        if($request->department)
            $department=Department::where('department_id',$request->department)->first()->name;
        else
            $department="All";

        $headings=[ ['ACS MEDICAL COLLEGE'],['subtitle2'=>'Monthly Punch Report - '.$request->date.' To : '.$request->to_date],[''.$course." - ".$department." - ".$academic_year.' '], $title_set ];
        $extraData=['headings'=>$headings];
        return \Excel::download(new MonthlyExport($dataSet,$extraData), 'monthlyPunch'.DATE('d-m-Y').'.xlsx');
    }

}
