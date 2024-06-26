<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Item;

class ItemController extends Controller
{
    // check user permission
    private function checkUserPermission(array $permissionNames, array $userRole = ['admin, user'])
    {
        $user = Auth::user();
        $role = $user->roles->whereIn('role_name', $userRole)->first();

        if(!$role) {
            return false;
        }

        // return false if the role does not have all the specified permissions
        foreach($permissionNames as $permissionName) {
            if(!$role->permissions->contains('permission_name', $permissionName)) {
                return false;
            }
        }

        // return true if the role has all the specified permissions
        return true;
    }

    public function index()
    {
        $user = Auth::user();
        $role = $user->roles->first()->role_name;

        $query = Item::query();
        if ($role === "admin") {
            // if the user is an admin, only fetch items from their company
            $query->where('company_id', $user->company_id);
        } elseif ($role === "user") {
            // if the user is a user, only fetch items created by them
            $query->where('user_id', $user->id);
        }

        $search = request()->query('search');

        if($search) {
                $query->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            }

        $items = $query->latest()->paginate(10);

      return response()->json($items, 200);
    }


    public function searchItem(Request $request)
    {
        $user = Auth::user();

        $items = Item::where('user_id', $user->id)
        ->where(function ($query) use ($request) {
            $query->where('name', 'LIKE', "%{$request->get('query')}%")
            ->orWhere('description', 'LIKE', "%{$request->get('query')}%");
        })->select('id', 'name', 'description', 'quantity', 'image')->get();

        return $this->sendResponse($items->toArray(), '');
    }

    public function show($id)
    {
        $item = Item::find($id);

        if(!$item) {
            return $this->sendError(['error' => 'Item not found'], 404);
        }

        return $this->sendResponse($item->toArray(), 'Item retrieved successfully');
    }

    public function store(Request $request) : JsonResponse
    {
        // check user permission
        if(!$this->checkUserPermission(['add_item'], ['admin', 'user'])) {
            return $this->sendError(['error' => 'You do not have permission to add an item'], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255|string|unique:items,name',
            'description' => 'required|max:255|string',
            'quantity' => 'required|integer',
            'image' => 'nullable|string',
        ],
        [
            'name.unique' => 'The item has been already added',
        ]);

        if ($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $input = $request->all();
            $input['user_id'] = auth()->id(); // add the user_id to the input array
            $input['company_id'] = auth()->user()->company_id;

            // if an image was uploaded, save the uuid
            if($request->has('image')) {
                $input['image'] = $request->input('image');
            }

            $item = Item::create($input);
            DB::commit();

            return $this->sendResponse($item->toArray(), 'Item added successfully', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(['error' => $e->getMessage()], 400);
        }

    }

    public function destroy ($itemId) : JsonResponse
    {
        DB::beginTransaction();

        $itemId = Item::find($itemId);

        if(!$itemId) {
            return $this->sendError(['error' => 'Item does not exist'], 400);
        }

        try {
            $itemId->delete();
            DB::commit(); // commit the transaction

            return $this->sendResponse([], 'Item deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(['error' => $e->getMessage()], 400);
        }
    }

    public function restore ($itemId) : JsonResponse
    {
        $item = Item::withTrashed()->find($itemId);

        if(!$item) {
            return $this->sendError(['error' => 'Item does not exist'], 400);
        }

        $item->restore();
        return $this->sendResponse([], 'Item restored successfully');
    }

    public function currentUserTrashedItems() : JsonResponse
    {
        $user = Auth::user();
        if($user) {
            $itemsPerUser = Item::onlyTrashed()->where('user_id', $user->id)->latest()->get();
            if($itemsPerUser->isEmpty()) {
                return $this->sendError(['error' => 'No item found for this user'], 404);
            }
            return $this->sendResponse($itemsPerUser->toArray(), 'Items retrieved successfully');
        } else {
            return $this->sendError(['error' => 'User not found'], 404);
        }
    }

    public function restoreCurrentUserTrashedItems($itemId) : JsonResponse
    {
        $user = Auth::user();
        if($user) {
            $item = Item::onlyTrashed()->where('user_id', $user->id)->find($itemId);
            if(!$item) {
                return $this->sendError(['error' => 'Item does not exist or does not belong to this user'], 404);
            }
            $item->restore();
            return $this->sendResponse($item->toArray(), 'Item restored successfully');
        } else {
            return $this->sendError(['error' => 'User not found'], 404);
        }
    }


    // update an item
    public function update(Request $request, $itemId)
    {

        $user = Auth::user();

        // Define the permissions required to update an item
        $requiredPermissions = ['edit_item'];
        $allowedRoles = ['super_admin', 'admin', 'user'];

        // check if the user has the required permissions
        if(!$this->checkUserPermission($requiredPermissions, $allowedRoles)) {
            return $this->sendError(['error' => 'You do not have permission to edit an item'], 400);
        }

        $itemQuery = Item::query();

        // if the user is not a super_admin, restrict the update to their own items
        if(!$user->hasRole('super_admin')) {
            $itemQuery->where('user_id', $user->id);
        }

        $item = $itemQuery->find($itemId);
        if(!$item) {
            return $this->sendError(['error' => 'Item does not exist or does not belong to this user'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'max:255|string|unique:items,name,'.$itemId,
            'description' => 'max:255|string',
            'quantity' => 'integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ],
        [
            'name.unique' => 'The item has been already added',
        ]);

        if ($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            $item->update($request->all());
            DB::commit();

            return $this->sendResponse($item->toArray(), 'Item updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(['error' => $e->getMessage()], 400);
        }
    }


    public function updateCurrentUserItem(Request $request, $itemId) : JsonResponse
    {
        $user = Auth::user();
        if ($user) {
            $item = Item::where('user_id', $user->id)->find($itemId);
            if (!$item) {
                return $this->sendError(['error' => 'Item does not exist or does not belong to this user'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'max:255|string|unique:items,name,'.$itemId,
                'description' => 'max:255|string',
                'quantity' => 'integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ],
            [
                'name.unique' => 'The item has been already added',
            ]);

            if ($validator->fails()) {
                return $this->sendError(['error' => $validator->errors()], 400);
            }
        } else {
            return $this->sendError(['error' => 'User not found'], 404);
        }

        DB::beginTransaction();

        try {
            $item->update($request->all());
            DB::commit();

            return $this->sendResponse($item->toArray(), 'Item updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(['error' => $e->getMessage()], 400);
        }
    }

}
