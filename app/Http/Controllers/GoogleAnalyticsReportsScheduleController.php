<?php

namespace App\Http\Controllers;

use App\GAReportsSchedule;
use Illuminate\Http\Request;

class GoogleAnalyticsReportsScheduleController extends Controller
{
    public function index()
    {
        $schedules = GAReportsSchedule::all();
        
        return view('ga_reports.schedules.index', [
            'schedules' => $schedules,
        ]);
    }
    
    public function show_add_form(Request $request)
    {
        return view('ga_reports.schedules.schedule_form_add');
    }
    
    public function add(Request $request)
    {
        $this->validate($request, [
            'schedule_text' => 'required|max:250',
        ]);
        
        $schedule                = new GAReportsSchedule();
        $schedule->schedule_text = $request->schedule_text;
        $schedule->save();
        
        return redirect('/ga_reports/schedules');
    }
    
    public function show_for_edit(Request $request)
    {
        $schedule = GAReportsSchedule::whereId($request->id)->first();
        
        return view('ga_reports.schedules.edit', [
            'schedule' => $schedule,
        ]);
    }
    
    public function edit(Request $request)
    {
        $this->validate($request, [
            'schedule_text' => 'required|max:250',
        ]);
        $schedule                = GAReportsSchedule::whereId($request->id)->first();
        $schedule->schedule_text = $request->schedule_text;
        $schedule->save();
        
        return redirect('/ga_reports/schedules');
    }
    
    public function delete(Request $request)
    {
        $schedule = GAReportsSchedule::whereId($request->id)->first();
        $schedule->delete();
        
        return redirect('/ga_reports/schedules');
    }
}
