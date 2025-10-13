<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use App\Models\TimeLog;

class TimeLogButton extends Component
{
    public $lastLog;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $today = now()->toDateString();

        $lastLog = TimeLog::where('user_id', auth()->id())
            ->whereDate('created_at', $today)
            ->latest()
            ->first();

        if ($lastLog && $lastLog->created_at->toDateString() === $today) {
            $this->lastLog = $lastLog;
        } else {
            $this->lastLog = null;
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.time-log-button', ['todayLog' => $this->lastLog]);
    }
}

