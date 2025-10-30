<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UomController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.uom.index');
    }
}

