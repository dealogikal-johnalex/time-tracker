<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
use App\Helpers\GeoHelper;

class TimeLogController extends Controller
{
    // Display today's time log on dashboard
    public function index()
    {
        $todayLog = TimeLog::where('user_id', auth()->id())
            ->whereDate('clock_in', now()->toDateString())
            ->first();

        return view('dashboard', compact('todayLog'));
    }

    // Clock In function
    public function clockIn(Request $request)
    {
        $user = Auth::user();

        // Ensure there is no open time log (already clocked in)
        $open = $user->timeLogs()->where('status', 'open')->first();
        if ($open) {
            return response()->json(['error' => 'You already have an open session'], 422);
        }

        // If no open session, check if there was an old session that was not closed
        $unfinishedLog = $user->timeLogs()->where('status', 'open')->whereNull('clock_out')->first();
        if ($unfinishedLog) {
            // Automatically close any unfinished sessions before starting a new one
            $unfinishedLog->update([
                'status' => 'closed',
                'clock_out' => Carbon::now('Asia/Manila'),
                'duration' => Carbon::now('Asia/Manila')->diffInSeconds(Carbon::parse($unfinishedLog->clock_in)),
            ]);
        }

        // Create a new time log entry for clocking in
        $log = TimeLog::create([
            'user_id' => $user->id,
            'clock_in' => Carbon::now('Asia/Manila'),
            'status' => 'open',
            'note' => $request->input('note'),
        ]);

        // Save clock-in location
        $agent = new \Jenssegers\Agent\Agent();
        $device = "{$agent->device()} - {$agent->browser()} on {$agent->platform()}";

        $address = GeoHelper::getAddress($request->latitude, $request->longitude);
        // $ip = $request->ip() ?? $request->server('REMOTE_ADDR');
        $ip = $this->getRealIp($request);

        $log->locations()->create([
            'type' => 'clock_in',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $address,
            'ip_address' => $ip,
            'device' => $device,
        ]);

        return response()->json(['success' => true, 'log' => $log]);
    }

    // Clock Out function
    public function clockOut(Request $request)
    {
        $user = Auth::user();

        // Ensure there is an open session to clock out
        $open = $user->timeLogs()->where('status', 'open')->first();
        if (!$open) {
            return response()->json(['error' => 'No open session to close.'], 422);
        }

        // ✅ Close session
        $now = Carbon::now('Asia/Manila');
        $open->clock_out = $now;
        $open->duration = $now->diffInSeconds(Carbon::parse($open->clock_in));
        $open->status = 'closed';
        $open->note = $request->input('note') ?? $open->note;
        $open->save();

        // $log = TimeLog::where('user_id', Auth::id())
        //     ->whereNull('clock_out')
        //     ->latest('clock_in')
        //     ->first();

        // if (!$open) {
        //     return response()->json(['message' => 'Already timed out or not timed in.'], 400);
        // }

        // $open->update([
        //     'clock_out' => $now,
        //     'duration' => $now->diffInSeconds(Carbon::parse($open->clock_in)),
        //     'status' => 'closed',
        //     'note' => $request->input('note') ?? $open->note,
        // ]);

        // 📍 Save clock-out location
        $agent = new \Jenssegers\Agent\Agent();
        $device = "{$agent->device()} - {$agent->browser()} on {$agent->platform()}";

        $address = GeoHelper::getAddress($request->latitude, $request->longitude);
        $ip = $request->ip() ?? $request->server('REMOTE_ADDR');

        $open->locations()->create([
            'type' => 'clock_out',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $address,
            'ip_address' => $ip,
            'device' => $device,
        ]);

        return response()->json(['success' => true, 'log' => $open]);
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

        // ✅ Ensure endDate is not before today
        if ($endDate->lt($today)) {
            $endDate = $today->copy()->endOfDay();
        }

        // ✅ Adjust startDate to never go before first log date
        if ($startDate->lt($firstLogDate)) {
            $startDate = $firstLogDate;
        }

        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);

        // ✅ Fetch logs and index them by Manila date with eager load locations
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

            // ✅ Skip future dates (compare date only)
            if ($date->toDateString() > $today->toDateString()) {
                $date->subDay();
                continue;
            }

            $dateStr = $date->toDateString();
            $log = $logs[$dateStr] ?? null;

            if ($log) {
                $clockIn = Carbon::parse($log->clock_in)->timezone('Asia/Manila');
                $clockOut = $log->clock_out
                    ? Carbon::parse($log->clock_out)->timezone('Asia/Manila')
                    : null;

                // ✅ Determine status based on today's log
                if ($date->isSameDay($today)) {
                    $status = !$clockOut ? 'Pending' : 'Present';
                } else {
                    $status = 'Present';
                }

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
                // ✅ If today has no log → Pending, otherwise Absent
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

        // ✅ Manual pagination
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


    // Time Out function (Updating the time log and handling location)
    public function timeOut(Request $request)
    {
        $log = TimeLog::where('user_id', Auth::id())
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Already timed out or not timed in.'], 400);
        }

        $log->update([
            'clock_out' => now(),
        ]);

        $agent = new Agent();
        $device = "{$agent->device()} - {$agent->browser()} on {$agent->platform()}";

        $address = GeoHelper::getAddress($request->latitude, $request->longitude);
        $ip = $request->ip() ?? $request->server('REMOTE_ADDR');

        $log->locations()->create([
            'type' => 'clock_out',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $address,
            'ip_address' => $ip,
            'device' => $device,
        ]);

        return response()->json(['message' => 'Time Out recorded successfully!'], 200);
    }

    // Admin view for all logs (pagination)
    public function adminIndex()
    {
        $logs = TimeLog::with('user')->latest()->paginate(20);
        return view('admin.time_logs.index', compact('logs'));
    }

    // Get logs for employees (pagination)
    public function getLogs2()
    {
        $logs = auth()->user()->timeLogs()->latest()->paginate(20);
        return view('employee.time_logs', compact('logs'));
    }

    // Helper function to get real IP address of the user
    private function getRealIp(Request $request)
    {
        $ip = $request->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            $ch = curl_init('https://api.ipify.org');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $ip = curl_exec($ch);
            curl_close($ch);
        }

        return $ip ?: 'UNKNOWN';
    }
}
