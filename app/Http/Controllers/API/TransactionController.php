<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Company;
use App\Models\User;
use App\Models\Item;
use Illuminate\Contracts\Support\ValidatedData;

class TransactionController extends Controller

/*
 ? Objective
 * Transfer an item from one user to another user - done
 * Remove the item once the transaction is approved - working
 * Approve the transaction - working
 * Reject the transaction - working
 * View all transactions - working

*/

/*
 * Objective
 * transaction_details table
    - sender's id
    - receiver's id
    - company_id
    - address_from
    - address_to
  * item_transfers pivot table
    - transaction_id from transaction_details
    - item_id from items
    - status
    - approved_by
    - approved_at
  * Use cases
  - users can send one ore more items to another user
  - a transaction could have one or more items
  - an admin can approved or reject a transaction through status

*/
{

    private function checkUserPermission (array $permissionNames, array $userRole = ['admin', 'user'])
    {
        $user = Auth::user();
        // dd($user);
        $role = $user->roles->whereIn('role_name', $userRole)->first();
        // dd($role);

        if(!$role) {
            return false;
        }

        foreach($permissionNames as $permissionName) {
            if(!$role->permissions->contains('permission_name', $permissionName)) {
                return false;
            }
        }
        return true;
    }

    public function transferItem(Request $request) : JsonResponse
    {
        if(!$this->checkUserPermission(['transfer_item'], ['user', 'admin'])) {
            return $this->sendError(['error' => '
            You don"t have permission to transfer an item'], 400);
        }
        $validator =  Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id',
            'item_ids' => 'required|array',
            'items_ids.*' => 'exists:items,id',
            'address_from' => 'sometimes|max:255',
            'address_to' => 'sometimes|max:255',
            'message' => 'nullable|string|max:255',
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();

        try {
            $validatedData = $validator->validated();

            $transaction = new TransactionDetail;
            $transaction->sender_id = auth()->user()->id;
            $transaction->company_id = auth()->user()->company_id;
            $transaction->receiver_id = $validatedData['receiver_id'];
            $transaction->message = $validatedData['message'];

            // If address is not provided (sender and receiver) in the request, use the company's address
            $address_from = $validatedData['address_from'] ?? auth()->user()->company->address;
            $transaction->address_from = $address_from;

            $address_to = $validatedData['address_to'] ?? User::find($validatedData['receiver_id'])->company->address;
            if(!$address_to) {
                return $this->sendError(['error' => 'No company address, Please add a receiver address'], 400);
            }
            $transaction->address_to = $address_to;

            $transaction->save();
            // dd($transaction);

            // Loop through the array of item Ids and attach each on to the transaction
            foreach ($validatedData['item_ids'] as $itemId) {
                $transaction->items()->attach($itemId);
            }

            DB::commit();

        return $this->sendResponse($transaction->toArray(), 'Item transfer successfully. ');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error during transaction: ' . $e->getMessage());
            return $this->sendError(['error' => 'Transaction failed.'], 400);
        }
    }

    public function viewTransactionsPerAdmin()
    {
        if(!$this->checkUserPermission(['view_transfer_item'], ['admin', 'user']))
        {
            return $this->sendError(['error' => 'You do not have permission to view transactions'], 400);
        }

        $companyId = auth()->user()->company_id;
        $company  = Company::find($companyId);
        $transactions = $company->transactionDetails()->latest()->get();

        if(!$transactions) {
            return $this->sendError(['error' => 'No transactions found on this user'], 400);
        }

        $transactions = $transactions->map(function ($transaction) {
            $items = $transaction->items->map(function ($item) {
                $approver = User::find($item->pivot->approved_by);
                $approverName = $approver ? $approver->first_name : null;
                return [
                    'name' => $item->name,
                    'description' => $item->description,
                    'status' => $item->pivot->status,
                    'approved_by' => $approverName,
                    'approved_at' => $item->pivot->approved_at,
                ];
            })->toArray();

            return [
                'id' => $transaction->id,
                'sender_full_name' => $transaction->sender->first_name . ' ' . $transaction->sender->last_name,
                'receiver_full_name' => $transaction->sender->first_name . ' ' . $transaction->sender->last_name,
                'address_from' => $transaction->address_from,
                'address_to' => $transaction->address_to,
                'items' => $items, // array of item names
            ];
        });

        return $this->sendResponse($transactions->toArray(), 'Transactions retrieved successfully');
    }

    public function viewTransactionsPerUser()
    {
        if(!$this->checkUserPermission(['view_transfer_item'], ['user'])) {
            return $this->sendError(['error' => 'You do not have permission to view transactions'], 400);
        }

        $userId = auth()->user()->id;

        // search
        $query = TransactionDetail::where('sender_id', $userId);
        $search = request()->query('search');

        if($search) {
            $query->where(function($query) use ($search) {
                $query->where('address_from', "like", "%{$search}%")->orWhere('address_to', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%")
                ->orWhereHas('items', function($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%");
                });
            });
        }

      $transactions = $query->latest()->paginate(10);

        if(!$transactions) {
            return $this->sendError(['error' => 'No transactions found on this user'], 400);
        }

        $transactions->getCollection()->transform(function ($transaction) use ($search) {
            $items = $transaction->items->filter(function ($item) use ($search) {
                // Only include the item if its name or description matches the search query/term
                return stripos($item->name, $search) !== false || stripos($item->description, $search) !== false;
            })->map(function ($item) use ($transaction) {
                $approver = User::find($item->pivot->approved_by);
                $approverName = $approver ? $approver->first_name : null;
                return [
                    'transaction_id' => $transaction->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'status' => $item->pivot->status,
                    'approved_by' => $approverName,
                    'approved_at' => $item->pivot->approved_at,
                ];
            })->toArray();

            return [
                'id' => $transaction->id,
                'sender_full_name' => $transaction->sender->first_name . ' ' . $transaction->sender->last_name,
                'receiver_full_name' => $transaction->receiver->first_name . ' ' . $transaction->receiver->last_name,
                'address_from' => $transaction->address_from,
                'address_to' => $transaction->address_to,
                'items' => $items, // array of item names
            ];
        });

        return response()->json($transactions, 200);
    }

    public function transactionStatus(Request $request, $transactionId)
    {
        if(!$this->checkUserPermission(['approve_item'], ['admin'])) {
            return $this->sendError(['error' => 'You do not have permission to approve transactions'], 400);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected,canceled,pending,delivered',
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        $validatedData = $validator->validated();

        $transaction = TransactionDetail::find($transactionId);
        if(!$transaction) {
            return $this->sendError(['error' => 'Transaction not found'], 400);
        }

        // check if the transaction belongs to the same company as the admin
        if($transaction->company_id !== auth()->user()->company_id) {
            return $this->sendError(['error' => 'You do not have permission to approve transactions'], 400);
        }

        // update the status of each item in the transaction
        foreach ($transaction->items as $item) {
            $item->pivot->status = $validatedData['status'];
            $item->pivot->approved_by = auth()->user()->id;
            $item->pivot->approved_at = now();
            $item->pivot->save();
        }

        return $this->sendResponse($transaction->load('items')->toArray(), 'Transaction status updated successfully');
    }
}
