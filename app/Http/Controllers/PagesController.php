<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\UploadRequest;
use App\Models\User;
use App\Models\UsersHasPages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class PagesController extends Controller
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
    // give page audience in(public,private,only_me)
    public function createPage(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = Str::uuid();


        $this->validate($request, [
            'page_name' => 'required|string|max:50',
            'page_details' => 'required|string|max:10000',
        ]);


        $pageName = cleanInput($request->page_name);
        $pageNameIdentifire = preg_replace('/[^\p{L}0-9]+/u', '', $pageName);;
        $pageDetails = cleanInput($request->page_details);

        $identifierBase = strtolower(str_replace(' ', '', $pageNameIdentifire));

        // Generate the identifier
        $identifier = $this->generateIdentifier($identifierBase);

        Pages::create([
            'page_id' => $pageId,
            'identifier' => $identifier,
            'page_name' => $pageName,
            'page_details' => $pageDetails,
            'page_creator' => $userId,
            'page_admins' => $userId,
            'page_picture' => 'storage/defaultProfile/page.png',
            'page_cover' => 'storage/defaultCover/page.jpg',


        ]);
        UsersHasPages::create([
            'user_id' => $userId,
            'page_id' => $pageId
        ]);

        return response()->json([
            'message' => 'Page is created successfully'
        ]);
    }
    //Follow or Unfollow any page
    public function FollowOrUnFollowPage(Request $request, $pageId)
    {
        // Retrieve the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'pageId' => 'required|string|max:50',
        ]);
        $pageId = cleanInput($pageId);
        $page = Pages::where('page_id', $pageId)->first();



        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Check if the user is already a member of the page
        $isMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->exists();

        if (!$isMember) {
            // If the user is not a member, create the association
            UsersHasPages::create([
                'user_id' => $userId,
                'page_id' => $pageId
            ]);
            return response()->json(['message' => 'You have followed the page.']);
        } else {
            // If the user is a member, remove the association
            UsersHasPages::where('user_id', $userId)
                ->where('page_id', $pageId)
                ->delete();
            return response()->json(['message' => 'You have unfollowed the page']);
        }
    }

    //Add admins on pages

    public function addAdmin(Request $request, $pageId, $newAdmin)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['pageId' => $pageId]);
        $request->merge(['newAdmin' => $newAdmin]);
        // Validate input parameters
        $this->validate($request, [
            'pageId' => 'required',
            'newAdmin' => 'required',
        ]);
        $pageId = cleanInput($pageId);
        $newAdmin = cleanInput($newAdmin);
        $isnewAdmin=User::find($newAdmin);
        if (!$isnewAdmin) {
            return response([
                'message' => 'New admin Id not founded'
            ]);
        }

        // Your existing logic for checking if the user is an admin and if the new member is valid
        $page = Pages::where('page_id', $pageId)->first();

        if ($page) {
            $admin = $page->page_admins;
            if (!Str::contains($admin, $userId)) {
                return response([
                    'message' => 'You are not admin'
                ]);
            } else {

                if (Str::contains($admin, $newAdmin)) {
                    return response([
                        'message' => 'User is already an admin of this Page.'
                    ], 422); // HTTP 422 Unprocessable Entity
                }
                $admin_list = explode(',', $admin); // Split the string into an array

                $number_of_admins = count($admin_list);

                if ($number_of_admins < 30) {
                    // Update the admin column with the concatenated value
                    $newAdminValue = $admin . ',' . $newAdmin;
                    $page->update(['page_admins' => $newAdminValue]);

                    return response([
                        'message' => 'Admin updated successfully.'
                    ]);
                } else {
                    return response([
                        'message' => 'There can be maximum 30 admins for page'
                    ]);
                }
            }
        } else {
            return response([
                'message' => 'Page not found'
            ]);
        }
    }


    //Retrive Page Members
    public function getPageMembers($pageId)
    {
        $pageId = cleanInput($pageId);

        // Find the page
        $page = Pages::find($pageId);

        // Check if the page exists
        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Get the user_ids of the page members
        $userIds = $page->user()->pluck('users.user_id');

        // Check if there are no members
        if ($userIds->isEmpty()) {
            return response()->json(['message' => 'No members found for this page']);
        }

        // Return the list of page members' user_ids
        return response()->json(['members' => $userIds]);
    }

    //Retrive all post from page
    public function getPagePosts($pageId)
    {
        $user = auth()->user();
        $userId = $user->user_id;

        $page = Pages::where('page_id', $pageId)->first();

        // Check if the page exists
        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Retrieve all posts for the page with their respective text or image posts and authors
        $posts = Posts::where('page_id', $pageId)
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
        return response()->json([
            'page' => $pageId,
            'page_profile' => $page->page_picture,
            'page_name' => $page->page_name,
            'page_cover' => $page->page_cover,
            'posts' => $formattedPosts
        ]);
    }

    // Update Page name
    public function updatePageName($pageId, Request $request)
    {

        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'name' => 'required|string|max:50',
            'pageId' => 'required',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $name = cleanInput($request->name);
        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $admin = $page->page_admins;
        if (!Str::contains($admin, $userId)) {
            return response([
                'message' => 'You are not admin'
            ]);
        }

        $page->update(['page_name' => $name]);
        return response()->json(['message' => 'Page name updated successfully']);
    }



    //Update page details
    public function updatePageDetails($pageId, Request $request)
    {

        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'details' => 'required|string|max:10000',
            'pageId' => 'required|string|max:50',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $details = cleanInput($request->details);
        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $admin = $page->page_admins;
        if (!Str::contains($admin, $userId)) {
            return response([
                'message' => 'You are not admin'
            ]);
        }
        $page->update(['page_details' => $details]);
        return response()->json(['message' => 'Page details updated successfully']);
    }
}
