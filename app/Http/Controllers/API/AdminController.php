<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;

class AdminController extends Controller
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

    public function indexByCompany()
    {
        if(!$this->checkUserAndPermission(['view_users'], 'admin')) {
            return $this->sendError(['error' => 'Unauthorized'], 401);
        }

        $search = request()->query('search');
        $companyId = auth()->user()->company_id;
        $currentUserId = auth()->user()->id;
        // fetch all users in the company except the current user
        $query = User::where('company_id', $companyId)->where('id', '!=', $currentUserId);

        if($search) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'LIKE', "%{$search}%")
                ->orWhere('last_name', 'LIKE', "%{$search}%")
                ->orWhere('email', 'LIKE', "%{$search}%")
                ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->with('roles')->latest()->paginate(10);
        foreach ($users as $user) {
            $user->append('role_name');
        }

        return response()->json($users, 200);
    }
}
