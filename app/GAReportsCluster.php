<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GAReportsCluster extends Model
{
    public function posts()
    {
        return $this->belongsToMany(GAReportsPosts::class, 'cluster_post', 'cluster_id', 'post_id')->withTimestamps();
    }
}
