<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use App\Helpers\GeoHelper;
use App\Models\TimeLog;
use App\Models\TimeLogLocation;

class TimeLogController extends Controller
{
    
    public function index()
    {
        $logs = TimeLog::where('user_id', auth()->id())->get();
        return view('index', compact('logs'));
    }

    public function store(Request $request, $action)
    {
        $user = Auth::user(); // Get the authenticated user
        
        // Retrieve the latest time log
        $lastLog = TimeLog::where('user_id', $user->id)->latest()->first();

        // Helper function to save location and device info
        $saveLocation = function($timeLogId, $type) use ($request) {
            TimeLogLocation::create([
                'time_log_id' => $timeLogId,
                'type' => $type,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'address' => $request->input('address'),
                'ip_address' => $request->ip(),
                'device' => $request->header('User-Agent'),
            ]);
        };

        $timeLog = null;

       // Handle actions
        switch ($action) {
            case 'clock_in':
                $timeLog = TimeLog::create([
                    'user_id' => $user->id,
                    'clock_in' => Carbon::now(),
                    'status' => 'clocked_in',
                ]);
                $saveLocation($timeLog->id, 'clock_in');
                break;

            case 'clock_out':
                if ($lastLog) {
                    $lastLog->update([
                        'clock_out' => Carbon::now(),
                        'status' => 'done',
                    ]);
                    $saveLocation($lastLog->id, 'clock_out');
                }
                break;

            case 'break_in':
                if ($lastLog && $lastLog->status == 'clocked_in') {
                    $lastLog->update([
                        'break_in' => Carbon::now(),
                        'status' => 'on_break',
                    ]);
                    $saveLocation($lastLog->id, 'break_in');
                }
                break;

            case 'break_out':
                if ($lastLog && $lastLog->status == 'on_break') {
                    $lastLog->update([
                        'break_out' => Carbon::now(),
                        'status' => 'break_completed',
                    ]);
                    $saveLocation($lastLog->id, 'break_out');
                }
                break;
        }

        return response()->json(['message' => 'Success']);
    }

