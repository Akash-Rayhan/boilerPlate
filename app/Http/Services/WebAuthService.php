<?php


namespace App\Http\Services;


use App\Http\Repository\UserRepository;
use App\Jobs\SendForgetPasswordEmailJob;
use App\Jobs\SendVerificationEmailJob;
use App\Models\MobileDevice;
use App\Models\PasswordReset;
use App\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WebAuthService extends CommonService
{
    public $repository;

    /**
     * ProfileService constructor.
     */
    function __construct()
    {
        $this->repository = new UserRepository();
        parent::__construct($this->repository);
    }

    /**
     * @param $request
     * @return array
     */
    public function signInProcess($request)
    {
        $credentials = $this->credentials($request->except('_token'));
        $valid = Auth::attempt($credentials);
        if ($valid) {
            $user = Auth::user();
            if ($user->role == SUPER_ADMIN_ROLE) {// || $user->role == ADMIN_ROLE || $user->role == USER_ROLE . Admin and user web features are disabled for this app
                return [
                    'success' => true,
                    'message' => __('Congratulations! You have signed in successfully.'),
                    'data' => $user
                ];
            } else {
                Auth::logout();

                return [
                    'success' => false,
                    'message' => __('You are not authorized'),
                    'data' => null
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => __('Email or password is incorrect'),
                'data' => null
            ];
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function credentials($data)
    {
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'email' => $data['email'],
                'password' => $data['password']
            ];
        } else {
            return [
                'user_name' => $data['email'],
                'password' => $data['password']
            ];
        }
    }

    /**
     * @param $request
     * @return array
     */
    public function sendForgetPasswordEmail($request)
    {
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $where = ['email' => $request->email];
        } else {
            $where = ['user_name' => $request->email];
        }
        $user = User::where($where)->first();
        if (empty($user)) {
            return [
                'success' => false,
                'message' =>  __('User not found'),
                'data' => null
            ];
        }
        if ($user->role == ADMIN_ROLE) {
            if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                return [
                    'success' => false,
                    'message' =>  __('Please enter your username instead of email'),
                    'data' => null
                ];
            }
        }

        $randNo = randomNumber(6);
        try {
            $defaultEmail = 'boilerplate@email.com';
            $defaultName = 'Boiler Plate';
            $logo =  asset('assets/images/laravelLogo.png');
            dispatch(new SendForgetPasswordEmailJob($randNo, $defaultName, $logo, $user, $defaultEmail));
            PasswordReset::create([
                'user_id' => $user->id,
                'verification_code' => $randNo
            ]);
        } catch (\Exception $exception) {
            return [
                'success' => false,
                'message' =>  __('Something went wrong. Please try again'),
                'data' => null
            ];
        }

        return [
            'success' => true,
            'message' =>  __('Code has been sent to ') . ' ' . $user->email,
            'data' => null
        ];
    }

    /**
     * @param $request
     * @return array
     */
    public function resetPassword($request)
    {
        $passwordResetCode = PasswordReset::where(['verification_code' => $request->reset_password_code, 'status' => PENDING_STATUS])->first();
        if (!empty($passwordResetCode)) {
            $latestResetCode = PasswordReset::where(['user_id' => $passwordResetCode->user_id, 'status' => PENDING_STATUS])->orderBy('id', 'desc')->first();
            if (($latestResetCode->verification_code != $request->reset_password_code)) {
                return [
                    'success' => false,
                    'message' =>   __('Your given reset password code is incorrect'),
                    'data' => null
                ];
            }
        } else {
            return [
                'success' => false,
                'message' =>   __('Your given reset password code is incorrect'),
                'data' => null
            ];
        }

        if (!empty($passwordResetCode)) {
            $totalDuration = Carbon::now()->diffInMinutes($passwordResetCode->created_at);
            if ($totalDuration > EXPIRE_TIME_OF_FORGET_PASSWORD_CODE) {
                return [
                    'success' => false,
                    'message' =>  __('Your code has been expired. Please give your code with in') . EXPIRE_TIME_OF_FORGET_PASSWORD_CODE . __('minutes'),
                    'data' => null
                ];
            }
            $user = User::where('id', $passwordResetCode->user_id)->first();
            if (empty($user)) {
                return [
                    'success' => false,
                    'message' =>  __('User not found'),
                    'data' => null
                ];
            }
            $user->password = Hash::make($request->new_password);
            $user->update();
            $passwordResetCode->status = ACTIVE_STATUS;
            $passwordResetCode->update();

            return [
                'success' => true,
                'message' =>  __('Password is reset successfully'),
                'data' => null
            ];
        }

        return [
            'success' => false,
            'message' =>   __('Your given reset password code is incorrect'),
            'data' => null
        ];
    }
}
