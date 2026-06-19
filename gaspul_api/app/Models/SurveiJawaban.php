<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveiJawaban extends Model
{
    protected $table = 'survei_jawaban';

    protected $fillable = [
        'survei_id', 'user_id', 'unit_kerja_id',
        'q1', 'q2', 'q3', 'q4', 'q5', 'q6', 'q7', 'q8', 'q9',
        'saran', 'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function survei()
    {
        return $this->belongsTo(Survei::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function unitKerja()
    {
        return $this->belongsTo(UnitKerja::class);
    }
}
