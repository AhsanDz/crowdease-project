<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function login()    { return view('operator.login'); }
    public function dashboard(){ return view('operator.dashboard'); }
    public function routes()   { return view('operator.routes'); }
    public function vehicles() { return view('operator.vehicles'); }
    public function stops()    { return view('operator.stops'); }
    public function apikeys()  { return view('operator.apikeys'); }
    public function webhooks() { return view('operator.webhooks'); }
}
