<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Carbon\Carbon;

class TimeLogController extends Controller
{
    public function index1(Request $request)
    {
        $query = TimeLog::with('user')->orderBy('clock_in', 'desc');

        // 🔍 Apply filters
        if ($request->filled('start_date')) {
            $query->whereDate('clock_in', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('clock_out', '<=', $request->end_date);
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('employee_id', 'like', "%{$request->employee_id}%");
            });
        }

        if ($request->filled('name')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->name}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('user.roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $logs = $query->paginate(15);
        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.time-logs.index', compact('logs', 'roles'));
    }
    
public function index2(Request $request)
{
    $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
    $endDate = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

    $users = \App\Models\User::with('roles')->get();
    $allLogs = TimeLog::with('user')
        ->whereBetween('clock_in', [$startDate, $endDate])
        ->get();

    $dateRange = [];
    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
        // skip weekends if you want Mon-Fri only:
        // if ($date->isWeekend()) continue;
        $dateRange[] = $date->toDateString();
    }

    $logsWithAbsents = [];

    foreach ($users as $user) {
        foreach ($dateRange as $date) {
            $log = $allLogs->first(fn($l) =>
                $l->user_id === $user->id && $l->clock_in && Carbon::parse($l->clock_in)->isSameDay($date)
            );

            if ($log) {
                $logsWithAbsents[] = [
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => Carbon::parse($log->clock_in)->toDateString(),
                    'time_in' => Carbon::parse($log->clock_in)->format('h:i A'),
                    'time_out' => $log->clock_out ? Carbon::parse($log->clock_out)->format('h:i A') : '-',
                    'status' => 'Present',
                ];
            } else {
                $logsWithAbsents[] = [
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => $date,
                    'time_in' => '-',
                    'time_out' => '-',
                    'status' => 'Absent',
                ];
            }
        }
    }

    // optional: sort by date desc
    $logsWithAbsents = collect($logsWithAbsents)->sortByDesc('date')->values();

    return view('admin.time_logs.index', [
        'logs' => $logsWithAbsents,
        'startDate' => $startDate->toDateString(),
        'endDate' => $endDate->toDateString(),
    ]);
}

public function indexOLD(Request $request)
{
    // 📅 Define date range (default: current month)
    $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
    $endDate   = $request->end_date ? Carbon::parse($request->end_date) : Carbon::now();

    // 🧑‍💻 Fetch users based on filters
    $usersQuery = \App\Models\User::with('roles');

    if ($request->filled('employee_id')) {
        $usersQuery->where('employee_id', 'like', "%{$request->employee_id}%");
    }

    if ($request->filled('name')) {
        $usersQuery->where('name', 'like', "%{$request->name}%");
    }

    if ($request->filled('role')) {
        $usersQuery->role($request->role);
    }

    $users = $usersQuery->get();

    // 📊 Get logs for these users in the date range
    $logs = TimeLog::with('user, locations')
        ->whereBetween('clock_in', [$startDate, $endDate])
        ->whereIn('user_id', $users->pluck('id'))
        ->orderBy('clock_in', 'desc')
        ->get();
    // $locations = TimeLog::with('locations')->where('user_id', Auth::id())->get();


    // 📅 Build date range (Mon–Fri only, optional)
    $dateRange = [];
    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
        // Uncomment if you want to skip weekends:
        if ($date->isWeekend()) continue;
        $dateRange[] = $date->toDateString();
    }

    // 🧠 Build combined logs (including absents)
    $allLogs = collect();

    foreach ($users as $user) {
        foreach ($dateRange as $date) {
            $log = $logs->first(fn($l) =>
                $l->user_id === $user->id && Carbon::parse($l->clock_in)->isSameDay($date)
            );

            if ($log) {
                $allLogs->push([
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => Carbon::parse($log->clock_in)->format('D, F d, Y'),
                    'clock_in' => Carbon::parse($log->clock_in)->format('h:i A'),
                    'clock_out' => $log->clock_out ? Carbon::parse($log->clock_out)->format('h:i A') : '-',
                    'status' => 'Present',
                ]);
            } else {
                $allLogs->push([
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => Carbon::parse($date)->format('D, F d, Y'),
                    'clock_in' => '-',
                    'clock_out' => '-',
                    'status' => 'Absent',
                ]);
            }
        }
    }

    // 📚 Sort and paginate manually
    $sortedLogs = $allLogs->sortByDesc('date')->values();
    $perPage = 15;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $sortedLogs->slice(($currentPage - 1) * $perPage, $perPage)->values();

    $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentItems,
        $sortedLogs->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    $roles = \Spatie\Permission\Models\Role::all();

    return view('admin.time-logs.index', [
        'logs' => $paginatedLogs,
        'roles' => $roles,
        'startDate' => $startDate->toDateString(),
        'endDate' => $endDate->toDateString(),
    ]);
}

