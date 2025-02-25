<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\FriendRequest;
use App\Models\UniqeUser;
use App\Models\User;
use App\Models\UserFollow;
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

    /*   Send Friend Request */
    public function send_friendrequest(Request $request)
    {
   
        $user = auth()->user();
        $userId = $user->user_id;
        $message = '';
        // Validate input parameters
        $request->validate([
            'receiver_id' => 'required|string|max:50'
        ]);

        $userId = cleanInput($userId);
        $receiver_id = cleanInput($request->receiver_id);

        // Fetch the friend's list
        $friendList = FriendList::where('user_id', $userId)->first();
        $friendIdsArray = [];

        if ($friendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $friendList->user_friends_ids;

            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);
            }
        }

        // Check if the receiver_id is already in the friend list
        if (in_array($receiver_id, $friendIdsArray)) {
            $message = 'Already your friend.';
            return response()->json(['message' => $message]);
        }

        // Use the DB::transaction method for simplified transactions
        DB::transaction(function () use ($receiver_id, &$message, $user, $userId) {
            //   <<<----Retrieve the friend request where the sender is the receiver ID and the receiver is the authenticated user

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

            /*     <<<<----  Check if a friend request already exists  ----> */
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



    /* Cancel Friend Request */

    public function cancel_friend_request(Request $request)
    {
        // Get Authenticated user
        $user = auth()->user();

        // Validate the incoming request data
        $request->validate([
            'receiver_id' => 'required|max:50',
        ]);

        // Initialize the message variable
        $message = '';
        $receiver_id = cleanInput($request->receiver_id);
        // Use a transaction to ensure data integrity
        DB::transaction(function () use ($receiver_id, $user, $message) {
            // Check if a friend request exists with the current user as the sender and the provided receiver_id
            $friendRequest = FriendRequest::where('sender_id', $user->user_id)
                ->where('receiver_id', $receiver_id)
                ->first();

            if ($friendRequest) {
                // If a matching friend request exists, delete it
                $friendRequest->delete();
                $message = 'Friend request canceled successfully.';
            } else {
                // Handle the case where no matching friend request was found
                $message = 'There is no friend request.';
            }
        });

        // Optionally, return a response or message to indicate the operation was successful
        return response()->json(['data' => $message]);
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
    //we will not delete record from friend request table we just update(except auth user rejected)
    public function manageFriendRequest(Request $request)
    {


        // Validate the input parameters
        $request->validate([
            'decision' => 'required|in:accepted,rejected',
        ]);
        $decision = cleanInput($request->decision);
        /*  Requested UserID */
        $requested_id = cleanInput($request->sender_id);
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

        return response()->json(['data' => $message]);
    }




    public function unfriendUser(Request $request) // Get the user ID from the route parameter
    {
         // Validate the incoming request
    $this->validate($request, [
        'useridtoremove' => 'required|string|max:50',
    ]);

    $useridtoremove = $request->useridtoremove; // Get user ID from the request body
        // Get the authenticated user
        $user = auth()->user();


        // Find the authenticated user's friend list
        $authUserFriendList = FriendList::where('user_id', $user->user_id)->first();
        // Find the friend's friend list to remove the authenticated user
        $requestedUserFriendList = FriendList::where('user_id', $useridtoremove)->first();

        if ($authUserFriendList && $requestedUserFriendList) {
            // Update the authenticated user's friend list by removing the friend's ID
            $authUserFriends = explode(',', $authUserFriendList->user_friends_ids);
            $authUserFriends = array_filter($authUserFriends, function ($id) use ($useridtoremove) {
                return $id !== $useridtoremove; // Remove the friend ID
            });
            $authUserFriendList->update(['user_friends_ids' => implode(',', $authUserFriends)]);

            // Update the friend's friend list by removing the authenticated user's ID
            $requestedUserFriends = explode(',', $requestedUserFriendList->user_friends_ids);
            $requestedUserFriends = array_filter($requestedUserFriends, function ($id) use ($user) {
                return $id !== $user->user_id; // Remove the authenticated user ID
            });
            $requestedUserFriendList->update(['user_friends_ids' => implode(',', $requestedUserFriends)]);

            // Update any pending friend requests between the users to 'rejected'
            $friendRequest = FriendRequest::where(function ($query) use ($user, $useridtoremove) {
                $query->where('receiver_id', $user->user_id)->where('sender_id', $useridtoremove);
            })->orWhere(function ($query) use ($user, $useridtoremove) {
                $query->where('receiver_id', $useridtoremove)->where('sender_id', $user->user_id);
            })->first();

            if ($friendRequest) {
                $friendRequest->update(['status' => 'rejected']);
            }

            // Return success response
            return response()->json(['message' => 'Unfriend Successfully'], 200);
        } else {
            // Return error if the friend list is not found
            return response()->json(['error' => 'Friend list not found or user does not exist'], 404);
        }
    }




    public function getSpecificUserFriendDetails(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificUserId = cleanInput($request->query('id'));
    
        // Define the number of friends per page
        $perPage = 15;
    
        // Get the authenticated user's ID
        $authUserId = auth()->id();
    
        // Retrieve the friend list for the authenticated user
        $authFriendList = FriendList::where('user_id', $authUserId)->first();
    
        // Retrieve the friend list for the specified user ID
        $friendList = FriendList::where('user_id', $specificUserId)->first();
    
        if ($friendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $friendList->user_friends_ids;
    
            // Check if user_friends_ids column is not empty
            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);
    
                // Retrieve the authenticated user's friend IDs if available
                $authFriendIdsArray = [];
                if ($authFriendList && !empty($authFriendList->user_friends_ids)) {
                    $authFriendIdsArray = explode(',', $authFriendList->user_friends_ids);
                }
    
                // Retrieve friend details from the users table with pagination
                $friends = User::whereIn('user_id', $friendIdsArray)
                    ->select('user_id', 'user_fname', 'user_lname', 'profile_picture', 'identifier')
                    ->paginate($perPage);
    
                // Iterate over the paginated friends to add the is_friend field
                $friends->getCollection()->transform(function ($user) use ($authFriendIdsArray, $authUserId) {
                    // Check if the authenticated user has sent a friend request to this user
                    $friendRequestExists = FriendRequest::where('sender_id', $authUserId)
                        ->where('receiver_id', $user->user_id)
                        ->exists();
    
                    return [
                        'user_id' => $user->user_id,
                        'user_fname' => $user->user_fname,
                        'user_lname' => $user->user_lname,
                        'profile_picture' => $user->profile_picture,
                        'identifier' => $user->identifier,
                        'is_friend' => $user->user_id == $authUserId || in_array($user->user_id, $authFriendIdsArray) ? true : false, // Set is_friend to true if the friend is in the auth user's friend list, false otherwise
                        'friend_request_sent' => $friendRequestExists ? true : false, // Set friend_request_sent to true if a friend request has been sent, false otherwise
                    ];
                });
    
                // Return the friend details as JSON response, including the is_friend field
                return response()->json(['data' => $friends], 200);
            } else {
                // If no friends are found, return an empty data set with a success message
                return response()->json(['data' => [], 'message' => 'No friends found for the user.'], 200);
            }
        } else {
            // Handle the case where the friend list is not found for the user
            return response()->json(['data' => [], 'message' => 'Friend list not found for the user.'], 200);
        }
    }
    



    /* Get friend for Auth user */
 /* Get friend for Auth user */
public function getAuthUserFriendDetails(Request $request)
{
    // Define the number of friends per page
    $perPage = 15;

    // Get the page number from the request (default to 1 if not provided)
    $page = $request->get('page', 1);

    // Get the authenticated user
    $user = auth()->user();

    // Get the authenticated user's ID
    $authUserId = $user->user_id;

    // Retrieve the friend list for the authenticated user
    $authFriendList = FriendList::where('user_id', $authUserId)->first();

    if ($authFriendList) {
        // Access the user_friends_ids column
        $userFriendsIds = $authFriendList->user_friends_ids;

        // Check if user_friends_ids column is not empty
        if (!empty($userFriendsIds)) {
            // Split the comma-separated string into an array of friend IDs
            $friendIdsArray = explode(',', $userFriendsIds);

            // Retrieve friend details from the users table with pagination
            $friends = User::whereIn('user_id', $friendIdsArray)
                ->select('user_id', 'user_fname', 'user_lname', 'profile_picture', 'identifier')
                ->paginate($perPage, ['*'], 'page', $page);

            // If no friends are found, return an empty 'data' field
            if ($friends->isEmpty()) {
                return response()->json(['data' => []], 200);
            }

            // Return the friend details in a 'data' field as expected by the frontend
            return response()->json([
                'data' => $friends->items(), // Only returning the items (friends)
                'current_page' => $friends->currentPage(),
                'last_page' => $friends->lastPage(),
                'total' => $friends->total(),
            ]);
        } else {
            // If no friend IDs found, return empty 'data'
            return response()->json(['data' => [],'messsage'=>'you have no friend'], 200);
        }
    } else {
        // If no friend list found, return empty 'data'
        return response()->json(['data' => [],'messsage'=>'you have no friend'], 200);
    }
}




    //get friend sugestion 7 record for home

    public function getFriendSuggestionHome()
    {
        $authUser = auth()->user(); // Authenticated user

        // Get the authenticated user's ID
        $authUserId = $authUser->user_id;

        // Retrieve the friend list for the authenticated user
        $authFriendList = FriendList::where('user_id', $authUserId)->first();

        // Initialize friend IDs array
        $friendIdsArray = [];

        if ($authFriendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $authFriendList->user_friends_ids;

            // Check if user_friends_ids column is not empty
            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);
            }
        }

        // Retrieve IDs of users who have a pending friend request with the authenticated user
        $pendingRequestSenderIds = FriendRequest::where('sender_id', $authUserId)
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('receiver_id');

        $pendingRequestReceiverIds = FriendRequest::where('receiver_id', $authUserId)
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('sender_id');

        // Merge both sender and receiver IDs
        $pendingRequestIds = $pendingRequestSenderIds->merge($pendingRequestReceiverIds)->unique()->toArray();

        // Merge friend IDs with pending request IDs to exclude from suggestions
        $excludeIds = array_merge($friendIdsArray, $pendingRequestIds, [$authUserId]);

        // Fetch 10 users, excluding the authenticated user, their friends, and pending friend requests
        $otherUsers = User::whereNotIn('user_id', $excludeIds)
            ->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier')
            ->inRandomOrder() // Randomize the results
            ->limit(10) // Limit to 10 results
            ->get();

        return response()->json(['data' => $otherUsers]);
    }






    //get User info for show others profile 
    public function getUserInfo($id)
{
    $id=cleanInput($id);
    $userId=auth()->user()->user_id;
    // Retrieve the user by ID, selecting specific fields
    $user = User::where('user_id', $id)
        ->select('cover_photo', 'identifier', 'profile_picture', 'user_fname', 'user_lname','privacy_setting')
        ->withCount(['followers', 'followings']) // Keep follower & following counts
        ->first();

    // Check if user not exists
    if (!$user) {
        Log::warning("User not found for ID: {$id}");
        return response()->json([
            'error' => 'User not found'
        ], 404);
    }

    // Retrieve the friend list for the specified user ID
    $friendList = FriendList::where('user_id', $id)->first();

    // Count the number of friends based on user_friends_ids
    $friendCount = ($friendList && !empty($friendList->user_friends_ids))
        ? count(explode(',', $friendList->user_friends_ids))
        : 0;

        $isFollowing = UserFollow::where('follower_id',$userId)
        ->where('following_id', $id)
        ->exists();
    /* friend_status */
     // Default friend_state to 'not_friend'
     $friend_state = 'not_friend';

     // 1. Check if they are already friends by looking for them in the friend list
     $authFriendList = FriendList::where('user_id', $userId)->first();
     if ($authFriendList) {
         $friendIds = explode(',', $authFriendList->user_friends_ids);
         if (in_array($id, $friendIds)) {
             $friend_state = 'friend'; // Already friends
         }
     }
 
     // 2. If not friends, check if a pending friend request exists between the authenticated user and the target user
     if ($friend_state === 'not_friend') {
         // Check if auth user sent a friend request to the target user
         $sentFriendRequest = FriendRequest::where('sender_id', $userId)
             ->where('receiver_id', $id)
             ->where('status', 'pending')
             ->first();
 
         if ($sentFriendRequest) {
             $friend_state = 'sended'; // Auth user sent the request
         }
 
         // Check if the auth user is the receiver of a pending request from the target user
         $receivedFriendRequest = FriendRequest::where('sender_id', $id)
             ->where('receiver_id', $userId)
             ->where('status', 'pending')
             ->first();
 
         if ($receivedFriendRequest) {
             $friend_state = 'received'; // Auth user received the request
         }
     }
 

    // Add friendCount as a column in the user object
    $user->setAttribute('friend_state', $friend_state);
    $user->setAttribute('friends_count', $friendCount);
    $user->setAttribute('is_following', $isFollowing);

    // Return user data with the additional column
    return response()->json(['data' => $user], 200);
}





