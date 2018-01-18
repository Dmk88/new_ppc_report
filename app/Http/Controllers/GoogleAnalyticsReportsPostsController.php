<?php

namespace App\Http\Controllers;

use App\GAReportsPosts;
use Illuminate\Http\Request;

// use App\ExclusionType;
// use App\FormData;
// use App\GoogleDoc;
// use App\GAReportsCluster;

class GoogleAnalyticsReportsPostsController extends Controller
{
    public function change_post_cluster(Request $request)
    {
        $message = 'error';
        $data    = json_decode($request->getContent());
        if (empty($request->id) || empty($data->cluster) || !isset($data->value)) {
            return $message;
        }
        $post = GAReportsPosts::whereId($request->id)->first();
        
        $post->setCluster($data->cluster, $data->value);
        $message = 'success';
        
        return $message;
    }
    
    public function grab_posts(Request $request)
    {

    }
}
