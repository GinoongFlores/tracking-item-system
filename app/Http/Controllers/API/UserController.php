<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index () {
        $user = User::all();
        return response()->json($user->toArray(), 200);
    }

    public function currentUser () {
        return response()->json(Auth::user(), 200);
    }

    public function register (Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'phone' => 'numeric|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'role' => [
                'required',
                Rule::in(['Super Admin', 'Admin', 'User']),
                function ($attribute, $value, $fail) {
                    if ($value === "Super Admin" && User::whereHas('roles', function($query) {
                        $query->where('role_name', 'Super Admin');
                    })->count() > 0) {
                     $fail('There can only be one Super Admin');
                    //  return $this->sendError(['error' => $fail], 400);
                    }
                },
            ],
        ]);

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

         // attach role
         $role = Role::where('role_name', $request->role)->first(); // get role
         if($role === null) {
            return $this->sendError("Role not found", 400);
        }

        $input = $request->all();
        $input['password'] = bcrypt($input['password']);
        $user = User::create($input);

        $user->roles()->attach($role->id); // attach role to user

        // token
        $success['token'] = $user->createToken('MyApp')->accessToken;
        $success['first_name'] = $user->first_name;
        $success['role'] = $role->role_name;

        return $this->sendResponse($success, 'User registered successfully.');
    }

    public function login (Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|min:8',
        ]);

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400, false);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['first_name'] = $user->first_name;
            $token = $success['token'];
            $profile = $success['first_name'];
            $role = $user->roles->first()->role_name;

            return response()->json([
                'message' => 'Welcome '. $profile,
                'role' => $role,
                'status' => 'success',
                'token' => $token,
            ]);
        } else {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized'], 401, false);
        }
    }

    public function logout (Request $request): JsonResponse {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }
}

