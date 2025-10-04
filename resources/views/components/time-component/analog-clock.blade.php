<div class="relative border-8 border-gray-800 rounded-full flex items-center justify-center shadow-lg bg-white"
     style="width: {{ $size }}px; height: {{ $size }}px; position: relative;">
     
    {{-- Center dot --}}
    <div class="absolute w-4 h-4 bg-black rounded-full z-20"></div>

    {{-- Hour hand --}}
    <div id="hour" class="absolute w-2 bg-black rounded-full transform origin-bottom" style="height: 60px; bottom: 50%;"></div>

    {{-- Minute hand --}}
    <div id="minute" class="absolute w-1 bg-gray-700 rounded-full transform origin-bottom" style="height: 80px; bottom: 50%;"></div>

    {{-- Second hand --}}
    <div id="second" class="absolute w-0.5 bg-red-600 rounded-full transform origin-bottom" style="height: 90px; bottom: 50%;"></div>
</div>

{{-- ✅ Digital Clock --}}
<div id="digital-clock" class="text-center text-2xl font-bold text-gray-800 dark:text-gray-200 mt-4 tracking-wider"></div>

{{-- ✅ Clock Script --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const hourEl = document.getElementById('hour');
        const minuteEl = document.getElementById('minute');
        const secondEl = document.getElementById('second');
        const digitalClockEl = document.getElementById('digital-clock');

        function updateClock() {
            const now = new Date();

            // Analog clock
            const seconds = now.getSeconds() * 6;
            const minutes = now.getMinutes() * 6;
            const hours = (now.getHours() % 12) * 30 + now.getMinutes() * 0.5;

            secondEl.style.transform = `rotate(${seconds}deg)`;
            minuteEl.style.transform = `rotate(${minutes}deg)`;
            hourEl.style.transform = `rotate(${hours}deg)`;

            // Digital clock (24-hour format)
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');

            digitalClockEl.textContent = `${hh}:${mm}:${ss}`;
        }

        updateClock();
        setInterval(updateClock, 1000);
    });
</script>
