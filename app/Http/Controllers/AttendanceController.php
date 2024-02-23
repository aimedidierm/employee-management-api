<?php

namespace App\Http\Controllers;

use App\Events\EmployeeAttendanceRecordedEvent;
use App\Models\Attendance;
use App\Http\Requests\StoreAttendanceRequest;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;
use Illuminate\Support\Facades\View;
use Barryvdh\Snappy\Facades\SnappyPdf;

class AttendanceController extends Controller
{

    public function registerAttendance(StoreAttendanceRequest $request)
    {
        $employee = Employee::find($request->employee_id);
        $date = now();

        $attendance = Attendance::firstOrNew([
            'employee_id' => $employee->id,
            'arrived_at' => $date->format('Y-m-d'),
        ]);

        $wasFirstAttendance = !$attendance->exists;

        if (!$attendance->exists) {
            $attendance->save();
        } elseif (!$attendance->left_at) {
            $attendance->left_at = $date;
            $attendance->save();
        } else {
            return $this->attendanceAlreadyRegisteredResponse($attendance);
        }

        event(new EmployeeAttendanceRecordedEvent($employee, $attendance));

        return $this->attendanceRegisteredResponse($attendance);
    }

    public function getAttendance(Request $request)
    {
        $from = Carbon::parse($request->from)->addDay();
        $to = Carbon::parse($request->to)->addDay();

        $attendances = Attendance::whereDate("arrived_at", ">=", $from)
            ->whereDate("arrived_at", "<=", $to)
            ->whereNotNull("arrived_at")
            ->with("employee:id,name,code")
            ->orderByDesc("arrived_at")
            ->get()
            ->groupBy(function ($data) {
                return Carbon::parse($data->arrived_at)->format("Y-m-d");
            })
            ->map(function ($groupedAttendances, $date) {
                return [
                    "date" => $date,
                    "data" => $groupedAttendances->map(function ($attendance) {
                        return [
                            "time_arrived_at" => optional($attendance->arrived_at)->format("h:i:s A"),
                            "time_left_at" => optional($attendance->left_at)->format("h:i:s A"),
                        ];
                    }),
                ];
            })->values();

        return response()->json([
            "status" => 200,
            "data" => $attendances,
        ]);
    }

    public function exportAttendanceExcel(Request $request)
    {
        $from = Carbon::parse($request->from)->addDay()->format("Y-m-d");
        $to = Carbon::parse($request->to)->addDay()->format("Y-m-d");

        return Excel::download(new AttendanceExport($from, $to), 'attendance.xlsx');
    }

    public function exportAttendancePdf(Request $request)
    {
        $from = Carbon::parse($request->from)->addDay()->format("Y-m-d");
        $to = Carbon::parse($request->to)->addDay()->format("Y-m-d");

        $attendances = Attendance::whereDate("arrived_at", ">=", $from)
            ->whereDate("arrived_at", "<=", $to)
            ->whereNotNull("arrived_at")
            ->with("employee:id,name,code")
            ->orderByDesc("arrived_at")
            ->get();

        $html = View::make('exports.attendance', compact('attendances'))->render();

        $pdf = SnappyPdf::loadHTML($html);

        return $pdf->download("export_{$from}_{$to}.pdf");
    }

    private function attendanceRegisteredResponse($attendance)
    {
        return Response::json([
            "status" => 200,
            "data" => $attendance,
            "message" => "Attendance successfully registered",
        ]);
    }

    private function attendanceAlreadyRegisteredResponse($attendance)
    {
        return Response::json([
            "status" => 200,
            "data" => $attendance,
            "message" => "Attendance is already registered for this date.",
        ]);
    }
}
