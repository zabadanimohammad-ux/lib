<?php

namespace App\Http\Controllers;

use FactoryMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function __construct() {
        $this->middleware('auth:api' , ['except' => ['login' , 'register']]);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (! $token = auth('api')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
            return response()->json([
                'access_token' =>$token,
                'token_type' =>'bearer',
                'expires_in' => JWTAuth::factory()->getTTL()*60,
                'user' => auth('api')->user()

            ]);
    }

    public function register(Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|between:7,10',
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)]
        ));
//        $token = JWTAuth::login($user);
        $token = JWTAuth::fromUser($user);



        return response()->json([
                'access_token' =>$token,
                'token_type' =>'bearer',
                'expires_in' => JWTAuth::factory()->getTTL()*60,
                'user' => $user

            ], 201);
    }

    public function logout() {
        auth('api')->logout();
        return response()->json(['message' => 'user successfully signed out']);
    }

    public function userProfile() {
        return response()->json(auth('api')->user());
    }
}
