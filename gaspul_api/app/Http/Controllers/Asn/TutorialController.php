<?php

namespace App\Http\Controllers\Asn;

use App\Http\Controllers\Controller;

class TutorialController extends Controller
{
    public function index()
    {
        $videos = config('tutorial.videos');

        return view('asn.tutorial.index', compact('videos'));
    }
}