    // Get Time Logs with pagination and absences
public function getLogs(Request $request)
{
    $userId = Auth::id();
    $today = Carbon::today('Asia/Manila');

    // Get user's earliest clock-in date
    $firstLogDate = TimeLog::where('user_id', $userId)->min('clock_in');
    $firstLogDate = $firstLogDate ? Carbon::parse($firstLogDate)->timezone('Asia/Manila')->startOfDay() : $today;

    // Use request range or default (last 30 days)
    $startDate = $request->start_date
        ? Carbon::parse($request->start_date)->timezone('Asia/Manila')->startOfDay()
        : $today->copy()->subDays(30)->startOfDay();

    $endDate = $request->end_date
        ? Carbon::parse($request->end_date)->timezone('Asia/Manila')->endOfDay()
        : $today->copy()->endOfDay();

    // Ensure endDate is not before today
    if ($endDate->lt($today)) {
        $endDate = $today->copy()->endOfDay();
    }

    // Adjust startDate to never go before first log date
    if ($startDate->lt($firstLogDate)) {
        $startDate = $firstLogDate;
    }

    $perPage = $request->get('per_page', 10);
    $page = $request->get('page', 1);

    // Fetch logs and index them by Manila date with eager load locations
    $logs = TimeLog::with('locations')
        ->where('user_id', $userId)
        // ->whereDate('clock_in', '>=', $startDate)
        // ->whereBetween('clock_in', [$startDate->startOfDay(), $endDate->endOfDay()])
        // ->whereBetween('clock_in', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
        ->whereBetween('clock_in', [
            $startDate->copy()->timezone('UTC')->startOfDay(),
            $endDate->copy()->timezone('UTC')->endOfDay()
        ])
        ->orderBy('clock_in', 'desc')
        ->get()
        ->mapWithKeys(function ($log) {
            $localDate = Carbon::parse($log->clock_in)->timezone('Asia/Manila')->toDateString();
            return [$localDate => $log];
        });

    // Build date-wise results including absences
    $result = [];
    $date = $endDate->copy();
    // $date = $today->copy();

    while ($date->gte($startDate)) {
        // Skip weekends, uncomment if you want to show weekends
        // if ($date->isWeekend()) {
        //     $date->subDay();
        //     continue;
        // }

        // Skip future dates (compare date only)
        if ($date->toDateString() > $today->toDateString()) {
            $date->subDay();
            continue;
        }

        $dateStr = $date->toDateString();
        $log = $logs[$dateStr] ?? null;

        if ($log) {
            $clockIn = Carbon::parse($log->clock_in)->timezone('Asia/Manila');
            $breakIn = $log->break_in ? Carbon::parse($log->break_in)->timezone('Asia/Manila') : null;
            $breakOut = $log->break_out ? Carbon::parse($log->break_out)->timezone('Asia/Manila') : null;
            $clockOut = $log->clock_out ? Carbon::parse($log->clock_out)->timezone('Asia/Manila') : null;

            // Determine status based on today's log
            $status = $date->isSameDay($today) && !$clockOut ? 'Pending' : 'Present';
            $status = $log->status != null ? $log->status : $status;

            $isLate = $clockIn->gt(Carbon::parse($date->format('Y-m-d') . ' 08:00:00', 'Asia/Manila'));
            $isUndertime = $clockOut && $clockOut->lt(Carbon::parse($date->format('Y-m-d') . ' 17:00:00', 'Asia/Manila'));

            $remarks = [];
            if ($isLate) $remarks[] = 'Late';
            if ($isUndertime) $remarks[] = 'Undertime';
            if ($status === 'Pending') $remarks[] = 'Awaiting clock-out';

            // Extract locations
            $clockInLocation = $log->locations->firstWhere('type', 'clock_in');
            $clockOutLocation = $log->locations->firstWhere('type', 'clock_out');

            $result[] = [
                'date' => $dateStr,
                'clock_in' => $clockIn->format('H:i:s'),
                'break_in' => $breakIn ? $breakIn->format('H:i:s') : null,
                'break_out' => $breakOut ? $breakOut->format('H:i:s') : null,
                'clock_out' => $clockOut ? $clockOut->format('H:i:s') : null,
                'status' => $status,
                'remarks' => implode(', ', $remarks),
                'clock_in_location' => $clockInLocation ? [
                    'latitude' => $clockInLocation->latitude,
                    'longitude' => $clockInLocation->longitude,
                    'address' => $clockInLocation->address,
                    'ip_address' => $clockInLocation->ip_address,
                    'device' => $clockInLocation->device,
                ] : null,
                'clock_out_location' => $clockOutLocation ? [
                    'latitude' => $clockOutLocation->latitude,
                    'longitude' => $clockOutLocation->longitude,
                    'address' => $clockOutLocation->address,
                    'ip_address' => $clockOutLocation->ip_address,
                    'device' => $clockOutLocation->device,
                ] : null,
            ];
        } else {
            // If there's no log, show 'Pending' or 'Absent'
            $status = $date->isSameDay($today) ? 'Pending' : 'Absent';
            $remarks = $status === 'Pending' ? 'Awaiting clock-in' : 'Absent';

            $result[] = [
                'date' => $dateStr,
                'clock_in' => null,
                'clock_out' => null,
                'status' => $status,
                'remarks' => $remarks,
                'clock_in_location' => null,
                'clock_out_location' => null,
            ];
        }

        $date->subDay();
    }

    // Manual pagination
    $total = count($result);
    $offset = ($page - 1) * $perPage;
    $pagedLogs = array_slice($result, $offset, $perPage);

    return response()->json([
        'data' => $pagedLogs,
        'pagination' => [
            'total' => $total,
            'per_page' => (int) $perPage,
            'current_page' => (int) $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => min($offset + $perPage, $total)
        ]
    ]);
}


}
