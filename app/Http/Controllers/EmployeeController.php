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
    /**
     * @OA\Get(
     *     path="/api/v1/employees/get/{employee_code}",
     *     summary="Get single employee by code",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="employee_code",
     *         in="path",
     *         description="Employee code",
     *         required=true,
     *         example="EMP1234",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="data", ref="#/components/schemas/Employee"),
     *                 @OA\Property(property="status", type="integer", example=200)
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/employees/create",
     *     summary="Create a new employee",
     *     tags={"Employees"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeCreateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee successfully created"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable entity",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to create user"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Patch(
     *     path="/api/v1/employees/update/{employee_code}",
     *     summary="Update an existing employee",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="employee_code",
     *         in="path",
     *         description="Employee code",
     *         required=true,
     *         example="EMP1234",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee successfully updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee successfully updated"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Employee not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to update user"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Delete(
     *     path="/api/v1/employees/delete/{employee_code}",
     *     summary="Delete an existing employee",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="employee_code",
     *         in="path",
     *         description="Employee code",
     *         required=true,
     *         example="EMP1234",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Employee successfully deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Employee successfully deleted"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Employee not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Employee not found"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to delete user"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Get(
     *     path="/api/v1/employees/search",
     *     summary="Search employees",
     *     tags={"Employees"},
     *     @OA\Parameter(
     *         name="position",
     *         in="query",
     *         description="Position of the employee",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Name of the employee",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="email",
     *         in="query",
     *         description="Email of the employee",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="phone",
     *         in="query",
     *         description="Phone number of the employee",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="code",
     *         in="query",
     *         description="Code of the employee",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of results per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Employee")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Failed to fetch employees"),
     *             @OA\Property(property="status", type="integer", example=500)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/employees/details",
     *     summary="Get employee details",
     *     tags={"Employees"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/EmployeeUpdateRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             properties={
     *                 @OA\Property(property="dob", type="string", format="date"),
     *                 @OA\Property(property="national_id", type="string"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="status", type="string", enum={"active", "inactive"}),
     *                 @OA\Property(property="position", type="string", enum={"manager", "employee"}),
     *             }
     *         )
     *     )
     * )
     */
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
