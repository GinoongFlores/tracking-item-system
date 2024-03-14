<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                Rule::in(['super_admin', 'admin', 'user']),
                function ($attribute, $value, $fail) {
                    if ($value === "super_admin" && User::whereHas('roles', function($query) {
                        $query->where('role_name', 'super_admin');
                    })->count() > 0) {
                     $fail("The $attribute can only be one Super Admin");
                    //  return $this->sendError(['error' => $fail], 400);
                    }
                },
            ],
        ]);

        // company name is required to users and admin, and not on super admin
        $validator->sometimes('company_name', 'required', function ($input) {
            return $input->role !== 'super_admin';
        });

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        // set default values for a user and role
        $user = null;
        $role = null;
        try {

                // create the user
                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                // $input['company_id'] = $company->id;

                // start a database transaction
                DB::beginTransaction();
                $user = User::create($input);

                // attach role
                $role = Role::where('role_name', $request->role)->first(); // get role

                if($role === null) {
                    // roll back the transaction in case of an error
                    DB::rollback();
                    return $this->sendError("Role not found", 400);
                }

                // attach role to user
                $user->roles()->attach($role->id);

                // if the role is user or admin, attach the company
                if(in_array($role->role_name,['user', 'admin'])) {
                     $company = Company::where('company_name', $request->company_name)->first(); // get company

                     if(!$company) {
                        // roll back incase of an error
                        DB::rollBack();
                        return $this->sendError(['error' => 'Company does not exist'], 400);
                     }
                     // attach company to user
                     $user->company_id = $company->id;
                     $user->save();
                }

                // commit the transaction
                DB::commit();

        } catch (\Exception $e) {
            // roll back the transaction in case of an error
            DB::rollBack();
            //return an error response
            return $this->sendError(['error' => $e->getMessage()], 500);
        }

        if ($user === null) {
            return $this->sendError(['error' => 'User not created'], 500);
        }

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

