<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\FriendRequest;
use App\Models\GroupJoinRequest;
use App\Models\Groups;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use App\Models\UsersHasGroups;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GroupsController extends Controller
{
    // Function to generate a unique identifier with at least three numbers appended
    private function generateIdentifier($baseIdentifier)
    {
        // If baseIdentifier is empty, generate a random six-letter string
        if (empty($baseIdentifier)) {
            $baseIdentifier = '';
            for ($i = 0; $i < 6; $i++) {
                $baseIdentifier .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
            }
        }

        // Append an underscore (_) followed by two random letters
        $letters = '_';
        for ($i = 0; $i < 2; $i++) {
            $letters .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
        }
        $baseIdentifier .= $letters;

        // Check if the generated identifier already exists
        while (
            User::where('identifier', $baseIdentifier)->exists() ||
            Groups::where('identifier', $baseIdentifier)->exists() ||
            Pages::where('identifier', $baseIdentifier)->exists()
        ) {
            // If it does, append new random letters
            $letters = '_';
            for ($i = 0; $i < 2; $i++) {
                $letters .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
            }
            $baseIdentifier .= $letters;
        }

        return $baseIdentifier;
    }
    // give group audience in(public,private,only_me)
    public function createGroup(Request $request)
    {
        $user = auth()->user();
        $group_id = Str::uuid();
        $this->validate($request, [
            'group_name' => 'required|string|max:50',
            'group_details' => 'required|string|max:10000',
            'audience' => 'required|in:public,private,only_me'
        ], [
            'group_name.regex' => 'The group name may only contain letters, numbers'
        ]);

        $group_name = cleanInput($request->group_name);
        $group_details = cleanInput($request->group_details);
        $audience = cleanInput($request->audience);

        // Remove all spaces from the group name
        $group_nameidentifierBase = preg_replace('/[^\p{L}0-9]+/u', '', $group_name);

        $identifierBase = strtolower($group_nameidentifierBase);

        // Generate the identifier
        $identifier = $this->generateIdentifier($identifierBase);

        Groups::create([
            'group_id' => $group_id,
            'identifier' => $identifier,
            'group_name' => $group_name,
            'group_details' => $group_details,
            'group_creator' => $user->user_id,
            'group_admins' => $user->user_id,
            'audience' => $audience,
            'group_picture' => 'http://127.0.0.1:8000/storage/mprofile_picture/group.jpg',
            'group_cover' => 'http://127.0.0.1:8000/storage/cover_photo/group.jpg',


        ]);
        UsersHasGroups::create([
            'user_id' => $user->user_id,
            'group_id' => $group_id
        ]);

        return response()->json([
            'message' => 'Group is created successfully'
        ]);
    }

    // add any user to group
    public function addMember(Request $request, $groupId, $newMember)
    {

        $user = auth()->user();

        $request->merge(['newMember' => $newMember]);
        $request->merge(['groupId' => $groupId]);
        // Validate input parameters
        $this->validate($request, [
            'groupId' => 'required|string|max:50',
            'newMember' => 'required|string|max:50',
        ]);
        $groupId = cleanInput($groupId);
        $newMember = cleanInput($newMember);
        $isNewMember = User::find($newMember);
        if (!$isNewMember) {
            return response([
                'message' => 'New member Id not founded'
            ]);
        }

        // Your existing logic for checking if the user is an admin and if the new member is valid
        $group = Groups::where('group_id', $groupId)->first();

        if ($group) {
            $admin = $group->group_admins;
            if (!Str::contains($admin, $user->user_id)) {
                return response([
                    'message' => 'You are not admin'
                ]);
            } else {

                // Check if the user is already a member of the group
                $existingMembership = UsersHasGroups::where('user_id', $newMember)
                    ->where('group_id', $groupId)
                    ->first();

                if ($existingMembership) {
                    return response([
                        'message' => 'User is already a member of the group'
                    ], 422);
                }



                // Retrieve any existing join requests
                $existingRequest = GroupJoinRequest::where('sender_id', $newMember)
                    ->where('group_id', $groupId)
                    ->first();

                if ($existingRequest) {
                    $existingRequest->delete();
                }

                // Create a new UserGroup record
                UsersHasGroups::create([
                    'user_id' => $newMember,
                    'group_id' => $groupId
                ]);


                return response([

                    'message' => 'New member added to group successfully'
                ], 200);
            }
        } else {
            return response([
                'message' => 'Group not found'
            ]);
        }
    }

    //Set other member admin to group

    public function addAdmin(Request $request, $groupId, $newMember)
    {
        $user = auth()->user();
        $request->merge(['groupId' => $groupId]);
        $request->merge(['newMember' => $newMember]);
        // Validate input parameters
        $this->validate($request, [
            'groupId' => 'required|string|max:50',
            'newMember' => 'required|string|max:50',
        ]);
        $groupId = cleanInput($groupId);
        $newMember = cleanInput($newMember);
        $isnewMember = User::find($newMember);
        if (!$isnewMember) {
            return response([
                'message' => 'New Member Id not founded'
            ]);
        }

        // Your existing logic for checking if the user is an admin and if the new member is valid
        $group = Groups::where('group_id', $groupId)->first();

        if ($group) {
            $admin = $group->group_admins;
            if (!Str::contains($admin, $user->user_id)) {
                return response([
                    'message' => 'You are not admin'
                ]);
            } else {

                if (Str::contains($admin, $newMember)) {
                    return response([
                        'message' => 'User is already an admin of this group.'
                    ], 422); // HTTP 422 Unprocessable Entity
                } else {
                    $newmemberId = User::where('user_id', $newMember)->first();
                    if ($newmemberId->groups->contains($groupId)) {
                        $admin_list = explode(',', $admin); // Split the string into an array

                        $number_of_admins = count($admin_list);

                        if ($number_of_admins < 30) {
                            // Update the admin column with the concatenated value
                            $newAdminValue = $admin . ',' . $newMember;
                            $group->update(['group_admins' => $newAdminValue]);

                            return response([
                                'message' => 'Admin updated successfully.'
                            ]);
                        } else {
                            return response([
                                'message' => 'There can be maximum 50 admins for group'
                            ]);
                        }
                    } else {
                        return response([
                            'message' => 'The user is not member of this group'
                        ]);
                    }
                }
            }
        } else {
            return response([
                'message' => 'Group not found'
            ]);
        }
    }

    //Retrive Group Members
    public function getGroupMembers($groupId)
    {
        // Find the group
        $group = Groups::find($groupId);

        // Check if the group exists
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Get the user_ids of the group members
        $userIds = $group->user()->pluck('users.user_id');

        // Return the list of group members' user_ids
        return response()->json(['members' => $userIds]);
    }
    //Retrive all post from group
    public function getGroupPosts($groupId)
    {
        $user = auth()->user();
        $userId = $user->user_id;

        // Retrieve the group and check if the user is a member
        $group = Groups::where('group_id', $groupId)->first();

        // Check if the group exists
        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }
        // Retrieve the group and check if the user is a member
        $isMember = UsersHasGroups::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();

        if (!$isMember && $group->audience == 'private') {
            return response()->json(['message' => 'Group is Private']);
        }

        // Retrieve all posts for the group with their respective text or image posts and authors
        $posts = Posts::where('group_id', $groupId)
            ->with(['textPost', 'imagePost', 'author'])
            ->orderBy('created_at', 'desc') // Order by creation date in descending order
            ->get();

        // Define an array to hold the formatted posts data
        $formattedPosts = [];

        // Iterate through each post and format the data
        foreach ($posts as $post) {
            $postContent = null;

            // Check if the post has both text and image content
            if ($post->textPost && $post->imagePost) {
                // If both text and image exist, include both in the post content
                $postContent = [
                    'text' => $post->textPost->post_text,
                    'image' => $post->imagePost->post_url,
                ];
            } elseif ($post->textPost) {
                // If only text exists, include text in the post content
                $postContent = [
                    'text' => $post->textPost->post_text,
                    'image' => null, // Set image to null
                ];
            } elseif ($post->imagePost) {
                // If only image exists, include image in the post content
                $postContent = [
                    'text' => null, // Set text to null
                    'image' => $post->imagePost->post_url,
                ];
            }

            // Format the post data including author details
            $formattedPost = [
                'post_id' => $post->post_id,
                'post_content' => $postContent,
                'author' => [
                    'user_id' => $post->author->user_id,
                    'full_name' => $post->author->user_fname . " " . $post->author->user_lname, // Assuming there's a full_name attribute for the author
                    // Include other author details as needed
                ],
            ];

            // Add the formatted post to the array
            $formattedPosts[] = $formattedPost;
        }

        // Return the formatted posts data
        return response()->json(['group' => $group->group_id, 'posts' => $formattedPosts]);
    }


    

    // Update Group name
    public function updateGroupName($groupId, Request $request)
    {

        $request->merge(['groupId' => $groupId]);
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'groupId' => 'required|string|max:50',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $groupId = cleanInput($groupId);
        $name = cleanInput($request->name);

        $groupMember = UsersHasGroups::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();
        if (!$groupMember) {
            return response([
                'message' => 'You are not member of this group'
            ], 422);
        }


        $group = Groups::where('group_id', $groupId)->first();

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }
        $group->update(['group_name' => $name]);
        return response()->json(['message' => 'Group name updated successfully']);
    }








    //Update group details
    public function updateGroupDetails($groupId, Request $request)
    {

        $request->merge(['groupId' => $groupId]);
        $this->validate($request, [
            'details' => 'required|string|max:10000',
            'groupId' => 'required|string|max:50',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $groupId = cleanInput($groupId);
        $details = cleanInput($request->details);

        $groupMember = UsersHasGroups::where('user_id', $userId)
            ->where('group_id', $groupId)
            ->first();
        if (!$groupMember) {
            return response([
                'message' => 'You are not member of this group'
            ], 422);
        }


        $group = Groups::where('group_id', $groupId)->first();

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }
        $group->update(['group_details' => $details]);
        return response()->json(['message' => 'Group details updated successfully']);
    }






 /*    Groups that auth user are not admin */
    public function getJoinedGroupsButNotAdmin()
    {
        // Get the currently authenticated user
        $user = auth()->user();
    
        // Define the number of items per page
        $perPage = 9; // Adjust this number as needed
        $page = request()->input('page', 1); // Get current page from query parameter, default to 1
    
        // Retrieve all groups that the user has joined
        $joinedGroups = $user->groups()->get(); // Retrieve all groups
    
        // Filter out the groups where the user is an admin
        $groupsNotAdmin = $joinedGroups->filter(function ($group) use ($user) {
            // Check if the user is not listed in the group_admins field
            return !str_contains($group->group_admins, $user->user_id);
        });
    
        // Calculate the total number of pages
        $totalItems = $groupsNotAdmin->count();
        $totalPages = ceil($totalItems / $perPage);
    
        // Slice the filtered results for pagination
        $pagedGroups = $groupsNotAdmin->slice(($page - 1) * $perPage, $perPage)->values();
    
        // Map to select only the desired fields
        $groupsArray = $pagedGroups->map(function ($group) {
            return [
                'group_name' => $group->group_name,
                'group_id' => $group->group_id,
                'identifier' => $group->identifier,
                'group_cover' => $group->group_cover,
                'group_picture' => $group->group_picture,
                'audience' => $group->audience,
            ];
        });
    
        // Return the filtered list of groups as an array with pagination metadata
        return response()->json([
            'data' => $groupsArray,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }


 /*    Groups that auth user are admin */
    public function getGroupsWhereAdmin()
    {
        // Get the currently authenticated user
        $user = auth()->user();
    
        // Define the number of items per page
        $perPage = 10; // Adjust this number as needed
        $page = request()->input('page', 1); // Get current page from query parameter, default to 1
    
        // Retrieve all groups that the user has joined
        $joinedGroups = $user->groups()->get(); // Retrieve all groups
    
        // Filter out the groups where the user is an admin
        $groupsWhereAdmin = $joinedGroups->filter(function ($group) use ($user) {
            // Check if the user is listed in the group_admins field
            return str_contains($group->group_admins, $user->user_id);
        });
    
        // Calculate the total number of pages
        $totalItems = $groupsWhereAdmin->count();
        $totalPages = ceil($totalItems / $perPage);
    
        // Slice the filtered results for pagination
        $pagedGroups = $groupsWhereAdmin->slice(($page - 1) * $perPage, $perPage)->values();
    
        // Map to select only the desired fields
        $groupsArray = $pagedGroups->map(function ($group) {
            return [
                'group_name' => $group->group_name,
                'group_id' => $group->group_id,
                'identifier' => $group->identifier,
                'group_cover' => $group->group_cover,
                'group_picture' => $group->group_picture,
                'audience' => $group->audience,
            ];
        });
    
        // Return the filtered list of groups as an array with pagination metadata
        return response()->json([
            'data' => $groupsArray,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }
    
   /*  getGroupSuggestion */
  
   public function getGroupSuggestion()
   {
       // Get the currently authenticated user
       $user = auth()->user();
   
       // Define the number of items per page
       $perPage = 9; // Adjust this number as needed
       $page = request()->input('page', 1); // Get current page from query parameter, default to 1
   
       // Retrieve all groups
       $allGroups = Groups::all(); // Assuming Groups is your model
   
       // Retrieve groups the user has joined
       $joinedGroups = $user->groups()->pluck('groups.group_id'); // Get IDs of groups user is part of
   
       // Filter out the groups where the user is an admin
       $adminGroups = $user->groups()->whereRaw("FIND_IN_SET(?, groups.group_admins)", [$user->user_id])->pluck('groups.group_id'); // Get IDs of groups where user is an admin
   
       // Filter groups that the user has not joined and is not an admin
       $groupsNotJoinedOrAdmin = $allGroups->filter(function ($group) use ($joinedGroups, $adminGroups) {
           return !$joinedGroups->contains($group->group_id) && !$adminGroups->contains($group->group_id);
       });
   
       // Calculate the total number of pages
       $totalItems = $groupsNotJoinedOrAdmin->count();
       $totalPages = ceil($totalItems / $perPage);
   
       // Slice the filtered results for pagination
       $pagedGroups = $groupsNotJoinedOrAdmin->slice(($page - 1) * $perPage, $perPage)->values();
   
       // Map to select only the desired fields
       $groupsArray = $pagedGroups->map(function ($group) {
           return [
               'group_name' => $group->group_name,
               'group_id' => $group->group_id,
               'identifier' => $group->identifier,
               'group_cover' => $group->group_cover,
               'group_picture' => $group->group_picture,
               'audience' => $group->audience,
           ];
       });
   
       // Return the filtered list of groups as an array with pagination metadata
       return response()->json([
           'data' => $groupsArray,
           'current_page' => (int)$page,
           'per_page' => $perPage,
           'total' => $totalItems,
           'total_pages' => $totalPages
       ]);
   }
   

    


    

    //get specific groupdetails
    public function groupDetails(Request $request)
    {
        // Get Authenticated user
        $user = auth()->user();
    
        // Find the group by ID
        $group = Groups::where('group_id', $request->id)->first();
    
        // Initialize isAdmin as false
        $isAdmin = false;
    
        // Check if the group exists and the authenticated user is an admin of the group
        if ($group) {
            $isAdmin = str_contains($group->group_admins, $user->user_id);
        }
    
        // Add isAdmin to the group data
        $groupData = $group->toArray(); // Convert group model to array
        $groupData['isAdmin'] = $isAdmin; // Add isAdmin flag
    
        return response()->json(['data' => $groupData]);
    }
    






    
    /* get for specific group posts  */
    public function getSpecificGroupPosts(Request $request)
    {
        $user = auth()->user();
        $specificGroupId = cleanInput($request->query('id'));
        // Debug the value of $specificGroupId
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);

        $posts = Posts::where('group_id', $specificGroupId)
            ->with(['author', 'textPost', 'imagePost'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($posts);
    }


    /* get for specific group all photo */
    public function getSpecificGroupPhotos(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificGroupId = cleanInput($request->query('id'));

        // Set default pagination values, with the option to customize via query parameters
        $perPage = $request->query('per_page', 6); // default to 10 per page
        $page = $request->query('page', 1);

        // Query for the posts with associated image posts for the specific user, paginate the results
        $posts = Posts::where('group_id', $specificGroupId)
            ->with('imagePost') // Eager load the image posts relationship
            ->whereHas('imagePost') // Ensure we only get posts with associated image posts
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated result as JSON
        return response()->json($posts);
    }

    /* Get all users of a specific group */
    public function gettAllGroupMember(Request $request)
    {
        // Clean and get the group ID from the request query
        $groupId = cleanInput($request->query('id'));

        // Find the group using the group_id
        $group = Groups::where('group_id', $groupId)->first();

        // Check if group exists
        if (!$group) {
            return response()->json(['error' => 'Group not found.'], 404);
        }

        // Define the number of members per page
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        // Get the authenticated user's ID
        $authUserId = auth()->id();

        // Retrieve the friend list for the authenticated user
        $authFriendList = FriendList::where('user_id', $authUserId)->first();

        // Retrieve the authenticated user's friend IDs if available
        $authFriendIdsArray = [];
        if ($authFriendList && !empty($authFriendList->user_friends_ids)) {
            $authFriendIdsArray = explode(',', $authFriendList->user_friends_ids);
        }

        // Retrieve all users associated with this group using pagination
        $users = $group->user()->paginate($perPage, ['*'], 'page', $page);

        // Iterate over the paginated group members to add the is_friend and friend_request_sent fields
        $users->getCollection()->transform(function ($user) use ($authFriendIdsArray, $authUserId) {
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
                'is_friend' => in_array($user->user_id, $authFriendIdsArray) ? true : false, // Set is_friend to true if the member is in the auth user's friend list, false otherwise
                'friend_request_sent' => $friendRequestExists ? true : false, // Set friend_request_sent to true if a friend request has been sent, false otherwise
            ];
        });

        // Return the group members as JSON response, including the is_friend and friend_request_sent fields
        return response()->json($users);
    }


    /* get posts where group_id is not null */
    public function getRandomGroupPosts(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);
    
        // Fetch posts where group_id is not null
        $posts = Posts::whereNotNull('group_id')
            ->with([
                'author:user_id,user_lname,user_fname,profile_picture,identifier',
                'textPost',
                'imagePost',
                'group:group_id,group_name,group_picture' // Only select group_id and group_picture from the group table
            ])
            ->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json($posts);
    }
    

}
