<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        return view('admin.units.index');
    }

    public function create()
    {
        return view('admin.units.tambah');
    }

    public function edit($id)
    {
        return view('admin.units.edit', compact('id'));
    }
}
