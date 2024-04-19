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

        // loop through the permission names to check if the user has the permission and role
        foreach($permissionNames as $permissionName) {
            if(!$role->permissions->contains('permission_name', $permissionName)) {
                return false;
            }
        }

    // return true if the user has the permission and role
        return true;
    }

    public function index () {
      // check if the requested user is a super admin
      if(!$this->checkUserAndPermission(['view_users'], 'super_admin')) {
        return $this->sendError(['error' => 'Unauthorized'], 401);
      }

      $search = request()->query('search');
      $query = User::query();

      if($search) {
            $query->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%");
      }

        $users = $query->with('roles')->latest()->paginate(10);
        foreach ($users as $user) {
            $user->append('role_name');
        }
        return response()->json($users, 200);
    }

    public function currentUser () : JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return $this->sendError(['error' => 'No authenticated user'], 400);
        }

        $role = $user->roles->first();
        $company = $user->company;
        $check_role_name = $role ? $role->role_name : null;
        $check_company_name = $company ? $company->company_name : null;


        $userData = [
            'first_name' => $user->first_name,
            'role' => $check_role_name,
            'company' => $check_company_name,
            'email' => $user->email,
            'is_activated' => $user->is_activated,
        ];

        return $this->sendResponse($userData, 'User retrieved successfully');
    }

    public function show(string $id) {
        // check if the requested user is a super admin
      if(!$this->checkUserAndPermission(['view_users'], 'super_admin')) {
        return $this->sendError(['error' => 'Unauthorized'], 401);
      }

        $user = User::find($id);
        if(!$user) {
            return $this->sendError(['error' => 'User not found'], 400);
        }

        return $this->sendResponse($user->toArray(), 'User retrieved successfully');
    }


    // update/edit user details
    public function update(Request $request, $userId) : JsonResponse
    {
        $user = User::find($userId); // get user
        if(!$user) {
            return $this->sendError(['error' => 'User not found'], 400);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'max:255',
            'last_name' => 'max:255',
            // allows the users to update even if the phone number & email is the same
            'phone' => 'numeric|unique:users,phone,' . $user->id,
            'email' => 'email|max:255|unique:users,email,' . $user->id,
            'company_name' => 'max:255',
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        // check company name
        if(isset($request->company_name)) {
            $company = Company::where('company_name', $request->company_name)->first();
            if(!$company) {
                return $this->sendError(['error' => 'Company not found'], 400);
            }
            // assign company to user
            $user->company_id = $company->id;
        }

        DB::beginTransaction();

        try {
            $user->update($request->all());
            DB::commit();

            return $this->sendResponse($user->toArray(), 'User updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(['error' => $e->getMessage()], 400);
        }

        return $this->sendResponse($user->toArray(), 'User updated successfully');

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

        // user role change upon request
        $user->roles()->sync([$role->id]);

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
}

