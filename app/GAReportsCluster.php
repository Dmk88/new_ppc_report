<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GAReportsCluster extends Model
{
    public function posts()
    {
        return $this->belongsToMany('App\GAReportsPosts')
            ->withTimestamps();
    }
}
