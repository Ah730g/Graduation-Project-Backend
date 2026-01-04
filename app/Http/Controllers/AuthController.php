<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignupRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function signup(SignupRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            "name" => $data["name"],
            "email" => $data["email"],
            "password" => bcrypt($data["password"]),
            "role" => "admin",
            "status" => "active"
        ]);
        auth()->login($user);
        $token = $user->createToken("user_token")->plainTextToken;
        $userDTO = new UserResource($user);
        return response(compact("userDTO","token"),201);
    }
    public function login(LoginRequest $request)
    {
        $data = $request->validated();
        $user = User::where("email",$data["email"])->first();
        if(!$user)
            return response(['message' => "User Not Found"],404);
        if(!Hash::check($data["password"],$user->password))
            return response(["message" => "password is not correct"],404);
        auth()->login($user);
        $userDTO = new UserResource($user);
        $token = $user->createToken("user_token")->plainTextToken;
        return response(compact("userDTO","token"),200);
    }
    public function Logout(Request $request)
    {
        /** @var \app\Models\User $user */
        $user = $request->user();
        $user->tokens()->delete();
        return response("",200);
    }
}
