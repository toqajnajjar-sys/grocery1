namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\ChangePasswordAction;
use App\Actions\Auth\DeleteAccountAction;
use App\Actions\Auth\ForgotPasswordAction;
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\LogoutAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\VerifyOtpAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\DeleteAccountRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Traits\V1\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return self::successResponse(
            'Registration successful',
            [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            201
        );
    }

    /**
     * Login user.
     */
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        $result = $action->execute($request->validated());

        return self::successResponse(
            'Login successfully',
            [
                'user'  => new UserResource($result['user']),
                'token' => $result['token'],
            ],
            200
        );
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        return self::successResponse(
            'User retrieved successfully',
            ['user' => new UserResource($request->user())],
            200
        );
    }

    /**
     * Logout user and revoke tokens.
     */
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $action->execute($request->user());

        return self::successResponse('Logout successfully', null, 200);
    }

    /**
     * Send OTP for forgot password.
     */
    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action): JsonResponse
    {
        $action->execute($request->input('identifier'));

        return self::successResponse('OTP sent successfully. Please check your email', null, 200);
    }

    /**
     * Verify OTP code.
     */
    public function verifyOtp(VerifyOtpRequest $request, VerifyOtpAction $action): JsonResponse
    {
        $isValid = $action->execute(
            $request->input('identifier'),
            $request->input('otp')
        );

        if (! $isValid) {
            return self::errorResponse('Invalid or expired OTP', null, 400);
        }

        return self::successResponse('OTP verified successfully', null, 200);
    }

    /**
     * Reset password using OTP.
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPasswordAction $action): JsonResponse
    {
        $action->execute(
            $request->input('identifier'),
            $request->input('otp'),
            $request->input('password')
        );

        return self::successResponse('Password reset successfully', null, 200);
    }

    /**
     * Change authenticated user password.
     */
    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $action->execute($request->user(), $request->input('password'));

        return self::successResponse('Password changed successfully', null, 200);
    }

    /**
     * Delete authenticated user account.
     */
    public function deleteAccount(DeleteAccountRequest $request, DeleteAccountAction $action): JsonResponse
    {
        $action->execute($request->user());

        return self::successResponse('Account deleted successfully', null, 200);
    }
}