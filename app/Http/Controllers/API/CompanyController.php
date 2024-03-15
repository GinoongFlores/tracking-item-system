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

       // helper function check user permission
    private function checkUserPermission($permissionName)
    {
        $user = Auth::user();

        // Get the 'super_admin' role
        $superAdminRole = $user->roles->firstWhere('role_name', 'super_admin');

        // check if the 'super_admin' role exist and has the specified permission
        return $superAdminRole && $superAdminRole->permissions->contains('permission_name', $permissionName);
    }


    public function index() : JsonResponse
    {
        // check user permission
        if(!$this->checkUserPermission('view_company')) {
            return $this->sendError(['error' => 'You do not have permission to view a company'], 400, false);
        }

        $company = Company::all();
        return $this->sendResponse($company->toArray(), 'Company retrieved successfully');
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
        // check user permission
        if(!$this->checkUserPermission('add_company')) {
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
        // check user permission
        if(!$this->checkUserPermission('view_company')) {
            return $this->sendError(['error' => 'You do not have permission to view a company'], 400, false);
        }

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
        // check user permission
        if(!$this->checkUserPermission('edit_company')) {
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
        // check user permission
        if(!$this->checkUserPermission('delete_company')) {
            return $this->sendError(['error' => 'You do not have permission to delete a company'], 400);
        }

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
