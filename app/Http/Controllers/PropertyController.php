<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Porperty;
class PropertyController extends Controller
{
    public function index()
    {
        return Porperty::all();
    }
}
