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
            'company_name' => 'required|max:255',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'role' => [
                'required',
                Rule::in(['Super Admin', 'Admin', 'User']),
                function ($attribute, $value, $fail) {
                    if ($value === "Super Admin" && User::whereHas('roles', function($query) {
                        $query->where('role_name', 'Super Admin');
                    })->count() > 0) {
                     $fail("The $attribute can only be one Super Admin");
                    //  return $this->sendError(['error' => $fail], 400);
                    }
                },
            ],
        ]);

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }        // create the user

        // add a company name only for Super Admin and Admin
        try {
            if(in_array($request->role, ['Super Admin', 'Admin'])) {
                // look for company name
                $company = Company::where('company_name', $request->company_name)->first();

                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                $input['company_id'] = $company->id;

                if (!$company) {
                    # return $this->sendError('Company not found', 400);
                    return response()->json(['error' => 'Company does not exist'], 400);
                }

                // start a database transaction
                DB::beginTransaction();
                $user = User::create($input);

                // commit the transaction
                DB::commit();

            } else {
                // if the user is not a Super Admin or Admin, they must select an existing company
                $company = Company::where('company_name', $request->company_name)->first();

                if (!$company) {
                    # return $this->sendError('Company not found', 400);
                    return response()->json(['error' => 'Company does not exist'], 400);
                }

                // create the user
                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                $input['company_id'] = $company->id;

                // start a database transaction
                DB::beginTransaction();
                $user = User::create($input);

                // commit the transaction
                DB::commit();
            }

            // create the user
        } catch (\Exception $e) {
            // roll back the transaction in case of an error
            DB::rollBack();
            //return an error response
            return $this->sendError(['error' => $e->getMessage()], 500);
        }

         // attach role
         $role = Role::where('role_name', $request->role)->first(); // get role
         if($role === null) {
            return $this->sendError("Role not found", 400);
        }

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

