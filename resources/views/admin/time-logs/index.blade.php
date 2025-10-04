<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('All Employee Clock Logs') }}
        </h2>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            
            {{-- 🔎 Filters --}}
            <form method="GET" action="{{ route('admin.time-logs') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Start Date</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">End Date</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Employee ID</label>
                    <input type="text" name="employee_id" value="{{ request('employee_id') }}" placeholder="e.g. 00001"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" value="{{ request('name') }}" placeholder="e.g. John Doe"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                    <select name="role"
                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ ucfirst($role->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-5 flex justify-end space-x-2 mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">Filter</button>
                    <a href="{{ route('admin.time-logs') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded">Reset</a>
                </div>
            </form>

            {{-- 📤 Export & Print --}}
            <div class="flex justify-end mb-4 space-x-2">
                <a href="{{ route('admin.time-logs.export', request()->query()) }}" 
                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded transition">
                    📥 Export CSV
                </a>
                <button onclick="window.print()" 
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white rounded transition">
                    🖨️ Print Report
                </button>
            </div>

            {{-- 📊 Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse w-full text-sm">
                    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                        <tr>
                            <th class="px-4 py-2 text-left">Employee ID</th>
                            <th class="px-4 py-2 text-left">Name</th>
                            <th class="px-4 py-2 text-left">Role</th>
                            <th class="px-4 py-2 text-left">Date</th>
                            <th class="px-4 py-2 text-left">Clock In</th>
                            <th class="px-4 py-2 text-left">Clock Out</th>
                            <th class="px-4 py-2 text-left">Total Hours</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                        @forelse ($logs as $index => $log)
                            <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                <td class="px-4 py-2">{{ $log['employee_id'] }}</td>
                                <td class="px-4 py-2">{{ $log['name'] }}</td>
                                <td class="px-4 py-2">{{ $log['role'] }}</td>
                                <td class="px-4 py-2">{{ $log['date'] }}</td>
                                <td class="px-4 py-2">{{ $log['clock_in'] }}</td>
                                <td class="px-4 py-2">{{ $log['clock_out'] }}</td>
                                <td class="px-4 py-2">
                                    @if($log['clock_in'] !== '-' && $log['clock_out'] !== '-')
                                        @php
                                            $in = \Carbon\Carbon::createFromFormat('H:i:s', $log['clock_in']);
                                            $out = \Carbon\Carbon::createFromFormat('H:i:s', $log['clock_out']);
                                            $hours = number_format($in->floatDiffInHours($out), 2);
                                        @endphp
                                        {{ $hours }} hrs
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    @if($log['status'] === 'Present')
                                        <span class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100 rounded-full text-xs font-semibold">Present</span>
                                    @elseif($log['status'] === 'Pending')
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100 rounded-full text-xs font-semibold">Pending</span>
                                    @else
                                        <span class="px-2 py-1 bg-red-100 text-red-800 dark:bg-red-700 dark:text-red-100 rounded-full text-xs font-semibold">Absent</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <button type="button" onclick="toggleDetails({{ $index }})"
                                        class="text-blue-600 hover:underline">View Details</button>
                                </td>
                            </tr>

                            {{-- 📍 Hidden details row --}}
                            <tr id="details-{{ $index }}" class="hidden bg-gray-50 dark:bg-gray-900">
                                <td colspan="9" class="px-6 py-4 text-sm">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="font-semibold mb-1">📍 Clock-In Location</h4>
                                            @if($log['clock_in_location'])
                                                <p><strong>Address:</strong> {{ $log['clock_in_location']['address'] ?? 'N/A' }}</p>
                                                <p><strong>IP:</strong> {{ $log['clock_in_location']['ip_address'] ?? 'N/A' }}</p>
                                                <p><strong>Device:</strong> {{ $log['clock_in_location']['device'] ?? 'N/A' }}</p>
                                            @else
                                                <p class="text-gray-500">No clock-in location recorded.</p>
                                            @endif
                                        </div>

                                        <div>
                                            <h4 class="font-semibold mb-1">📍 Clock-Out Location</h4>
                                            @if($log['clock_out_location'])
                                                <p><strong>Address:</strong> {{ $log['clock_out_location']['address'] ?? 'N/A' }}</p>
                                                <p><strong>IP:</strong> {{ $log['clock_out_location']['ip_address'] ?? 'N/A' }}</p>
                                                <p><strong>Device:</strong> {{ $log['clock_out_location']['device'] ?? 'N/A' }}</p>
                                            @else
                                                <p class="text-gray-500">No clock-out location recorded.</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-gray-600 dark:text-gray-400">No logs found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <script>
        function toggleDetails(index) {
            const row = document.getElementById(`details-${index}`);
            row.classList.toggle('hidden');
        }
    </script>
</x-app-layout>
