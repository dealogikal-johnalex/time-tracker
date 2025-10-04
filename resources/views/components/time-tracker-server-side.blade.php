<div>
    <h2 class="text-xl font-bold mb-4">Today's Logs</h2>

    @if($todayLog->count() > 0)
        <table class="table-auto w-full">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach($todayLog as $log)
                    <tr>
                        <td>{{ $log->employee->name ?? 'N/A' }}</td>
                        <td>{{ $log->time_in }}</td>
                        <td>{{ $log->time_out }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-gray-400">No logs for today.</p>
    @endif
</div>
