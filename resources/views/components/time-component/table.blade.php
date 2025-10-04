<div x-data="timeLogs()" x-init="window.timeLogsComponent = $data; fetchLogs(1)" class="mt-10">

    <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-200 text-center md:text-left">Time Logs</h2>

    <!-- 📅 Filters -->
    <div class="mb-4 flex justify-end">
        <button 
            @click="showFilters = !showFilters" 
            class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-white rounded transition"
            x-text="showFilters ? 'Hide Filters' : 'Show Filters'">
        </button>
    </div>
    <div 
        class="flex flex-col md:flex-row md:items-end sm:justify-between bg-gray-900 p-4 rounded-lg mb-6 gap-4"
        x-show="showFilters"
        x-transition
    >
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-300 mb-1">Start Date</label>
                <input type="date" x-model="start_date" class="border rounded px-2 py-1 text-black" />
            </div>
            <div>
                <label class="block text-sm text-gray-300 mb-1">End Date</label>
                <input type="date" x-model="end_date" class="border rounded px-2 py-1 text-black" />
            </div>
        </div>
        <div class="flex space-x-2">
            <button @click="fetchLogs(1)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition">Filter</button>
            <button @click="resetFilters()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded transition">Reset</button>
        </div>
    </div>

    <template x-if="logs.length > 0">
        <div class="overflow-x-auto mb-8">
            <table class="min-w-full border mb-4 bg-white text-black rounded-lg overflow-hidden shadow">
                <thead>
                    <tr class="bg-gray-200 text-gray-800">
                        <th class="px-4 py-2 text-center">Date</th>
                        <th class="px-4 py-2 text-center">Time In</th>
                        <th class="px-4 py-2 text-center">Time Out</th>
                        <th class="px-4 py-2 text-center hidden lg:table-cell">Duration</th>
                        <!-- <th class="px-4 py-2 text-center">Status</th> -->
                        <th class="px-4 py-2 text-center hidden lg:table-cell">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="log in logs" :key="log.date">
                        <tr
                            :class="{
                                'bg-red-200 text-red-900 font-semibold': log.status === 'Absent',
                                'bg-yellow-100': log.remarks?.includes('Late') || log.remarks?.includes('Undertime'),
                                'bg-gray-100': log.status === 'Present' && !log.remarks
                            }"
                        >
                            <td class="px-4 py-2 text-center" x-text="formatDateOnly(log.date)"></td>
                            <td class="px-4 py-2 text-center" x-text="formatTime(log.clock_in)"></td>
                            <td class="px-4 py-2 text-center" x-text="formatTime(log.clock_out)"></td>
                            <td class="px-4 py-2 text-center hidden lg:table-cell" x-text="calculateDuration(log.clock_in, log.clock_out)"></td>
                            <!-- <td class="px-4 py-2 text-center font-bold" x-text="log.status"></td> -->
                            <td class="px-4 py-2 text-center hidden lg:table-cell" x-text="log.remarks || '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- 📄 Pagination Controls -->
            <div class="flex items-center justify-between text-gray-300 mt-4">
                <button 
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page <= 1"
                    class="px-4 py-2 bg-gray-700 text-white rounded disabled:opacity-50"
                >
                    Previous
                </button>

                <span class="text-sm">
                    Page <span x-text="pagination.current_page"></span> of <span x-text="pagination.last_page"></span> |
                    Showing <span x-text="pagination.from"></span> - <span x-text="pagination.to"></span> of 
                    <span x-text="pagination.total"></span>
                </span>

                <button 
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page >= pagination.last_page"
                    class="px-4 py-2 bg-gray-700 text-white rounded disabled:opacity-50"
                >
                    Next
                </button>
            </div>
        </div>
    </template>

    <p x-show="logs.length === 0" class="text-gray-400 italic">No time logs yet.</p>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('timeLogs', () => ({
        logs: [],
        pagination: { total: 0, per_page: 10, current_page: 1, last_page: 1, from: 0, to: 0 },
        start_date: '',
        end_date: '',
        showFilters: false,

        async fetchLogs(page = 1) {
            try {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', this.pagination.per_page);
                if (this.start_date) params.append('start_date', this.start_date);
                if (this.end_date) params.append('end_date', this.end_date);

                const res = await fetch("{{ route('time.logs') }}?" + params.toString());
                if (res.ok) {
                    const data = await res.json();
                    this.logs = data.data || [];
                    this.pagination = data.pagination || this.pagination;
                }
            } catch (e) {
                console.error('Failed to fetch logs:', e);
            }
        },

        changePage(newPage) {
            if (newPage >= 1 && newPage <= this.pagination.last_page) {
                this.fetchLogs(newPage);
            }
        },

        resetFilters() {
            this.start_date = '';
            this.end_date = '';
            this.fetchLogs(1);
        },

        formatDateOnly(dateString) {
            if (!dateString) return '—';
            const d = new Date(dateString);
            return isNaN(d)
                ? 'Invalid Date'
                : d.toLocaleDateString('en-PH', {
                    weekday: 'short',
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric'
                });
        },

        formatTime(timeString) {
            if (!timeString) return '—';
            return timeString;
        },

        calculateDuration(clockIn, clockOut) {
            if (!clockIn || !clockOut) return '—';

            const [h1, m1] = clockIn.split(':').map(Number);
            const [h2, m2] = clockOut.split(':').map(Number);

            let diffMinutes = (h2 * 60 + m2) - (h1 * 60 + m1);
            if (diffMinutes < 0) return '—';

            const hours = Math.floor(diffMinutes / 60);
            const minutes = diffMinutes % 60;

            return `${hours}h ${minutes}m`;
        }
        
    }));

    // ✅ Global store that references the table component
    Alpine.store('timeLogsRef', {
        fetchLogs: async () => {
            if (window.timeLogsComponent) {
                await window.timeLogsComponent.fetchLogs();
            }
        }
    });
});
</script>
