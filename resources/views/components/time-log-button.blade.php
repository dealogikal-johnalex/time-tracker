@php
    // Helper function to get label and action based on status
    function getTimeLogStatus($todayLog) {
        if (!$todayLog) {
            return ['label' => 'Clock In', 'action' => 'clock_in', 'status' => null];
        }
        switch ($todayLog->status) {
            case 'clocked_in':
                return ['label' => 'Break In', 'action' => 'break_in', 'status' => 'clocked_in'];
            case 'on_break':
                return ['label' => 'Break Out', 'action' => 'break_out', 'status' => 'on_break'];
            case 'break_completed':
                return ['label' => 'Clock Out', 'action' => 'clock_out', 'status' => 'break_completed'];
            case 'done':
                return ['label' => 'Already Clocked Out', 'action' => '', 'status' => 'done'];
            default:
                return ['label' => 'Clock In', 'action' => 'clock_in', 'status' => null];
        }
    }

    $timeLogStatus = getTimeLogStatus($todayLog);
@endphp

<div class="mt-2 text-center space-y-6" x-data="timeTrackerComponent()">
    <input type="hidden" id="latitude" name="latitude">
    <input type="hidden" id="longitude" name="longitude">
    <input type="hidden" id="location" name="location">

    <button 
        @click.prevent="handleClick"
        :disabled="disabled || loading"
        class="px-6 py-3 rounded-lg text-white font-semibold transition flex items-center justify-center gap-2 w-40 mx-auto"
        :class="{
            'bg-green-600 hover:bg-green-700': label === 'Clock In' && !disabled && !loading,
            'bg-yellow-500 hover:bg-yellow-600': label === 'Break In' && !disabled && !loading,
            'bg-blue-500 hover:bg-blue-600': label === 'Break Out' && !disabled && !loading,
            'bg-red-600 hover:bg-red-700': label === 'Clock Out' && !disabled && !loading,
            'bg-gray-500 cursor-not-allowed': disabled || loading
        }"
    >
        <template x-if="loading">
            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </template>
        <template x-if="!loading">
            <span x-text="label"></span>
        </template>
    </button>

    <p x-show="message" x-text="message" class="mt-4 text-green-600 font-semibold"></p>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('timeTrackerComponent', () => ({
            label: @json($timeLogStatus['label']),
            action: @json($timeLogStatus['action']),
            status: @json($timeLogStatus['status']),
            loading: false,
            disabled: @json($todayLog && $todayLog->clock_out ? true : false),
            message: '',

            async handleClick() {
                if (this.disabled || this.loading) return;

                this.loading = true;
                this.message = '';

                if (!navigator.geolocation) {
                    this.message = 'Geolocation is not supported by your browser.';
                    this.loading = false;
                    return;
                }

                try {
                    const pos = await new Promise((resolve, reject) => {
                        navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 10000 });
                    });

                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;

                    let location = '';
                    try {
                        const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                        const data = await res.json();
                        location = data.display_name || '';
                    } catch (e) {
                        location = '';
                    }

                    const url = this.action ? `/log-time/${this.action}` : '';
                    if (!url) {
                        this.message = 'Invalid action.';
                        this.loading = false;
                        return;
                    }

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

                        // 🟢 Optional: Let server tell us the next status
                        if (data.status) {
                            this.updateState(data.status);
                        } else {
                            // Fallback if backend does not return new status
                            this.updateState(this.getNextStatus(this.status));
                        }

                        if (window.Alpine?.store('timeLogsRef')) {
                            Alpine.store('timeLogsRef').fetchLogs();
                        }
                    } else {
                        this.message = data.message || 'Something went wrong.';
                    }

                } catch (e) {
                    this.message = 'Please enable location services to use the time tracker.';
                    console.error(e);
                } finally {
                    this.loading = false;
                }
            },

            // 🧠 Update UI based on new status
            updateState(nextStatus) {
                switch (nextStatus) {
                    case 'clocked_in':
                        this.label = 'Break In';
                        this.action = 'break_in';
                        this.status = 'clocked_in';
                        break;
                    case 'on_break':
                        this.label = 'Break Out';
                        this.action = 'break_out';
                        this.status = 'on_break';
                        break;
                    case 'break_completed':
                        this.label = 'Clock Out';
                        this.action = 'clock_out';
                        this.status = 'break_completed';
                        break;
                    case 'done':
                        this.label = 'Already Timed Out';
                        this.action = '';
                        this.status = 'done';
                        this.disabled = true;
                        break;
                    default:
                        this.label = 'Clock In';
                        this.action = 'clock_in';
                        this.status = null;
                        break;
                }
            },

            // 🔁 Predict next status if backend doesn’t return it
            getNextStatus(current) {
                switch (current) {
                    case null: return 'clocked_in';
                    case 'clocked_in': return 'on_break';
                    case 'on_break': return 'break_completed';
                    case 'break_completed': return 'done';
                    default: return 'done';
                }
            }
        }));
    });
</script>