/* sended,received,   not_friend, ,friend, */



    /* <<<<---- Friend Request Page ----->>>> */

    /* friend request --->>>home  */
    public function friend_request(Request $request)
    {
        // Get Auth user
        $user = auth()->user(); // Retrieve the currently authenticated user
        $userId = $user->user_id; // Get the user ID of the authenticated user

        // Get paginated friend requests with selected user details where status is 'pending'
        $friend_requests = FriendRequest::select(
            'friend_requests.*', // Select all columns from the friend_requests table
            'users.user_id', // Select the user_id column from the users table
            'users.profile_picture', // Select the profile_picture column from the users table
            'users.user_fname', // Select the user_fname column from the users table
            'users.user_lname', // Select the user_lname column from the users table
            'users.identifier' // Select the identifier column from the users table
        )
            ->join('users', 'friend_requests.sender_id', '=', 'users.user_id') // Join the users table on the sender_id column
            ->where('friend_requests.receiver_id', $userId) // Filter the friend requests to only those where the receiver_id matches the authenticated user's ID
            ->where('friend_requests.status', 'pending') // Filter the friend requests to only those with a 'pending' status
            ->paginate(6);

        return response()->json($friend_requests); // Return the paginated results as JSON
    }


    public function getsuggestionfriend()
    {
        $authUser = auth()->user(); // Authenticated user

        // Get the authenticated user's ID
        $authUserId = $authUser->user_id;

        // Retrieve the friend list for the authenticated user
        $authFriendList = FriendList::where('user_id', $authUserId)->first();

        // Initialize friend IDs array
        $friendIdsArray = [];

        if ($authFriendList) {
            // Access the user_friends_ids column
            $userFriendsIds = $authFriendList->user_friends_ids;

            // Check if user_friends_ids column is not empty
            if (!empty($userFriendsIds)) {
                // Split the comma-separated string into an array of friend IDs
                $friendIdsArray = explode(',', $userFriendsIds);
            }
        }

        // Retrieve IDs of users who have a pending friend request with the authenticated user
        $pendingRequestSenderIds = FriendRequest::where('sender_id', $authUserId)
            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('receiver_id');

        $pendingRequestReceiverIds = FriendRequest::where('receiver_id', $authUserId)

            ->whereIn('status', ['pending', 'accepted'])
            ->pluck('sender_id');

        // Merge both sender and receiver IDs
        $pendingRequestIds = $pendingRequestSenderIds->merge($pendingRequestReceiverIds)->unique()->toArray();

        // Merge friend IDs with pending request IDs to exclude from suggestions
        $excludeIds = array_merge($friendIdsArray, $pendingRequestIds, [$authUserId]);

        // Fetch 10 users, excluding the authenticated user, their friends, and pending friend requests
        $otherUsers = User::whereNotIn('user_id', $excludeIds)
            ->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier')
            ->paginate(9); // Ensure page parameter is handled correctly

        return response()->json($otherUsers);
    }


    /* Get sent requests */

    public function getSentFriendRequest()
    {
        // Get the authenticated user ID
        $authUserId = auth()->user()->user_id;

        // Query to retrieve all users who have received a friend request from the authenticated user and the request status is pending
        $sentRequests = User::whereHas('friendRequest', function ($query) use ($authUserId) {
            $query->where('sender_id', $authUserId)
                ->where('status', 'pending');
        })->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier')->paginate(4);;

        return response()->json($sentRequests);
    }
}
