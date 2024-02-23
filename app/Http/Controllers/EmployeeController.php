<?php

namespace App\Http\Controllers;

use App\Enums\UserType;
use App\Models\Employee;
use App\Services\EmployeeCodeGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;

class EmployeeController extends Controller
{
    public function single(Request $request)
    {
        $employee = Employee::find($request->employee_id);

        if (!$employee) {
            return response()->json([
                "message" => "Employee not found",
                "status" => HttpFoundationResponse::HTTP_NOT_FOUND,
            ], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            "data" => $employee,
            "status" => HttpFoundationResponse::HTTP_OK,
        ]);
    }

    public function store(Request $request)
    {
        $this->validateEmployee($request);

        $employeeDetails = $this->getEmployeeDetails($request);

        $employeeDetails["code"] = EmployeeCodeGenerator::generate();

        $employee = Employee::create($employeeDetails);

        if ($employee) {
            $this->logActivity('Created an employee', $employee);

            return response()->json([
                "message" => $employee->name . " successfully created",
                "status" => HttpFoundationResponse::HTTP_OK,
            ]);
        }

        return response()->json([
            "error" => "Failed to create user",
            "status" => HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY,
        ], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function update(Request $request, string $employee_code)
    {
        $this->validateEmployee($request);

        $employeeDetails = $this->getEmployeeDetails($request);

        $employee = Employee::whereCode($employee_code)->first();

        if (!$employee) {
            return response()->json([
                "error" => "Employee not found",
                "status" => HttpFoundationResponse::HTTP_NOT_FOUND,
            ], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        if ($employee->update($employeeDetails)) {
            $this->logActivity('Updated an employee', $employee);
            return response()->json([
                "message" => $employee->name . " successfully updated",
                "status" => HttpFoundationResponse::HTTP_OK,
            ]);
        }

        return response()->json([
            "error" => "Failed to update user",
            "status" => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        ], HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    public function destroy(Request $request, string $employee_code)
    {
        $employee = Employee::whereCode($employee_code)->first();

        if (!$employee) {
            return response()->json([
                "error" => "Employee not found",
                "status" => HttpFoundationResponse::HTTP_NOT_FOUND,
            ], HttpFoundationResponse::HTTP_NOT_FOUND);
        }

        $employee->attendance()->delete();

        if (!$employee->delete()) {
            return response()->json([
                "error" => "Failed to delete user",
                "status" => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
            ], HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
        }


        return response()->json([
            "message" => $employee->name . " successfully deleted",
            "status" => HttpFoundationResponse::HTTP_OK,
        ]);
    }

    public function search(Request $request)
    {
        $query = Employee::with("todayAttendance")
            ->orderBy("created_at", "DESC");

        if ($request->position) {
            $query->wherePosition($request->position);
        }

        if ($request->name) {
            $query->where("name", "LIKE", $request->name . "%");
        }

        if ($request->email) {
            $query->where("email", "LIKE", $request->email . "%");
        }

        if ($request->phone) {
            $query->where("phone", "LIKE", $request->phone . "%");
        }

        if ($request->code) {
            $query->whereCode($request->code);
        }

        $employees = $query->paginate($request->limit ?? 15);

        if ($employees) {
            return $employees;
        }

        return response()->json([
            "error" => "Failed to fetch employees",
            "status" => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        ], HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    private function validateEmployee(Request $request)
    {
        return $this->validate($request, [
            "name" => "string|required",
            "email" => "email|required|unique:employees,email",
            "phone" => "required|unique:employees,phone",
            "national_id" => "required|unique:employees,national_id",
            "position" => [Rule::in(array_values((new ReflectionClass(UserType::class))->getConstants())), "required"],
            "status" => [Rule::in(array_values((new ReflectionClass(UserType::class))->getConstants())), "required"],
            "dob" => "date|required",
        ]);
    }

    private function getEmployeeDetails(Request $request)
    {
        $employeeDetails = $request->only([
            "dob", "national_id", "phone",
            "email", "name", "status", "position"
        ]);

        $employeeDetails["dob"] = Carbon::parse($request->dob)->format("Y-m-d");

        return $employeeDetails;
    }
}
