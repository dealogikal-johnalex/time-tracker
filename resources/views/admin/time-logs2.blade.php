<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('All Employee Time Logs') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <h1 class="text-3xl font-bold mb-6 text-gray-100">📅 Time Logs - {{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h1>

            {{-- 📅 Date Filter --}}
            <form method="GET" action="{{ route('admin.time-logs') }}" class="mb-6 flex items-center space-x-4">
                <label for="date" class="text-gray-300">Select Date:</label>
                <input type="date" name="date" id="date" value="{{ $date }}" class="px-3 py-2 rounded bg-gray-700 text-white">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Filter</button>
            </form>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-x-auto">
                <table class="min-w-full text-left text-sm text-gray-300">
                    <thead class="bg-gray-700 text-gray-200">
                        <tr>
                            <th class="px-4 py-3">Employee ID</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Time In</th>
                            <th class="px-4 py-3">Time Out</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php
                                $log = $user->timeLogs->first();
                            @endphp
                            <tr class="border-b border-gray-700">
                                <td class="px-4 py-3">{{ $user->employee_id ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ $user->name }}</td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">
                                    {{ $log && $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('h:i A') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ $log && $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '-' }}
                                </td>
                                <td class="px-4 py-3">
                                    @if($log)
                                        @if(!$log->time_out)
                                            <span class="px-2 py-1 bg-yellow-600 text-white rounded">Working</span>
                                        @else
                                            <span class="px-2 py-1 bg-green-600 text-white rounded">Present</span>
                                        @endif
                                    @else
                                        <span class="px-2 py-1 bg-red-600 text-white rounded">Absent</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-400">No employees found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
