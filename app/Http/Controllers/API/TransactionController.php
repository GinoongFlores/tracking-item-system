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
            $transaction->status = "Pending";

            $transaction->save();

            // Loop through the array of item Ids and attach each on to the transaction
            foreach ($validatedData['item_ids'] as $itemId) {
                $transaction->items()->attach($itemId);
            }

            // check if the item is already in a transaction

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
        $transactions = $company->transactionDetails;

        if(!$transactions) {
            return $this->sendError(['error' => 'No transactions found on this user'], 400);
        }

        $transactions = $transactions->map(function ($transaction) {
            $itemNames = $transaction->items->map(function ($item) {
                return $item->name;
            })->toArray();

            $itemDescriptions = $transaction->items->map(function ($item) {
                return $item->description;
            })->toArray();

            return [
                'id' => $transaction->id,
                // 'sender_name' => $transaction->sender->first_name . ' ' . $transaction->sender->last_name,
                'sender_name' => $transaction->sender->first_name,
                'item_name' => $itemNames, // array of item names
                'item_description' => $itemDescriptions,
                'status' => $transaction->status,
                'approved_at' => $transaction->approver,
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
        $transactions = TransactionDetail::where('sender_id', $userId)->get();

        if(!$transactions) {
            return $this->sendError(['error' => 'No transactions found on this user'], 400);
        }

        $transactions = $transactions->map(function ($transaction) {
            $itemNames = $transaction->items->map(function ($item) {
                return $item->name;
            })->toArray();

            $itemDescriptions = $transaction->items->map(function ($item) {
                return $item->description;
            })->toArray();

            return [
                'id' => $transaction->id,
                'sender_name' => $transaction->sender->first_name,
                'item_name' => $itemNames, // array of item names
                'item_description' => $itemDescriptions,
                'status' => $transaction->status,
                'approved_at' => $transaction->approver,
            ];
        });

        return $this->sendResponse($transactions->toArray(), 'Transactions retrieved successfully');
    }

    public function approveTransaction($transactionId)
    {

    }
}
