<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
    $request->validated();
    \Illuminate\Support\Facades\Log::info('Login attempt received', ['email' => $request->email, 'password' => $request->password]);

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }
    if ($user->role === 'student' && !$user->student) {
    return response()->json([
        'message' => 'Account not activated yet'
    ], 403);
    }


    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'token' => $token,
        'user' => $user,
        'role' => $user->role,
        'redirect_to' => $this->getRedirectPath($user->role)
    ]);
    }


    private function getRedirectPath($role)
    { 
    return match (strtolower($role)) {
        'student' => '/student/dashboard',
        'admin' => '/admin/dashboard',
        'advisor' => '/advisor/dashboard',
        default => '/login'
    };
    }



    public function logout(Request $request)
    {
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => 'Logged out']);
    }



    public function register(RegisterRequest $request)
    {
        
        $request->validated();

       
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',
    
        ]);

       
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token,
            'redirect_to' => 'auth/login'
        ]);
    }
}





