<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GAReports extends Model
{
    public function report_schedule()
    {
        return $this->belongsTo(GAReportsSchedule::class, 'report_schedule_id');
    }
}
