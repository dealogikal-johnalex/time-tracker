<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
  public function index()
  {
      $user = auth()->user();
      $todayLog = \App\Models\TimeLog::where('user_id', $user->id)
          ->whereDate('clock_in', now()->toDateString())
          ->first();

      return view('dashboard', [
          'todayLog' => $todayLog,
          'isClockedIn' => $todayLog && !$todayLog->clock_out,
      ]);
  }
}