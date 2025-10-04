<?php

namespace App\View\Components\TimeComponent;

use Illuminate\View\Component;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $todayLog;

    /**
     * Create a new component instance.
     */
    public function __construct()
    {
        $this->todayLog = TimeLog::where('user_id', Auth::id())
            ->whereDate('clock_in', now()->toDateString())
            ->first();
    }

    public function render()
    {
        return view('components.time-component.index', [
            'todayLog' => $this->todayLog
        ]);
    }
}
