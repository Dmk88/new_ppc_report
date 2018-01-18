<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GAReportsPosts extends Model
{
    public function clusters()
    {
        return $this->belongsToMany(GAReportsCluster::class, 'cluster_post', 'post_id', 'cluster_id')
            ->withTimestamps();
    }
    
    public function setCluster($cluster, $value){
        if($value){
            $this->clusters()->attach($cluster);
        }else{
            $this->clusters()->detach($cluster);
        }
    }
}
