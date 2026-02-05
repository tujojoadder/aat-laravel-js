<?php

namespace App\Http\Controllers;

use App\Models\GroupJoinRequest;
use App\Models\Groups;
use App\Models\UniqeUser;
use App\Models\UsersHasGroups;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupJoinRequestController extends Controller
{
    public function send_groupjoin_request(Request $request, $groupId)
    {

        $request->merge(['groupId' => $groupId]);

        // Validate input parameters
        $this->validate($request, [
            'groupId' => 'required|string'
        ]);
        $groupId = cleanInput($groupId);
        $group=Groups::find($groupId);
        if (!$group) {
            return response()->json(['message' => 'Group not founded']);
        }
        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($groupId, &$message) {
            $user = auth()->user();
            $userId = $user->user_id;

            // Check if the user is already a member of the group
            $existingMembership = UsersHasGroups::where('user_id', $userId)
                ->where('group_id', $groupId)
                ->first();

            if ($existingMembership) {
                $message = 'You are already a member of the group';
                return;
            }

            // Retrieve any existing join requests
            $existingRequest = GroupJoinRequest::where('sender_id', $userId)
                ->where('group_id', $groupId)
                ->first();

            // Check if an existing request was found
            if ($existingRequest) {
                if ($existingRequest->status === 'pending') {
                    // If there's a pending request, suggest accepting it instead
                    $message = 'You have already requested to join this group';
                    return;
                } else {
                    // If there's a rejected request, delete it
                    $existingRequest->delete();
                }
            }

            // Create a new join request
            GroupJoinRequest::create([
                'group_request_id' => Str::uuid(),
                'sender_id' => $userId,
                'group_id' => $groupId
            ]);

            $message = 'Your join request has been sent';
        });

        return response()->json(['message' => $message]);
    }


    //ManageFriendRequests(Accepted,Rejected)
    public function manageGroupjoinRequest($requestedId, Request $request)
    {

        $request->merge(['requestedId' => $requestedId]);
        // Validate the input parameters
        $request->validate([
            'decision' => 'required|in:accepted,rejected',
            'requestedId' => "required|string|max:50"
        ]);
        $decision = cleanInput($request->decision);
        $requestedId = cleanInput($requestedId);
        
        $user = auth()->user();
        $userId = $user->user_id;

        // Find the friend request based on the provided ID
        $joinRequest = GroupJoinRequest::where('group_request_id', $requestedId)->first();

        if (!$joinRequest) {
            return response()->json(['message' => 'Group join request not found']);
        }

        // Find the group associated with the join request
        $group = Groups::where('group_id', $joinRequest->group_id)->first();

        if (!$group) {
            return response()->json(['message' => 'Group not found']);
        }
        $admin = $group->group_admins;

        if (!Str::contains($admin, $userId)) {
            return response()->json(['message' => 'You are not an admin of this group']);
        }


        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($requestedId, &$message, $decision, $user, $userId, $joinRequest) {
            // Get the authenticated user


            if ($joinRequest) {
                // Check the user's decision: 'accepted' or 'rejected'
                if ($decision == 'accepted') {
                    UsersHasGroups::create([
                        'user_id' => $joinRequest->sender_id,
                        'group_id' => $joinRequest->group_id,
                    ]);
                    //delete the request
                    $joinRequest->delete();
                    $message = 'New Member added successfully to group';
                } else {
                    //delete the request
                    $joinRequest->delete();
                    // Handle the case where the user submits an invalid decision
                    $message = 'Request Deleted Successfully';
                }
            } else {
                // Handle the case where the friend request is not found or already processed
                $message = 'Friend request not found or already processed';
            }
        });

        return response()->json(['message' => $message]);
    }
}
