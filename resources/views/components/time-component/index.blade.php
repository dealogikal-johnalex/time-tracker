<h1 class="text-4xl font-bold mb-8 text-gray-100 text-center">Daily Time Tracker</h1>

<div class="grid grid-cols-1 md:grid-cols-2 gap-8">

    {{-- 📍 LEFT SIDE --}}
    <div class="w-full flex flex-col items-center">
        <x-time-log-clock size="250" />
        <x-time-log-button />
    </div>

    {{-- 📍 RIGHT SIDE --}}
    <div class="w-full">
        <x-time-log-table />
    </div>
</div>