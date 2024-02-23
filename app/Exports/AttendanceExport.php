<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AttendanceExport implements FromView, ShouldAutoSize
{
    protected $from;
    protected $to;

    public function __construct($from, $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public function view(): View
    {
        $attendances = Attendance::whereDate("arrived_at", ">=", $this->from)
            ->whereDate("arrived_at", "<=", $this->to)
            ->whereNotNull("arrived_at")
            ->with("employee:id,name,code")
            ->orderByDesc("arrived_at")
            ->get()
            ->map(function ($attendance) {
                return [
                    'date' => Carbon::parse($attendance->arrived_at)->format("Y-m-d"),
                    'code' => $attendance->employee->code,
                    'name' => $attendance->employee->name,
                    'arrived_at' => optional($attendance->arrived_at)->format("Y-m-d H:i:s"),
                    'left_at' => optional($attendance->left_at)->format("Y-m-d H:i:s"),
                ];
            });

        return view('exports.attendance', [
            'attendances' => $attendances
        ]);
    }
}
