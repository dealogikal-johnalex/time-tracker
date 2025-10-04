@props(['todayLog'])

<div class="mt-2 text-center space-y-6" x-data="timeTracker()">
    <input type="hidden" id="latitude" name="latitude">
    <input type="hidden" id="longitude" name="longitude">
    <input type="hidden" id="location" name="location">

    <button 
        x-text="label" 
        @click.prevent="handleClick"
        :disabled="disabled"
        class="px-6 py-3 rounded-lg text-white font-semibold transition flex items-center justify-center gap-2 w-40 mx-auto"
        :class="{
            'bg-green-600 hover:bg-green-700': label === 'Clock In' && !disabled,
            'bg-red-600 hover:bg-red-700': label === 'Clock Out' && !disabled,
            'bg-gray-500 cursor-not-allowed': disabled || loading
        }"
    >
        <template x-if="loading">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </template>
        <span x-text="loading ? 'Please wait...' : label"></span>
    </button>

    <p x-show="message" x-text="message" class="mt-4 text-green-600 font-semibold"></p>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('timeTracker', () => ({
        label: @json($todayLog ? ($todayLog->clock_out ? 'Already Timed Out' : 'Clock Out') : 'Clock In'),
        loading: false,
        disabled: @json($todayLog && $todayLog->clock_out ? true : false),
        message: '',

        async handleClick() {
            if (this.disabled || this.loading) return;

            this.loading = true;
            this.message = '';

            try {
                // ✅ Get location first
                const pos = await new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(resolve, reject);
                });

                const lat = pos.coords.latitude;
                const lng = pos.coords.longitude;

                // ✅ Reverse geocode
                let location = '';
                try {
                    const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                    const data = await res.json();
                    location = data.display_name || '';
                } catch (e) {
                    location = '';
                }

                const url = this.label === 'Clock In' ? "{{ route('clock.in') }}" : "{{ route('clock.out') }}";

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ latitude: lat, longitude: lng, location })
                });

                const data = await res.json();

                if (res.ok) {
                    this.message = data.message || 'Success';
                    
                    // ✅ Update button state after first successful click
                    if (this.label === 'Clock In') {
                        this.label = 'Clock Out';
                    } else {
                        this.label = 'Already Timed Out';
                        this.disabled = true;
                    }

                    // Refresh logs dynamically if using Alpine store
                    if (window.Alpine && Alpine.store('timeLogsRef')) {
                        Alpine.store('timeLogsRef').fetchLogs();
                    }

                    // // ✅ Force table refresh now
                    // if (window.timeLogsComponent) {
                    //     window.timeLogsComponent.fetchLogs(1);
                    // }
                } else {
                    this.message = data.message || 'Something went wrong.';
                }
            } catch (e) {
                console.error(e);
                this.message = 'Network error.';
            } finally {
                this.loading = false;
            }
        }
    }));
});
</script>
