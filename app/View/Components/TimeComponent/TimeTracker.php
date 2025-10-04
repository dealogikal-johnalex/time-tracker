<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

use App\Models\TimeLog;

class TimeTracker extends Component
{
    public $todayLog;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->todayLog = TimeLog::whereDate('created_at', today())->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.time-component.index');
    }
}
