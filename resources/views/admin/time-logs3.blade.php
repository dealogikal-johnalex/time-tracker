<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('All Employee Time Logs') }}
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            <table class="min-w-full text-left text-sm text-gray-400">
                <thead class="text-xs text-gray-300 uppercase bg-gray-700">
                    <tr>
                        <th class="px-4 py-2">Employee</th>
                        <th class="px-4 py-2">Time In</th>
                        <th class="px-4 py-2">Time Out</th>
                        <th class="px-4 py-2">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr class="border-b border-gray-700">
                            <td class="px-4 py-2">{{ $log->user->name ?? 'N/A' }}</td>
                            <td class="px-4 py-2">{{ $log->time_in }}</td>
                            <td class="px-4 py-2">{{ $log->time_out ?? '-' }}</td>
                            <td class="px-4 py-2">{{ \Carbon\Carbon::parse($log->time_in)->toFormattedDateString() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
