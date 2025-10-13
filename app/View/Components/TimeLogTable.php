<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\TimeLog;

class TimeLogTable extends Component
{
    public $logs;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->logs = TimeLog::where('user_id', auth()->id())->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.time-log-table', [
            'logs' => $this->logs
        ]);
    }
}
