<?php

namespace App\Http\Controllers;

use App\GAReportsCluster;
use Illuminate\Http\Request;

// use App\ExclusionType;
// use App\FormData;
// use App\GoogleDoc;
// use App\GAReportsCluster;

class GoogleAnalyticsReportsClustersController extends Controller
{
    public function index()
    {
        $clusters = GAReportsCluster::all();
        
        return view('ga_reports.clusters.index', [
            'clusters' => $clusters,
        ]);
    }
    
    public function show_for_edit(Request $request)
    {
        $cluster = GAReportsCluster::whereId($request->id)->first();
        
        return view('ga_reports.clusters.edit', [
            'cluster' => $cluster,
        ]);
    }
    
    public function show_add_form(Request $request)
    {
        return view('ga_reports.clusters.cluster_form_add');
    }
    
    public function edit(Request $request)
    {
        $this->validate($request, [
            'cluster_description' => 'max:150',
            'cluster_name'        => 'required|max:50',
        ]);
        $cluster                      = GAReportsCluster::whereId($request->id)->first();
        $cluster->cluster_name        = $request->cluster_name;
        $cluster->cluster_description = $request->cluster_description;
        $cluster->save();
        
        return redirect('/ga_reports_clusters');
    }
    
    public function add(Request $request)
    {
        $this->validate($request, [
            'cluster_description' => 'max:150',
            'cluster_name'        => 'required|max:50',
        ]);
        
        $cluster                      = new GAReportsCluster();
        $cluster->cluster_name        = $request->cluster_name;
        $cluster->cluster_description = $request->cluster_description;
        $cluster->save();
        
        return redirect('/ga_reports_clusters');
    }
    
    public function delete(Request $request)
    {
        $cluster = GAReportsCluster::whereId($request->id)->first();
        $cluster->delete();
        
        return redirect('/ga_reports_clusters');
    }
    
}
