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

    // helper function to check user permission and role
    private function checkUserAndPermission(array $permissionNames, $userRole = 'super_admin' )
    {
        $user = Auth::user();
        $role = $user->roles->firstWhere('role_name', $userRole); // get role

        // check if the user has the permission and role
        if (!$role) {
            return false;
        }

        // loop through the permission names to check if the user has the permission
        foreach($permissionNames as $permissionName) {
            if(!$role->permissions->contains('permission_name', $permissionName)) {
                return false;
            }
        }

    // return true if the user has the permission and role
        return true;
    }

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
                    // check user permission to add an admin
                    if($value === "admin" && !$this->checkUserAndPermission("add_admin")) {
                        $fail("Only a super admin can create an admin");
                    }
                    // check user permission to add a user
                    if($value === "user" && !$this->checkUserAndPermission("add_users", "admin")) {
                        $fail("Only an admin can create a user");
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

    // assign role to user
    public function assignRole(Request $request, $userId) : JsonResponse
    {
        // check if the requested user is a super admin before assigning a role
        if(!$this->checkUserAndPermission(['add_admin', 'add_users'], 'super_admin')) {
            return $this->sendError(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'role' => [
                'required',
                Rule::in(['admin', 'user']), //  check if the role is valid
            ]
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        $user = User::find($userId); // get user
        if(!$user) {
            return $this->sendError(['error' => 'User not found'], 400);
        }

        // get the role before attaching
        $role = Role::where('role_name', $request->role)->first(); // get role
        if(!$role) {
            return $this->sendError(['error' => 'Role not found'], 400);
        }

        // attach the role to the user
        $user->roles()->attach($role->id);

        return $this->sendResponse($user->toArray(), 'Role assigned successfully');

    }

    // user activation
    public function toggleActivation($id): JsonResponse
    {

        $user = User::find($id);
        if(!$user) {
            return $this->sendError(['error' => 'User not found'], 400);
        }

       $currentUser = Auth::user(); // get the current user

        // if the current user is a super admin
        if($currentUser->roles->firstWhere('role_name', 'super_admin')) {
            // super admin can activate/deactivate any user
            $user->is_activated = !$user->is_activated;
            $user->save();
            return $this->sendResponse($user->toArray(), 'User activation status updated successfully');
        } else if($currentUser->roles->firstWhere('role_name', 'admin')) {
            // admin can only activate/deactivate users, not super admin
            if(!$user->roles->firstWhere('role_name', 'super_admin')) {
                $user->is_activated = !$user->is_activated;
                $user->save();
                return $this->sendResponse($user->toArray(), 'User activation status updated successfully');
            } else {
                return $this->sendError(['error' => 'Unauthorized: Admin cannot deactivate a super admin']);
            }
        } else {
            return $this->sendError(['error' => 'Unauthorized'], 401);
        }
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