public function index(Request $request)
{
    // 📅 Define date range (default: current month)
    $startDate = $request->start_date 
        ? Carbon::parse($request->start_date, 'Asia/Manila')->startOfDay()
        : Carbon::now('Asia/Manila')->startOfMonth();

    $endDate = $request->end_date 
        ? Carbon::parse($request->end_date, 'Asia/Manila')->endOfDay()
        : Carbon::now('Asia/Manila')->endOfDay();

    // 🧑‍💻 Fetch users based on filters
    $usersQuery = \App\Models\User::with('roles');

    if ($request->filled('employee_id')) {
        $usersQuery->where('employee_id', 'like', "%{$request->employee_id}%");
    }

    if ($request->filled('name')) {
        $usersQuery->where('name', 'like', "%{$request->name}%");
    }

    if ($request->filled('role')) {
        $usersQuery->role($request->role);
    }

    $users = $usersQuery->get();

    // 📊 Get logs for these users (convert to UTC for DB if needed)
    $logs = TimeLog::with(['user', 'locations'])
        ->whereBetween('clock_in', [
            $startDate->copy()->timezone('UTC'),
            $endDate->copy()->timezone('UTC')
        ])
        // ->whereIn('user_id', $users->pluck('id'))
        // where user is not admin or superadmin
        ->whereNotIn('user_id', $users->whereIn('roles.name', ['admin', 'superadmin'])->pluck('id'))
        ->orderBy('clock_in', 'desc')
        ->get();

    // 📅 Build date range (Mon–Fri only, optional)
    $dateRange = [];
    for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
        // if ($date->isWeekend()) continue; // skip weekends if needed
        $dateRange[] = $date->copy();
    }

    // 🧠 Build combined logs (including absents and locations)
    $allLogs = collect();

    foreach ($users as $user) {
        foreach ($dateRange as $date) {
            // Match logs by user and Manila date
            $log = $logs->first(function ($l) use ($user, $date) {
                return $l->user_id === $user->id && 
                       Carbon::parse($l->clock_in)->timezone('Asia/Manila')->isSameDay($date);
            });

            if ($log) {
                $clockIn = Carbon::parse($log->clock_in)->timezone('Asia/Manila');
                $clockOut = $log->clock_out ? Carbon::parse($log->clock_out)->timezone('Asia/Manila') : null;

                // 📍 Extract locations
                $clockInLocation = $log->locations->firstWhere('type', 'clock_in');
                $clockOutLocation = $log->locations->firstWhere('type', 'clock_out');

                $allLogs->push([
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => $date->format('D, F d, Y'),
                    'clock_in' => $clockIn->format('H:i:s'),
                    'clock_out' => $clockOut ? $clockOut->format('H:i:s') : '-',
                    'status' => 'Present',
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
                ]);
            } else {
                // 📍 Absent or pending if today
                $status = $date->isSameDay(Carbon::today('Asia/Manila')) ? 'Pending' : 'Absent';

                $allLogs->push([
                    'employee_id' => $user->employee_id,
                    'name' => $user->name,
                    'role' => $user->getRoleNames()->implode(', '),
                    'date' => $date->format('D, F d, Y'),
                    'clock_in' => '-',
                    'clock_out' => '-',
                    'status' => $status,
                    'clock_in_location' => null,
                    'clock_out_location' => null,
                ]);
            }
        }
    }

    // 📚 Sort and paginate manually
    $sortedLogs = $allLogs->sortByDesc(fn($l) => Carbon::parse($l['date']))->values();
    $perPage = 15;
    $currentPage = LengthAwarePaginator::resolveCurrentPage();
    $currentItems = $sortedLogs->slice(($currentPage - 1) * $perPage, $perPage)->values();

    $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
        $currentItems,
        $sortedLogs->count(),
        $perPage,
        $currentPage,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    $roles = \Spatie\Permission\Models\Role::all();

    return view('admin.time-logs.index', [
        'logs' => $paginatedLogs,
        'roles' => $roles,
        'startDate' => $startDate->toDateString(),
        'endDate' => $endDate->toDateString(),
    ]);
}




    public function leaveLogs(Request $req)
    {
        $leaveLogs = LeaveLog::with('user')->orderBy('date','desc')->paginate(25);
        return view('admin.leave_logs', compact('leaveLogs'));
    }

    public function userLogs(Request $request) // UNUSED
    {
        // Filter by date (optional)
        $date = $request->get('date', Carbon::today()->toDateString());

        // ✅ Fetch all users with today's time logs
        $users = User::with(['timeLogs' => function ($q) use ($date) {
            $q->whereDate('time_in', $date);
        }])->get();

        return view('admin.time-logs', compact('users', 'date'));
    }

    public function export2(Request $request)
    {
        $query = TimeLog::with('user')->orderBy('clock_in', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('clock_in', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('clock_out', '<=', $request->end_date);
        }

        if ($request->filled('employee_id')) {
            $query->whereHas('user', fn($q) => $q->where('employee_id', 'like', "%{$request->employee_id}%"));
        }

        if ($request->filled('name')) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$request->name}%"));
        }

        if ($request->filled('role')) {
            $query->whereHas('user.roles', fn($q) => $q->where('name', $request->role));
        }

        $logs = $query->get();

        $filename = 'clock_logs_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $handle = fopen('php://temp', 'r+');

        // CSV Header
        fputcsv($handle, [
            'Employee ID', 'Name', 'Role', 'Date', 'Clock In', 'Clock Out',
            'Total Hours', 'Status'
        ]);

        foreach ($logs as $log) {
            $totalHours = $log->clock_out
                ? \Carbon\Carbon::parse($log->clock_in)->diffInHours($log->clock_out)
                : 0;

            $status = $log->clock_in ? 'Present' : 'Absent';

            fputcsv($handle, [
                $log->user->employee_id,
                $log->user->name,
                $log->user->getRoleNames()->implode(', '),
                $log->clock_in ? (string) \Carbon\Carbon::parse($log->clock_in)->format('Y-m-d') : '',
                $log->clock_in ? (string) \Carbon\Carbon::parse($log->clock_in)->format('h:i A') : '',
                $log->clock_out ? (string) \Carbon\Carbon::parse($log->clock_out)->format('h:i A') : '',
                $totalHours . ' hrs',
                $status
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return response($csvContent)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function export()
    {
        $logs = TimeLog::with('user')->latest()->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 🧭 Set column headers
        $sheet->fromArray([
            ['Employee ID', 'Name', 'Role', 'Date', 'Time In', 'Time Out', 'Total Hours', 'Status']
        ], null, 'A1');

        // 🕒 Fill data
        $row = 2;
        foreach ($logs as $log) {
            $sheet->setCellValue("A{$row}", $log->user->employee_id);
            $sheet->setCellValue("B{$row}", $log->user->name);
            $sheet->setCellValue("C{$row}", $log->user->getRoleNames()->implode(', '));
            $sheet->setCellValue("D{$row}", Carbon::parse($log->clock_in)->format('Y-m-d'));
            $sheet->setCellValue("E{$row}", $log->clock_in ? Carbon::parse($log->clock_in)->format('H:i') : '');
            $sheet->setCellValue("F{$row}", $log->clock_out ? Carbon::parse($log->clock_out)->format('H:i') : '');
            $sheet->setCellValue("G{$row}", $log->clock_out ? Carbon::parse($log->clock_in)->diffInHours($log->clock_out) . ' hrs' : '');
            $sheet->setCellValue("H{$row}", $log->clock_in ? 'Present' : 'Absent');
            $row++;
        }

        // 📅 Format Date column properly
        foreach (range(2, $row) as $r) {
            $sheet->getStyle("D{$r}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
        }

        // 📁 Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // 📤 Download file
        $writer = new Xlsx($spreadsheet);
        $fileName = 'clock_logs.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

}
