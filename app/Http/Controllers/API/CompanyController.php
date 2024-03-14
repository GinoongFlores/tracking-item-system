<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\CompanyResource;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() : JsonResponse
    {
        $company = Company::all();
        return $this->sendResponse($company->toArray(), 'Company retrieved successfully');
    }

    // helper function check user permission
    private function checkUpdatePermission($permissionName)
    {
        $user = Auth::user();

        // Get the 'super_admin' role
        $superAdminRole = $user->roles->firstWhere('role_name', 'super_admin');

        // check if the 'super_admin' role exist and has the specified permission
        return $superAdminRole && $superAdminRole->permissions->contains('permission_name', $permissionName);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) : JsonResponse
    {
        $user = Auth::user();

        // Get the 'super_admin' role
        $superAdminRole = $user->roles->firstWhere('role_name', 'super_admin');

        // check if the super_admin exist and has the permission to add a company
        $hasAddCompanyPermission = $superAdminRole && $superAdminRole->permissions->contains('permission_name', 'add_company');

        if (!$hasAddCompanyPermission) {
            return $this->sendError(['error' => 'You do not have permission to add a company'], 400);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|max:255',
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        // using begin transaction to have try catch block
        DB::beginTransaction();

        try {
            $existingCompany = Company::where('company_name', $request->company_name)->first();

            if ($existingCompany) {
                return $this->sendError(['error' => 'Company name already exists'], 400);
            }
            $company = Company::create($request->all());
            DB::commit();

            return $this->sendResponse($company->toArray(), 'Company created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id) : JsonResponse
    {
        $company = Company::find($id);

        if(is_null($company)) {
            return $this->sendError(['error' => 'Company does not exist'], 400, false);
        }

        return $this->sendResponse(new CompanyResource($company), 'Company retrieved successfully');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id) : JsonResponse
    {
        // check if the request user has the permission to edit a company
        $user = Auth::user();
        $superAdminRole = $user->roles->firstWhere('role_name', 'super_admin');

        $hasEditCompanyPermission = $superAdminRole && $superAdminRole->permissions->contains('permission_name', 'edit_company');

        if (!$hasEditCompanyPermission) {
            return $this->sendError(['error' => 'You do not have permission to edit a company'], 400);
        }

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|max:255',
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        $company = Company::find($id);

        // handle no company found
        if (!$company) {
            return $this->sendError(['error' => 'Company does not exist'], 400);
        }

        DB::beginTransaction();

        try {
            $company->update($request->all());
            DB::commit();

            return $this->sendResponse($company->toArray(), 'Company updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company) : JsonResponse
    {
        DB::beginTransaction();

        try {
            $company->delete();
            DB::commit();

            return $this->sendResponse($company->toArray(), 'Company deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError($e->getMessage(), [], 400);
        }
    }
}
