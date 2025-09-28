<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Templates;
use App\Models\Action;
use DataTables;

class TemplatesController extends Controller{
    
    public function index(){
    	return view('templates.index');
    }
    
    public function add(Request $request){
    	return view('templates.add');
    }
    public function store(Request $request){

    	$request->validate([
                'subject'=>'required',
                'content'=>'required',
            ],
            [
                'subject.required'=>'Subject field is required.',
                'content.required'=>'Content field is required.',
            ]
        );
        $templates=new Templates;
        $templates->subject=$request->subject;
        $templates->content=$request->content;
        $templates->status=1;
        $templates->created_at=DATE('Y-m-d H:i:s');
        $templates->updated_at=DATE('Y-m-d H:i:s');
        $templates->save();

        return redirect()->route('templates')->with('success','Templates details successfully saved !');;;
    }

    public function details(){

     $data=Templates::where('status','!=',2)->orderBy('template_id','DESC');
      return DataTables::of($data)
      ->addColumn('action', function ($data) {
            if(Action::chkaccess('templates.edit'))
               return '<a href="'.route('templates.edit',['id'=>$data->template_id]).'" class="btn btn-xs btn-primary" title="Edit" data-id="' . $data->template_id . '"><i class="fa fa-edit"></i></a>';
       })
      ->editColumn('updated_at',function($data){
        if($data->updated_at && $data->updated_at !="0000-00-00 00:00:00")
            return DATE('d-m-Y h:i A',strtotime($data->updated_at));
      })
      ->editColumn('content',function($data){
            return '<span title="'.$data->content.'">'.substr_replace($data->content, "...", 30).'</span>';
      })
      ->editColumn('status',function($data){
        if($data->status==1)
            return "Active";
        else
            return "In Active";
      })
      ->rawColumns(['action','content'])
      ->make(true);
    }

    public function edit(Request $request){
        $template=Templates::find($request->id);
        return view('templates.edit',['post'=>$template]);
    }

    public function update(Request $request){
            $request->validate([
               'subject'=>'required',
                'content'=>'required',
                'status'=>'required'
            ],
            [
                'subject.required'=>'Subject field is required.',
                'content.required'=>'Content field is required.',
                'status.required'=>'Status field is required.',
            ]
        );

        $template=Templates::find($request->id);
        $template->subject=$request->subject;
        $template->content=$request->content;
        $template->status=$request->status;
        $template->updated_at=DATE('Y-m-d H:i:s');
        $template->save();

        return redirect()->route('templates')->with('success','Templates details successfully updated !');;
    }

    public function search(Request $request){
        $qry=Templates::where('template_id',$request->id)->first();
        
        if($qry)
            echo $qry->content;

        exit;
    }

}
