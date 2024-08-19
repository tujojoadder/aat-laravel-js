<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\FriendRequest;
use App\Models\UniqeUser;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FriendRequestController extends Controller
{


    public function send_friendrequest(Request $request, $receiver_id)
    {
        $user=auth()->user();
        $userId=$user->user_id;
        $request->merge(['receiver_id' => $receiver_id]);

        // Validate input parameters
        $this->validate($request, [
            'receiver_id' => 'required|string|max:50'
        ]);
   
        $userId = cleanInput($userId);
        $receiver_id = cleanInput($receiver_id);

        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($receiver_id, &$message,$user,$userId) {
           

            // Retrieve the friend request where the sender is the receiver ID and the receiver is the authenticated user
            $existingRequest = FriendRequest::where('sender_id', $receiver_id)
                ->where('receiver_id', $userId)
                ->first();

            // Check if an existing request was found
            if ($existingRequest) {
                if ($existingRequest->status === 'pending') {
                    // If there's a pending request, suggest accepting it instead
                    $message = 'There is already a pending friend request from this user';
                    return;
                } elseif ($existingRequest->status === 'accepted') {
                    // If there's an accepted request, inform the user
                    $message = 'Already your friend.';
                    return;
                } else {
                    // If there's a rejected request, delete it
                    $existingRequest->delete();
                }
            }

            // Check if sender_id and receiver_id are the same
            if ($user->user_id == $receiver_id) {
                $message = 'You cannot send a friend request to yourself.';
                return;
            }

            // Check if a friend request already exists
            $existingRequest = FriendRequest::where('sender_id', $user->user_id)
                ->where('receiver_id', $receiver_id)
                ->first();

            if ($existingRequest) {
                // Friend request already exists
                if ($existingRequest->status == 'rejected') {
                    $existingRequest->update(['status' => 'pending']);
                    $message = 'Friend request sent successfully.';
                } elseif ($existingRequest->status == 'accepted') {
                    $message = 'Already your friend.';
                } else {
                    $message = 'Friend request already sent to this user.';
                }
            } else { // Create a new friend request
                FriendRequest::create([
                    'friend_request_id' => Str::uuid(),
                    'sender_id' => $user->user_id,
                    'receiver_id' => $receiver_id
                ]);

                $message = 'Friend request sent successfully.';
            }
        });

        return response()->json(['message' => $message]);
    }



    //View Authenticated user Friendlist(Pendding)
    public function friend_requestlist()
    {
        $user = auth()->user();

        // Retrieve sender IDs along with their corresponding user names and profile pictures
        $friend_requests = $user->friendRequest()
            ->where('status', 'pending')
            ->join('users', 'friend_requests.sender_id', '=', 'users.user_id')
            ->select('friend_requests.sender_id', 'users.user_fname', 'users.user_lname', 'users.profile_picture')
            ->get();

        // Check if there are any friend requests
        if ($friend_requests->isEmpty()) {
            // Return a custom message indicating no friend requests
            return response()->json(['message' => 'You have no friend requests'], 200);
        }

        // Extract sender IDs from the collection
        $requests = $friend_requests->pluck('sender_id');

        return response()->json([
            'friend_requests' => $requests
        ]);
    }


    //ManageFriendRequests(Accepted,Rejected)
    public function manageFriendRequest($requested_id, Request $request)
    {
        // Validate the input parameters
        $request->validate([
            'decision' => 'required|in:accepted,rejected',
        ]);
        $decision = cleanInput($request->decision);

        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($requested_id, &$message, $decision) {
            // Get the authenticated user
            $user = auth()->user();

            // Find the friend request based on the provided ID, authentication, and status
            $friendRequest = FriendRequest::where('receiver_id', $user->user_id)
                ->where('sender_id', $requested_id)
                ->where('status', 'pending')
                ->first();

            if ($friendRequest) {
                // Check the user's decision: 'accepted' or 'rejected'
                if ($decision == 'accepted') {
                    // Update friend request status to 'accepted'
                    $friendRequest->update(['status' => 'accepted']);

                    // Add the friend to the authenticated user's friend list
                    $friendList = FriendList::where('user_id', $user->user_id)->first();
                    if ($friendList) {
                        $currentFriends = $friendList->user_friends_ids;
                        $alluser = empty($currentFriends) ? $requested_id : $currentFriends . ',' . $requested_id;
                        $friendList->update(['user_friends_ids' => $alluser]);
                    } else {
                        FriendList::create([
                            'friend_lists_id' => Str::uuid(),
                            'user_friends_ids' => $requested_id,
                            'user_id' => $user->user_id,
                        ]);
                    }

                    // Add the authenticated user to the requested user's friend list
                    $requestedUserFriendList = FriendList::where('user_id', $requested_id)->first();
                    if ($requestedUserFriendList) {
                        $currentFriends = $requestedUserFriendList->user_friends_ids;
                        $alluser = empty($currentFriends) ? $user->user_id : $currentFriends . ',' . $user->user_id;
                        $requestedUserFriendList->update(['user_friends_ids' => $alluser]);
                    } else {
                        FriendList::create([
                            'friend_lists_id' => Str::uuid(),
                            'user_friends_ids' => $user->user_id,
                            'user_id' => $requested_id,
                        ]);
                    }

                    $message = 'Friend added successfully.';
                } elseif ($decision == 'rejected') {
                    // Update friend request status to 'rejected'
                    $friendRequest->update(['status' => 'rejected']);
                    $message = 'Rejected successfully';
                } else {
                    // Handle the case where the user submits an invalid decision
                    $message = 'Invalid decision. Please specify either "accepted" or "rejected".';
                }
            } else {
                // Handle the case where the friend request is not found or already processed
                $message = 'Friend request not found or already processed';
            }
        });

        return response()->json(['message' => $message]);
    }




    //

    public function unfriendUser($useridtoremove)
    {
        // Create a request instance manually
        $request = new \Illuminate\Http\Request();
        $request->merge(['useridtoremove' => $useridtoremove]);

        // Validate input parameters
        $this->validate($request, [
            'useridtoremove' => "required|string|max:50"
        ]);

        // Clean the input if necessary
        $useridtoremove = cleanInput($useridtoremove); // Uncomment if needed
       
        // Initialize the message variable
        $message = '';

        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($useridtoremove, &$message) {
            // Get the authenticated user
            $user = auth()->user();

            // Find the authenticated user's friend list
            $authUserFriendList = FriendList::where('user_id', $user->user_id)->first();

            // Find the requested user's friend list
            $requestedUserFriendList = FriendList::where('user_id', $useridtoremove)->first();

            if ($authUserFriendList && $requestedUserFriendList) {
                // Remove the requested user's ID from the authenticated user's friend list
                $updatedAuthUserFriendList = str_replace([$useridtoremove . ',', $useridtoremove], '', $authUserFriendList->user_friends_ids);
                $updatedAuthUserFriendList = rtrim($updatedAuthUserFriendList, ',');
                $authUserFriendList->update(['user_friends_ids' => $updatedAuthUserFriendList]);

                // Remove the authenticated user's ID from the requested user's friend list
                $updatedRequestedUserFriendList = str_replace([$user->user_id . ',', $user->user_id], '', $requestedUserFriendList->user_friends_ids);
                $updatedRequestedUserFriendList = rtrim($updatedRequestedUserFriendList, ',');
                $requestedUserFriendList->update(['user_friends_ids' => $updatedRequestedUserFriendList]);

                // Update the friend request status to 'rejected' (assuming a friend request was sent)
                $friendRequest = FriendRequest::where(function ($query) use ($user, $useridtoremove) {
                    $query->where('receiver_id', $user->user_id)->where('sender_id', $useridtoremove);
                })->orWhere(function ($query) use ($user, $useridtoremove) {
                    $query->where('receiver_id', $useridtoremove)->where('sender_id', $user->user_id);
                })->first();

                if ($friendRequest) {
                    $friendRequest->update(['status' => 'rejected']);
                }

                $message = 'Unfriend Successfully';
            } else {
                $message = 'Friend lists not found';
            }
        });

        return response()->json(['message' => $message]);
    }



    //Auth user friends list 
    public function getFriendIds()
    {
        $user = auth()->user();

        // Retrieve the friend list for the user
        $friendList = FriendList::where('user_id', $user->user_id)->first();

        if ($friendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $friendList->user_friends_ids;

            // Check if user_friends_ids column is not empty
            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);

                // Return the friend IDs as JSON response
                return response()->json(['friend_ids' => $friendIdsArray]);
            } else {
                // Handle the case where user_friends_ids column is empty
                return response()->json(['message' => 'No friends found for the user.'], 404);
            }
        } else {
            // Handle the case where the friend list is not found for the user
            return response()->json(['message' => 'No friends found for the user.'], 404);
        }
    }


    //Retrive specific users friendlist

    public function getSpecificUserFriendDetails(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificUserId = cleanInput($request->query('id'));
    
        // Define the number of friends per page
        $perPage = 3;
    
        // Retrieve the friend list for the specified user ID
        $friendList = FriendList::where('user_id', $specificUserId)->first();

        if ($friendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $friendList->user_friends_ids;
    
            // Check if user_friends_ids column is not empty
            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);
    
                // Retrieve friend details from the users table with pagination
                $friends = User::whereIn('user_id', $friendIdsArray)
                    ->select('user_id', 'user_fname', 'user_lname', 'profile_picture','identifier')
                    ->paginate($perPage);
    
                // Return the friend details as JSON response
                return response()->json($friends);
            } else {
                // Handle the case where user_friends_ids column is empty
                return response()->json(['message' => 'No friends found for the user.'], 404);
            }
        } else {
            // Handle the case where the friend list is not found for the user
            return response()->json(['message' => 'Friend list not found for the user.'], 404);
        }
    }
    





