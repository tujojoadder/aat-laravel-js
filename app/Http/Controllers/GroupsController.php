<?php

namespace App\Http\Controllers;

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
            'group_picture' => 'storage/defaultProfile/group.jpg',
            'group_cover' => 'storage/defaultCover/group.jpg',


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
}
