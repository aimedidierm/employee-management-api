<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Events\EmployeeCreatedEvent;
use App\Jobs\SendResetLinkToManagerJob;
use App\Models\Employee;
use App\Services\CodeGenerator;
use App\Services\EmployeeCodeGenerator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function me(Request $request)
    {
        return $request->user();
    }

    public function login(Request $request)
    {
        $this->validate(
            $request,
            [
                "password" => "string|required",
                "email" => "email|required",
            ]
        );

        $login = $this->managerGuard()->attempt([
            "email" => $request->email,
            "password" => $request->password
        ]);

        if (!$login) {
            return Response::json([
                "message" => "Wrong Username/Password",
                "status" => HttpFoundationResponse::HTTP_UNAUTHORIZED,
            ], HttpFoundationResponse::HTTP_UNAUTHORIZED);
        }

        /** @var \App\Models\Employee */
        $manager = auth("employee")->user();
        $token = $manager->createToken('API Token')->plainTextToken;

        return Response::json([
            "message" => "Login success",
            "data" => [
                "token" => $token,
                'user' => $manager
            ],
            "status" => HttpFoundationResponse::HTTP_OK,
        ], HttpFoundationResponse::HTTP_OK);
    }

    public function signup(Request $request)
    {
        $this->validateSignup($request);

        $employeeDetails = $request->all();

        $employeeDetails = $this->prepareEmployeeDetails($employeeDetails, $request->password);

        /** @var \App\Models\Employee */
        $employee = Employee::create($employeeDetails);

        if (!$employee) {
            return $this->unprocessableEntityResponse("Failed to create user due to an unexpected error");
        }


        $token = $employee->createToken('API Token')->plainTextToken;

        return response()->json([
            "data" => [
                "token" =>  $token,
                "user" => $employee,
            ],
            "message" => $employee->name . " successfully created",
            "status" => HttpFoundationResponse::HTTP_OK,
        ]);
    }

    public function resetPassword(Request $request, string $reset_code)
    {
        $this->validateResetPassword($request);

        /** @var \App\Models\Employee */
        $employee = Employee::manager()
            ->whereResetCode($reset_code)
            ->firstOrFail();

        $this->validateResetCodeExpiry($employee);

        $employee->password = Hash::make($request->password);
        $employee->reset_code = null;
        $employee->reset_code_expires_in = null;

        if (!$employee->save()) {
            abort(HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR, "Unexpected error, try again later");
        }

        return Response::json([
            "message" => "Password reset success",
            "status" => HttpFoundationResponse::HTTP_OK,
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $this->validate($request, [
            "email" => "email|required",
        ]);

        $employee = Employee::manager()
            ->whereEmail($request->email)
            ->first();

        if (!$employee) {
            return $this->notFoundResponse("There is no account registered with that email");
        }

        $uuid =  Str::uuid();

        $employee->reset_code = $uuid;
        $employee->reset_code_expires_in = Carbon::now()->addHours(2);

        if (!$employee->save()) {
            return $this->internalServerErrorResponse("Failed to reset password");
        }

        dispatch(new SendResetLinkToManagerJob($employee));

        return response()->json([
            "message" => "Reset link was sent to your " . $employee->email . " Account",
            "status" => HttpFoundationResponse::HTTP_OK,
        ], HttpFoundationResponse::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return Response::json([
            "message" => "Logout success",
            "status" => HttpFoundationResponse::HTTP_OK,
        ], HttpFoundationResponse::HTTP_OK);
    }

    public function viewResetPage(Request $request, string $reset_code)
    {
        $employee = Employee::manager()
            ->whereResetCode($reset_code)
            ->firstOrFail();

        $this->validateResetCodeExpiry($employee);

        return view("pages.reset_page")->with("reset_code", $reset_code);
    }

    private function managerGuard()
    {
        return Auth::guard("employee");
    }

    private function validateLogin(Request $request)
    {
        $this->validate($request, [
            "password" => "string|required",
            "email" => "email|required",
        ]);
    }

    private function validateSignup(Request $request)
    {
        $this->validate($request, [
            "name" => "string|required",
            "email" => "email|required|unique:employees,email",
            "phone" => "unique:employees,phone",
            "national_id" => "required|unique:employees,national_id",
            "dob" => "date|required",
            "password" => "string|min:6|required"
        ]);
    }

    private function prepareEmployeeDetails(array $employeeDetails, string $password)
    {
        $employeeDetails["status"] = UserStatus::ACTIVE->value;
        $employeeDetails["position"] = UserType::MANAGER->value;
        $employeeDetails["password"] = Hash::make($password);
        $employeeDetails["code"] = EmployeeCodeGenerator::generate();
        $employeeDetails["dob"] = Carbon::parse($employeeDetails["dob"])->format('Y-m-d');

        return $employeeDetails;
    }

    private function validateResetPassword(Request $request)
    {
        $this->validate($request, [
            "password" => "string|min:6|required",
            "confirm_password" => "string|same:password"
        ]);
    }

    private function validateResetCodeExpiry(Employee $employee)
    {
        $expires_in = Carbon::parse($employee->reset_code_expires_in);

        if ($expires_in->lt(Carbon::now())) {
            abort(HttpFoundationResponse::HTTP_NOT_FOUND, "Invalid reset code or it is expired, try requesting new reset link");
        }
    }

    private function unauthorizedResponse()
    {
        return Response::json([
            "message" => "Wrong Username/Password",
            "status" => HttpFoundationResponse::HTTP_UNAUTHORIZED,
        ], HttpFoundationResponse::HTTP_UNAUTHORIZED);
    }

    private function forbiddenResponse()
    {
        return Response::json([
            "message" => "This account has been suspended!",
            "status" => HttpFoundationResponse::HTTP_FORBIDDEN,
        ], HttpFoundationResponse::HTTP_FORBIDDEN);
    }

    private function unprocessableEntityResponse($message)
    {
        return Response::json([
            "errors" => [
                "message" => [$message]
            ],
            "status" => HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY,
        ], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function notFoundResponse($message)
    {
        return Response::json([
            "error" => $message,
            "status" => HttpFoundationResponse::HTTP_NOT_FOUND,
        ], HttpFoundationResponse::HTTP_NOT_FOUND);
    }

    private function internalServerErrorResponse($message)
    {
        return Response::json([
            "error" => $message,
            "status" => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        ], HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
