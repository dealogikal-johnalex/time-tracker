<div x-data="timeLogs()" x-init="fetchLogs()">
    <h2 class="text-xl font-bold mb-4">Time Logs</h2>

    <template x-if="logs.length > 0">
        <table class="table-auto w-full">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="log in logs" :key="log.id">
                    <tr>
                        <td x-text="log.employee"></td>
                        <td x-text="log.date"></td>
                        <td x-text="log.time_in"></td>
                        <td x-text="log.time_out"></td>
                    </tr>
                </template>
            </tbody>
        </table>
    </template>

    <template x-if="logs.length === 0">
        <p class="text-gray-400">No logs found.</p>
    </template>
</div>
