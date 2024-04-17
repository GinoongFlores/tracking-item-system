<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    //

    public function login (Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|min:8',
        ]);

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = Auth::user();
            $success['token'] = $user->createToken('MyApp')->accessToken;
            $success['first_name'] = $user->first_name;
            $token = $success['token'];
            $profile = $success['first_name'];
            $is_activated = $user->is_activated;
            // $role = $user->roles->first()->role_name;

            return response()->json([
                'message' => 'Welcome '. $profile,
                'status' => 'success',
                'token' => $token,
                'is_activated' => $is_activated,
            ]);
        } else {
            return $this->sendError('Unauthorized', ['error' => 'Unauthorized'], 401);
        }
    }


    public function register (Request $request): JsonResponse {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'phone' => 'numeric|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:8',
            'confirm_password' => 'required|same:password',
            'company_name' => 'required|max:255',
            // 'role' => [
            //     'nullable',
            //     Rule::in(['super_admin', 'admin', 'user']),
            //     function ($attribute, $value, $fail) {
            //         if ($value === "super_admin" && User::whereHas('roles', function($query) {
            //             $query->where('role_name', 'super_admin');
            //         })->count() > 0) {
            //          $fail("The $attribute can only be one Super Admin");
            //         //  return $this->sendError(['error' => $fail], 400);
            //         }
            //     },
            // ],
        ]);

        // company name is required to users and admin, and not on super admin
        $validator->sometimes('company_name', 'required', function ($input) {
            return $input->role !== 'super_admin';
        });

        // show validate errors
        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        try {

                // create the user
                $input = $request->all();
                $input['password'] = bcrypt($input['password']);
                // $input['company_id'] = $company->id;

                // start a database transaction
                DB::beginTransaction();
                $user = User::create($input);

                // attach role

                // attach company to user
                if(isset($request->company_name)) {
                    $company = Company::where('company_name', $request->company_name)->first();

                    if(!$company) {
                        // roll back in case of an error
                        DB::rollBack();
                        return $this->sendError(['error' => 'Company not found'], 400);
                    }

                    // attach company to user
                    $user->company_id = $company->id;
                    $user->save(); // save user with company
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

        return response()->json([
            'message' => 'Successfully registered',
            'status' => 'success',
            'name' => $success['first_name'],
            'token' => $success['token'] ,
        ]);
    }

    public function logout (Request $request): JsonResponse {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
