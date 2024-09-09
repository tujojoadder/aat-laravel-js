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

    public function getJoinedGroupsButNotAdminRight()
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
        $perPage = 4; // Adjust this number as needed
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
        $userId = $user->user_id;

        // Find the group by ID
        $group = Groups::where('group_id', $request->id)->first();

        // Initialize variables
        $isAdmin = false;
        $joinStatus = false;
        $isRequest = false;

        // Check if the group exists
        if ($group) {
            // Check if the authenticated user is an admin of the group
            $isAdmin = str_contains($group->group_admins, $userId);

            // Check if the authenticated user is a member of the group
            $joinStatus = UsersHasGroups::where('user_id', $userId)
                ->where('group_id', $request->id)
                ->exists();

            // Check if the authenticated user has a pending join request
            $joinRequest = GroupJoinRequest::where('sender_id', $userId)
                ->where('group_id', $request->id)
                ->first();

            // If a join request exists, set isRequest to true
            $isRequest = $joinRequest ? true : false;
        }

        // Add isAdmin, joinStatus, and isRequest to the group data
        $groupData = $group ? $group->toArray() : [];
        $groupData['isAdmin'] = $isAdmin; // Add isAdmin flag
        $groupData['joinStatus'] = $joinStatus; // Add joinStatus flag
        $groupData['isRequest'] = $isRequest; // Add isRequest flag

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
        ->where('approval',true)
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
            ->where('approval',true)
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

    public function gettAllGroupMemberManage(Request $request)
    {
        // Clean and get the group ID from the request query
        $groupId = cleanInput($request->query('id'));

        // Find the group using the group_id
        $group = Groups::where('group_id', $groupId)->first();

        // Check if group exists
        if (!$group) {
            return response()->json(['error' => 'Group not found.'], 404);
        }

        // Group creator ID
        $groupCreatorId = $group->group_creator;

        // Define the number of members per page
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        // Get the list of admin IDs for the group
        $adminIds = explode(',', $group->group_admins);

        // Retrieve all users associated with this group using pagination
        $users = $group->user()->paginate($perPage, ['*'], 'page', $page);

        // Get the authenticated user's ID
        $authUserId = auth()->user()->user_id;

        // Iterate over the paginated group members to add the isAdmin, isCreator, and isAuth fields
        $users->getCollection()->transform(function ($user) use ($adminIds, $groupId, $groupCreatorId, $authUserId) {
            return [
                'group_id' => $groupId,
                'user_id' => $user->user_id,
                'user_fname' => $user->user_fname,
                'user_lname' => $user->user_lname,
                'profile_picture' => $user->profile_picture,
                'identifier' => $user->identifier,
                'isAdmin' => in_array($user->user_id, $adminIds) ? true : false, // Set isAdmin to true if the member is an admin, false otherwise
                'isCreator' => $user->user_id == $groupCreatorId ? true : false, // Set isCreator to true if the member is the group creator, false otherwise
                'isAuth' => $user->user_id == $authUserId ? true : false, // Set isAuth to true if the member is the authenticated user, false otherwise
            ];
        });

        // Return the group members as a JSON response, including the isAdmin, isCreator, and isAuth fields
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











    //Both(public/private)Set other member admin to group

    public function addAdmin(Request $request, $groupId, $newMember)
    {
        $user = auth()->user();

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



    //Both(public/private) Remove from group
    public function kickOutUser(Request $request, $groupId, $memberId)
    {
        $user = auth()->user(); // The authenticated user (the one performing the action)

        // Clean the input
        $groupId = cleanInput($groupId);
        $memberId = cleanInput($memberId);

        // Find the group
        $group = Groups::where('group_id', $groupId)->first();

        if (!$group) {
            return response()->json(['message' => 'Group not found'], 404);
        }

        // Check if the authenticated user is an admin of the group
        $adminList = explode(',', $group->group_admins);

        if (!in_array($user->user_id, $adminList)) {
            return response()->json(['message' => 'You are not an admin of this group'], 403); // HTTP 403 Forbidden
        }

        // Check if the user being kicked is the group creator
        if ($group->group_creator === $memberId) {
            return response()->json(['message' => 'You cannot kick out the group creator'], 403);
        }

        // Check if the user to be kicked is a member of the group
        $userInGroup = UsersHasGroups::where('group_id', $groupId)
            ->where('user_id', $memberId)
            ->first();

        if (!$userInGroup) {
            return response()->json(['message' => 'User is not a member of this group'], 404);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Remove the user from the group
            // Remove the user from the group
            UsersHasGroups::where('group_id', $groupId)
                ->where('user_id', $memberId)
                ->delete();

            // If the user is an admin, remove them from the admin list
            if (in_array($memberId, $adminList)) {
                $adminList = array_filter(explode(',', $group->group_admins)); // Filter out empty values
                $adminList = array_diff($adminList, [$memberId]); // Remove the user from the admin list
                $group->group_admins = implode(',', $adminList); // Update the admin list in the group
                $group->save();
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'User has been kicked out successfully'], 200);
        } catch (\Exception $e) {
            // Rollback the transaction if something goes wrong
            DB::rollBack();

            // Log detailed error information
            Log::error('Error in kickOutUser: ', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Failed to kick out the user', 'error' => $e->getMessage()], 500);
        }
    }

    /* public group join */

    public function joinPublicGroup(Request $request, $groupId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input

        $groupId = cleanInput($groupId);
        // Data transaction
        DB::transaction(function () use ($userId, $groupId) {
            // Check if the group exists
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Check if the group's audience is public
            if ($group->audience === 'public') {
                // Create a record in the UsersHasGroups model
                UsersHasGroups::create([
                    'user_id' => $userId,
                    'group_id' => $groupId
                ]);
            } else {
                // If the group is not public, throw an exception to trigger transaction rollback
                throw new \Exception('Group is not public');
            }
        });

        // Handle success response
        return response()->json(['message' => 'Operation successful'], 200);
    }


    /* public group leave */

    public function leaveGroup(Request $request, $groupId)
    {


        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input
        $groupId = cleanInput($groupId);
        // Data transaction
        DB::transaction(function () use ($userId, $groupId) {
            // Check if the group exists
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Check if the user is a member of the group
            $membership = UsersHasGroups::where('user_id', $userId)->where('group_id', $groupId)->first();

            if (!$membership) {
                // Membership record does not exist
                throw new \Exception('User is not a member of the group');
            }

            // Remove the user from the group
            UsersHasGroups::where('user_id', $userId)->where('group_id', $groupId)->delete();
        });

        // Handle success response
        return response()->json(['message' => 'Successfully left the group'], 200);
    }


    /* private group join  request*/

    public function joinRequestPrivateGroup(Request $request, $groupId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input
        $groupId = cleanInput($groupId);
        // Data transaction
        DB::transaction(function () use ($userId, $groupId) {
            // Check if the group exists
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Check if the group's audience is private
            if ($group->audience === 'private') {
                $joinRequest = GroupJoinRequest::where('sender_id', $userId)->where('group_id', $groupId)->first();
                if ($joinRequest) {
                    $joinRequest->delete();
                }

                GroupJoinRequest::create([
                    'group_request_id' => Str::uuid(),
                    'sender_id' => $userId,
                    'group_id' => $groupId
                ]);
            } else {
                // If the group is not public, throw an exception to trigger transaction rollback
                throw new \Exception('Group is not private');
            }
        });

        // Handle success response
        return response()->json(['message' => 'Group join request successful'], 200);
    }

    //Cancel private group join request

    public function cancelJoinRequest(Request $request, $groupId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;

        // Clean the input
        $groupId = cleanInput($groupId);

        // Data transaction
        DB::transaction(function () use ($userId, $groupId) {
            // Check if the group exists
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Check if the user has already sent a join request
            $joinRequest = GroupJoinRequest::where('sender_id', $userId)->where('group_id', $groupId)->first();
            if ($joinRequest) {
                $joinRequest->delete(); // Cancel the join request
            } else {
                // If no join request exists, throw an exception to trigger transaction rollback
                throw new \Exception('No join request found');
            }
        });

        // Handle success response
        return response()->json(['message' => 'Join request canceled successfully'], 200);
    }


    /*     get join request for specific group */
    public function getUsersWithJoinRequests(Request $request)
    {
        $users = DB::transaction(function () use ($request) {
            // Retrieve groupId and page from the query parameters
            $groupId = $request->query('groupId');
            $page = $request->query('page', 1); // Default to page 1 if not provided
            $perPage = 5; // Default items per page

            // Check if the group exists
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Retrieve user IDs from join requests for the specified group
            $userIds = GroupJoinRequest::where('group_id', $groupId)
                ->pluck('sender_id')
                ->unique();

            // Paginate users
            return User::whereIn('user_id', $userIds)
                ->paginate($perPage, ['*'], 'page', $page);
        });

        return response()->json($users);
    }





    public function manageJoinGroupRequest(Request $request)
{
    // Validate the input parameters
    $request->validate([
        'decision' => 'required|in:add,cancel',
        'groupId' => 'required|string|max:50',
        'sender_id' => 'required|string|max:50',
    ]);

    $decision = cleanInput($request->decision);
    $groupId = cleanInput($request->groupId);
    $senderId = cleanInput($request->sender_id);

    // Use the DB::transaction method for simplified transactions
    DB::transaction(function () use ($groupId, &$message, $decision, $senderId) {
        $user = auth()->user();

        $group = Groups::where('group_id', $groupId)->first();

        if (!$group) {
            // Group does not exist, throw an exception to trigger transaction rollback
            throw new \Exception('Group not found');
        }

        // Check if the authenticated user is an admin of the group
        $admin = $group->group_admins;
        if (!Str::contains($admin, $user->user_id)) {
            throw new \Exception('You are not an admin of this group');
        }

        $request = GroupJoinRequest::where('group_id', $groupId)
                                   ->where('sender_id', $senderId)
                                   ->first();

        if ($request) {
            if ($decision === 'add') {
                $request->delete();
                // Add the user to the group
               $alreadyMember= UsersHasGroups::where('user_id', $senderId)
                ->where('group_id', $groupId)
                ->first();
                if ($alreadyMember) {
                    $message = 'User added to the group successfully';

                }else{
                    UsersHasGroups::create([
                        'user_id' => $senderId,
                        'group_id' => $groupId,
                    ]);
                    $message = 'User added to the group successfully';

                }               

            } else if ($decision === 'cancel') {
                $request->delete();
                // Just delete the join request
                $message = 'Join request canceled';
            }
          
        } else {
            throw new \Exception('Join request not found');
        }
    });

    return response()->json(['data' => $message], 200);
}


 /* get for specific approval requested group posts  */
 public function getSpecificGroupPostsApprovalRequestes(Request $request)
 {
     $user = auth()->user();
     $specificGroupId = cleanInput($request->query('id'));
     // Debug the value of $specificGroupId
     $perPage = $request->query('per_page', 5);
     $page = $request->query('page', 1);

     $posts = Posts::where('group_id', $specificGroupId)
         ->where('approval',false)
         ->with(['author', 'textPost', 'imagePost'])
         ->paginate($perPage, ['*'], 'page', $page);

     return response()->json($posts);
 }


 public function approvGroupPost(Request $request, $groupId, $postId) {
    // Sanitize input
    $groupId = cleanInput($groupId);
    $postId = cleanInput($postId);

    // Get authenticated user
    $user = auth()->user(); 

    // Use a transaction to ensure atomicity
    DB::transaction(function () use ($user, $groupId, $postId) {
        // Retrieve the group by its ID
        $group = Groups::where('group_id', $groupId)->firstOrFail();

        // Ensure the authenticated user is an admin of the group
        if (!Str::contains($group->group_admins, $user->user_id)) {
            throw new \Exception('You are not authorized to approve posts in this group.');
        }

        // Retrieve the post by its ID within the specified group
        $post = Posts::where('group_id', $groupId)
                      ->where('post_id', $postId)
                      ->firstOrFail();

        // Approve the post
        $post->update(['approval' => true]);
    }, 5); // Retry the transaction up to 5 times if it fails due to deadlock
}



public function RejectApprovGroupPost(Request $request, $groupId, $postId) {
    // Sanitize input
    $groupId = cleanInput($groupId);
    $postId = cleanInput($postId);

    // Get authenticated user
    $user = auth()->user(); 

    // Use a transaction to ensure atomicity
    DB::transaction(function () use ($user, $groupId, $postId) {
        // Retrieve the group by its ID
        $group = Groups::where('group_id', $groupId)->firstOrFail();

        // Ensure the authenticated user is an admin of the group
        if (!Str::contains($group->group_admins, $user->user_id)) {
            throw new \Exception('You are not authorized to approve posts in this group.');
        }

        // Retrieve the post by its ID within the specified group
        $post = Posts::where('group_id', $groupId)
                      ->where('post_id', $postId)
                      ->firstOrFail();

        // Approve the post
        $post->delete();
    }, 5); // Retry the transaction up to 5 times if it fails due to deadlock
}



}
