<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveiPertanyaan extends Model
{
    protected $table = 'survei_pertanyaan';

    protected $fillable = ['survei_id', 'urutan', 'tipe', 'pertanyaan'];

    const TIPE_SKALA = 'SKALA';
    const TIPE_TEKS  = 'TEKS';

    public function survei()
    {
        return $this->belongsTo(Survei::class);
    }
}
