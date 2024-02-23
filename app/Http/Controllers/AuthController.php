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
    /**
     * @OA\Get(
     *     path="/api/v1/auth/user",
     *     summary="Get authenticated user details",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(ref="#/components/schemas/User")
     *     )
     * )
     */
    public function me(Request $request)
    {
        return $request->user();
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Authenticate user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Login success"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="token_string"),
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             ),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Wrong Username/Password"),
     *             @OA\Property(property="status", type="integer", example=401)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/signup",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "dob"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *             @OA\Property(property="dob", type="string", format="date", example="1990-01-01")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="token_string"),
     *                 @OA\Property(property="user", ref="#/components/schemas/User")
     *             ),
     *             @OA\Property(property="message", type="string", example="User successfully created"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Unprocessable Entity",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="integer", example=422)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/reset-link/{reset_code}",
     *     summary="Reset user password",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password", "confirm_password"},
     *             @OA\Property(property="password", type="string", format="password", example="newpassword123"),
     *             @OA\Property(property="confirm_password", type="string", format="password", example="newpassword123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Password reset success"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid reset code or it is expired"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/request-reset-link",
     *     summary="Request a reset password link",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Reset link was sent to your email"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="There is no account registered with that email"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout success"),
     *             @OA\Property(property="status", type="integer", example=200)
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        return Response::json([
            "message" => "Logout success",
            "status" => HttpFoundationResponse::HTTP_OK,
        ], HttpFoundationResponse::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/reset-link/{reset_code}",
     *     summary="View password reset page",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\MediaType(
     *             mediaType="text/html",
     *             example="<!DOCTYPE html><html><head>...</head><body>...</body></html>"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not Found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid reset code or it is expired"),
     *             @OA\Property(property="status", type="integer", example=404)
     *         )
     *     )
     * )
     */
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

    /**
     * Validate the reset code expiry.
     *
     * @param \App\Models\Employee $employee
     * @return void
     */
    private function validateResetCodeExpiry(Employee $employee)
    {
        $expires_in = Carbon::parse($employee->reset_code_expires_in);

        if ($expires_in->lt(Carbon::now())) {
            abort(HttpFoundationResponse::HTTP_NOT_FOUND, "Invalid reset code or it is expired, try requesting new reset link");
        }
    }

    /**
     * Handle unauthorized response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function unauthorizedResponse()
    {
        return Response::json([
            "message" => "Wrong Username/Password",
            "status" => HttpFoundationResponse::HTTP_UNAUTHORIZED,
        ], HttpFoundationResponse::HTTP_UNAUTHORIZED);
    }

    /**
     * Handle forbidden response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    private function forbiddenResponse()
    {
        return Response::json([
            "message" => "This account has been suspended!",
            "status" => HttpFoundationResponse::HTTP_FORBIDDEN,
        ], HttpFoundationResponse::HTTP_FORBIDDEN);
    }

    /**
     * Handle unprocessable entity response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    private function unprocessableEntityResponse($message)
    {
        return Response::json([
            "errors" => [
                "message" => [$message]
            ],
            "status" => HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY,
        ], HttpFoundationResponse::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Handle not found response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    private function notFoundResponse($message)
    {
        return Response::json([
            "error" => $message,
            "status" => HttpFoundationResponse::HTTP_NOT_FOUND,
        ], HttpFoundationResponse::HTTP_NOT_FOUND);
    }

    /**
     * Handle internal server error response.
     *
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    private function internalServerErrorResponse($message)
    {
        return Response::json([
            "error" => $message,
            "status" => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        ], HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}
