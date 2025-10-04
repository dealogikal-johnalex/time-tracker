<h1 class="text-4xl font-bold mb-8 text-gray-100 text-center">Daily Time Tracker</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">

    {{-- 📍 LEFT SIDE --}}
    <div class="flex flex-col items-center">
        <x-time-component.analog-clock size="250" />
        <div class="w-full flex flex-col items-center">
            <x-time-component.button :todayLog="$todayLog" />

            @if(session('success'))
                <p class="mt-4 text-green-600 font-semibold">{{ session('success') }}</p>
            @endif
            @if(session('error'))
                <p class="mt-4 text-red-600 font-semibold">{{ session('error') }}</p>
            @endif
        </div>
    </div>

    {{-- 📍 RIGHT SIDE --}}
    <div class="w-full">
        <x-time-component.table />
    </div>
</div>