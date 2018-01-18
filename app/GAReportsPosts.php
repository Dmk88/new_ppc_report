<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class GAReportsPosts extends Model
{
    private static $rules = array(
        'post_wp_id' => 'required|unique:g_a_reports_posts',
        'post_name'  => 'required',
        'post_url'   => 'required',
    );
    
    public function clusters()
    {
        return $this->belongsToMany(GAReportsCluster::class, 'cluster_post', 'post_id', 'cluster_id')->withTimestamps();
    }
    
    public function setCluster($cluster, $value)
    {
        if ($value) {
            $this->clusters()->attach($cluster);
        } else {
            $this->clusters()->detach($cluster);
        }
    }
    
    public static function validate($data)
    {
        $v = Validator::make($data, self::$rules);
        
        return $v->passes();
    }
}
