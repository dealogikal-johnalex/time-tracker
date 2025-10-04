
<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">🕒 Time Tracker</h3>

    {{-- Show current status --}}
    @if($todayLog && $todayLog->clock_in && !$todayLog->clock_out)
        <div class="mb-4 text-green-600 dark:text-green-400">
            ✅ You clocked in at {{ \Carbon\Carbon::parse($todayLog->clock_in)->format('h:i A') }}.
        </div>
    @elseif($todayLog && $todayLog->clock_in && $todayLog->clock_out)
        <div class="mb-4 text-blue-600 dark:text-blue-400">
            📅 You clocked in at {{ \Carbon\Carbon::parse($todayLog->clock_in)->format('h:i A') }}
            and clocked out at {{ \Carbon\Carbon::parse($todayLog->clock_out)->format('h:i A') }}.
        </div>
    @else
        <div class="mb-4 text-red-600 dark:text-red-400">
            ❌ You haven't clocked in today.
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex space-x-4">
        @if(!$todayLog || !$todayLog->clock_in)
            {{-- Clock In --}}
            <form method="POST" action="{{ route('clock.in') }}">
                @csrf
                <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg">
                    ⏱️ Clock In
                </button>
            </form>
        @elseif($todayLog && !$todayLog->clock_out)
            {{-- Clock Out --}}
            <form method="POST" action="{{ route('clock.out') }}">
                @csrf
                <button type="submit" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg">
                    🛑 Clock Out
                </button>
            </form>
        @endif
    </div>

    {{-- 📊 Today Log --}}
    @if($todayLog)
        <div class="mt-6 border-t border-gray-300 dark:border-gray-700 pt-4">
            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">📊 Today's Log</h4>
            <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($todayLog->clock_in)->format('Y-m-d') }}</p>
            <p><strong>Time In:</strong> {{ $todayLog->clock_in ? \Carbon\Carbon::parse($todayLog->clock_in)->format('h:i A') : '-' }}</p>
            <p><strong>Time Out:</strong> {{ $todayLog->clock_out ? \Carbon\Carbon::parse($todayLog->clock_out)->format('h:i A') : '-' }}</p>
            @if($todayLog->clock_in && $todayLog->clock_out)
                <p><strong>Total Hours:</strong> {{ \Carbon\Carbon::parse($todayLog->clock_in)->diffInHours($todayLog->clock_out) }} hrs</p>
            @endif
        </div>
    @endif
</div>