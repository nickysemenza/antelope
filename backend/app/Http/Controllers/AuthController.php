<?php
namespace App\Http\Controllers;
use Auth;
use Hash;
use Mail;
use JWTAuth;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Mail\UserRegistration;
use App\Http\Controllers\Controller;
/**
 * Class AuthController.
 */
class AuthController extends Controller
{
    /**
     * @param  Request $request: email, password
     */
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');
        $validator = Validator::make($credentials, [
            'email' => 'required|email',
            'password'   => 'required',
        ]);
        if ($validator->fails()) {
            return response()->error($validator->errors()->all());
        } else {
            if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
                $token = Auth::user()->getToken();
                return response()->success(compact('token'));
            }
        }
        return response()->error('Invalid credentials');
    }
}