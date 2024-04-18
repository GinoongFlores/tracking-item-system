<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TransactionDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
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

    public function transferItem(Request $request, $itemId) : JsonResponse
    {
        if(!$this->checkUserPermission(['transfer_item'], ['user', 'admin'])) {
            return $this->sendError(['error' => '
            You don"t have permission to transfer an item'], 400);
        }

        if(!$itemId) {
            return $this->sendError(['error' => 'Item not found'], 404);
        }

        $validator =  Validator::make($request->all(), [
            'receiver_id' => 'required|exists:users,id'
        ]);

        if($validator->fails()) {
            return $this->sendError(['error' => $validator->errors()], 400);
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();

            $transaction = new TransactionDetail;
            // $transaction->item_id = $itemId;
            $transaction->sender_id = auth()->user()->id;
            $transaction->receiver_id = $validatedData['receiver_id'];
            $transaction->status = "Pending";

            $transaction->save();
            // Check if the model is saved successfully
            // if(!$transaction->save()) {
            //     \Log::error('TransactionDetail not saved: ' . json_encode($transaction->errors()));
            //     throw new \Exception('Transaction not saved');
            // }

            // Associate the item with the transaction
            $transaction->items()->attach($itemId);

            DB::commit();

        return $this->sendResponse($transaction->toArray(), 'Item transfer successfully. ');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error during transaction: ' . $e->getMessage());
            return $this->sendError(['error' => 'Transaction failed.'], 400);
        }
    }

    public function approveTransaction($transactionId)
    {

    }
}
