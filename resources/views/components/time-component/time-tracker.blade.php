@php
    use App\Models\TimeLog;
    $todayLog = TimeLog::where('user_id', auth()->id())
        ->whereDate('time_in', now()->toDateString())
        ->first();
@endphp
<x-app-layout>
    <div class="py-12 flex flex-col items-center">
        <h1 class="text-4xl font-bold mb-8">Daily Time Tracker</h1>

        {{-- Analog Clock --}}
        <div id="clock" class="relative w-64 h-64 border-8 border-gray-800 rounded-full flex items-center justify-center shadow-lg">
            <div id="hour" class="absolute w-2 bg-black origin-bottom" style="height: 60px;"></div>
            <div id="minute" class="absolute w-1 bg-gray-700 origin-bottom" style="height: 80px;"></div>
            <div id="second" class="absolute w-0.5 bg-red-600 origin-bottom" style="height: 90px;"></div>
        </div>

        {{-- Time In Form --}}
        <form action="{{ route('time.in') }}" method="POST" class="mt-10 text-center">
            @csrf
            <input type="hidden" name="latitude" id="latitude">
            <input type="hidden" name="longitude" id="longitude">
            <input type="text" name="employee_id" placeholder="Enter Employee ID"
                class="border rounded-lg p-2 w-64 text-center" required
                {{ $todayLog ? 'disabled' : '' }}>

            <br><br>
            <button type="submit" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700"
                {{ $todayLog ? 'disabled' : '' }}>
                {{ $todayLog ? 'Already Timed In' : 'Time In' }}
            </button>
        </form>

        {{-- Time Out Form --}}
        <form action="{{ route('time.out') }}" method="POST" class="mt-4 text-center">
            @csrf
            <input type="text" name="employee_id" placeholder="Enter Employee ID"
                class="border rounded-lg p-2 w-64 text-center" required
                {{ $todayLog && $todayLog->time_out ? 'disabled' : '' }}>
            <br><br>
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700"
                {{ !$todayLog || $todayLog->time_out ? 'disabled' : '' }}>
                {{ !$todayLog ? 'Time In First' : ($todayLog->time_out ? 'Already Timed Out' : 'Time Out') }}
            </button>
        </form>

        @if(session('success'))
            <p class="mt-4 text-green-600 font-semibold">{{ session('success') }}</p>
        @endif
        @if(session('error'))
            <p class="mt-4 text-red-600 font-semibold">{{ session('error') }}</p>
        @endif
    </div>

    <script>
        const hourEl = document.getElementById('hour');
        const minuteEl = document.getElementById('minute');
        const secondEl = document.getElementById('second');

        function updateClock() {
            const now = new Date();
            const seconds = now.getSeconds() * 6;
            const minutes = now.getMinutes() * 6;
            const hours = (now.getHours() % 12) * 30 + now.getMinutes() * 0.5;

            secondEl.style.transform = `rotate(${seconds}deg)`;
            minuteEl.style.transform = `rotate(${minutes}deg)`;
            hourEl.style.transform = `rotate(${hours}deg)`;
        }

        setInterval(updateClock, 1000);
        updateClock();

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById('latitude').value = pos.coords.latitude;
                document.getElementById('longitude').value = pos.coords.longitude;
            },
            (err) => {
                alert('Please enable location services to time in.');
            }
        );
    </script>
</x-app-layout>