//get friend sugestion 7 record for home

public function getFriendSuggestionHome()
{
    $authUser = auth()->user(); // Authenticated user

    // Fetch 5 users, excluding the authenticated user, and select specific columns
   $otherUsers = User::where('user_id', '!=', $authUser->user_id)
   ->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier')
   ->inRandomOrder() // Randomize the results
   ->limit(5) // Limit to 5 results
   ->get();
    return response()->json(['data' => $otherUsers]);
}

 
//get User info for show others profile 
public function getUserInfo($id)
{
 /*    // Sanitize and validate the ID
    if (empty($id) || !Uuid::isValid($id)) {
        Log::warning("Invalid User ID format: {$id}");
        return response()->json([
            'error' => 'Invalid User ID format'
        ], 400); // Bad Request
    }
 */
    // Retrieve the user by ID, selecting specific fields
    $user = User::where('user_id', $id)
        ->select('cover_photo', 'identifier', 'profile_picture', 'user_fname', 'user_lname')
        ->first();

    // Check if user exists
    if (!$user) {
        Log::warning("User not found for ID: {$id}");
        return response()->json([
            'error' => 'User not found'
        ], 404); // Not Found
    }

    // Return the user data as JSON
    return response()->json([
        'data' => $user
    ], 200); // OK
}











}
