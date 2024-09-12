<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\FriendRequest;
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

    // app/Http/Controllers/PageController.php

    public function createPage(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'page_name' => 'required|string|max:35',
            'page_details' => 'required|string|max:10000',
            'category' => 'required|string|max:35',
        ]);

        // Extract and clean input data
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = Str::uuid();
        $pageName = cleanInput($validatedData['page_name']);
        $pageNameIdentifier = preg_replace('/[^\p{L}0-9]+/u', '', $pageName);
        $pageDetails = cleanInput($validatedData['page_details']);
        $pageCategory = cleanInput($validatedData['category']);

        $identifierBase = strtolower(str_replace(' ', '', $pageNameIdentifier));

        // Generate the identifier
        $identifier = $this->generateIdentifier($identifierBase);

        try {
            DB::transaction(function () use (
                $pageId,
                $identifier,
                $pageName,
                $pageDetails,
                $userId,
                $pageCategory
            ) {
                // Create the page
                Pages::create([
                    'page_id' => $pageId,
                    'identifier' => $identifier,
                    'page_name' => $pageName,
                    'page_details' => $pageDetails,
                    'page_creator' => $userId,
                    'page_admins' => $userId,
                    'page_picture' => 'http://127.0.0.1:8000/storage/mprofile_picture/page.jpg',
                    'page_cover' => 'http://127.0.0.1:8000/storage/cover_photo/page.jpg',
                    'category' => $pageCategory,
                ]);

                // Associate the user with the page
                UsersHasPages::create([
                    'user_id' => $userId,
                    'page_id' => $pageId,
                ]);
            });

            return response()->json([
                'message' => 'Page created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the page.',
            ], 500);
        }
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
        $isnewAdmin = User::find($newAdmin);
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



    /*    Pages that auth user are admin */
    public function getPagesWhereAdmin()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Define the number of items per page
        $perPage = 4; // Adjust this number as needed
        $page = request()->input('page', 1); // Get current page from query parameter, default to 1

        // Retrieve all pages that the user has joined
        $joinedPages = $user->pages()->get(); // Retrieve all groups

        // Filter out the pages where the user is an admin
        $pagesWhereAdmin = $joinedPages->filter(function ($page) use ($user) {
            // Check if the user is listed in the page_admins field
            return str_contains($page->page_admins, $user->user_id);
        });

        // Calculate the total number of pages
        $totalItems = $pagesWhereAdmin->count();
        $totalPages = ceil($totalItems / $perPage);

        // Slice the filtered results for pagination
        $pagedPages = $pagesWhereAdmin->slice(($page - 1) * $perPage, $perPage)->values();

        // Map to select only the desired fields
        $pagesArray = $pagedPages->map(function ($page) {
            return [
                'page_name' => $page->page_name,
                'page_id' => $page->page_id,
                'identifier' => $page->identifier,
                'page_picture' => $page->page_picture,

            ];
        });

        // Return the filtered list of groups as an array with pagination metadata
        return response()->json([
            'data' => $pagesArray,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }


    public function getLikedPages()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Define the number of items per page
        $perPage = 4; // Adjust this number as needed
        $page = request()->input('page', 1); // Get current page from query parameter, default to 1

        // Retrieve all pages that the user has joined
        $joinedPages = $user->pages()->get(); // Retrieve all pages

        // Filter out the pages where the user is not an admin
        $pagesWhereNotAdmin = $joinedPages->filter(function ($page) use ($user) {
            // Check if the user is not listed in the page_admins field
            return !str_contains($page->page_admins, $user->user_id);
        });

        // Calculate the total number of pages
        $totalItems = $pagesWhereNotAdmin->count();
        $totalPages = ceil($totalItems / $perPage);

        // Slice the filtered results for pagination
        $pagedPages = $pagesWhereNotAdmin->slice(($page - 1) * $perPage, $perPage)->values();

        // Map to select only the desired fields
        $pagesArray = $pagedPages->map(function ($page) {
            return [
                'page_name' => $page->page_name,
                'page_id' => $page->page_id,
                'identifier' => $page->identifier,
                'page_picture' => $page->page_picture,
            ];
        });

        // Return the filtered list of pages as an array with pagination metadata
        return response()->json([
            'data' => $pagesArray,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }


    public function getPageSuggestion()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Define the number of items per page
        $perPage = 4; // Adjust this number as needed
        $page = request()->input('page', 1); // Get current page from query parameter, default to 1

        // Retrieve all pages that the user has joined
        $allPages = Pages::all(); // Retrieve all pages

        // Filter out the pages where the user is a member or an admin
        $pagesWhereNotMemberOrAdmin = $allPages->filter(function ($page) use ($user) {
            // Check if the user is not listed in the page_admins and is not a member
            return !str_contains($page->page_admins, $user->user_id) && !$user->pages->contains('page_id', $page->page_id);
        });

        // Calculate the total number of pages
        $totalItems = $pagesWhereNotMemberOrAdmin->count();
        $totalPages = ceil($totalItems / $perPage);

        // Slice the filtered results for pagination
        $pagedPages = $pagesWhereNotMemberOrAdmin->slice(($page - 1) * $perPage, $perPage)->values();

        // Map to select only the desired fields
        $pagesArray = $pagedPages->map(function ($page) {
            return [
                'page_name' => $page->page_name,
                'page_id' => $page->page_id,
                'identifier' => $page->identifier,
                'page_picture' => $page->page_picture,
            ];
        });

        // Return the filtered list of pages as an array with pagination metadata
        return response()->json([
            'data' => $pagesArray,
            'current_page' => (int)$page,
            'per_page' => $perPage,
            'total' => $totalItems,
            'total_pages' => $totalPages
        ]);
    }




    //get specific page details
    public function pageDetails(Request $request)
    {
        // Get Authenticated user
        $user = auth()->user();
        $userId = $user->user_id;

        // Find the group by ID
        $page = Pages::select(
            'page_id',
            'page_name',
            'page_details',
            'identifier',
            'page_picture',
            'page_cover',
            'category',
            'location',
            'phone',
            'email',
        )
            ->where('page_id', $request->id)->first();

        // Initialize variables
        $isAdmin = false;
        $joinStatus = false;


        // Check if the group exists
        if ($page) {
            // Check if the authenticated user is an admin of the group
            $isAdmin = str_contains($page->page_admins, $userId);

            // Check if the authenticated user is a member of the group
            $joinStatus = UsersHasPages::where('user_id', $userId)
                ->where('page_id', $request->id)
                ->exists();
        }
        // Add isAdmin, joinStatus, and isRequest to the group data
        $pageData = $page ? $page->toArray() : [];
        $pageData['isAdmin'] = $isAdmin; // Add isAdmin flag
        $pageData['joinStatus'] = $joinStatus; // Add joinStatus flag
        return response()->json(['data' => $pageData]);
    }


    /* getSpecificPagePosts */

    /* get for specific group posts  */
    public function getSpecificPagePosts(Request $request)
    {
        $user = auth()->user();
        $specificPageId = cleanInput($request->query('id'));
        // Debug the value of $specificPageId
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);

        $posts = Posts::where('page_id', $specificPageId)
            ->with(['author', 'textPost', 'imagePost'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($posts);
    }


    /* get for specific page all photo */
    public function getSpecificPagePhotos(Request $request)
    {

        // Clean the input and get the user ID from the request query
        $specificPageId = cleanInput($request->query('id'));

        // Set default pagination values, with the option to customize via query parameters
        $perPage = $request->query('per_page', 6); // default to 10 per page
        $page = $request->query('page', 1);

        // Query for the posts with associated image posts for the specific user, paginate the results
        $posts = Posts::where('page_id', $specificPageId)

            ->with('imagePost') // Eager load the image posts relationship
            ->whereHas('imagePost') // Ensure we only get posts with associated image posts
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated result as JSON
        return response()->json($posts);
    }


    /* Get all users of a specific group */
    public function getAllPageMember(Request $request)
    {
        // Clean and get the page ID from the request query
        $pageId = cleanInput($request->query('id'));

        // Find the page using the page_id
        $pages = Pages::where('page_id', $pageId)->first();

        // Check if the page exists
        if (!$pages) {
            return response()->json(['error' => 'Page not found.'], 404);
        }

        // Define the number of members per page
        $perPage = $request->query('per_page', 10);
        // Page number
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

        // Retrieve all users associated with this page using pagination
        $users = $pages->user()->paginate($perPage, ['*'], 'page', $page);

        // Iterate over the paginated page members to add the is_friend and friend_request_sent fields
        $users->getCollection()->transform(function ($user) use ($authFriendIdsArray, $authUserId) {
            // Check if the current user is the authenticated user
            if ($user->user_id == $authUserId) {
                return [
                    'user_id' => $user->user_id,
                    'user_fname' => $user->user_fname,
                    'user_lname' => $user->user_lname,
                    'profile_picture' => $user->profile_picture,
                    'identifier' => $user->identifier,
                    'is_friend' => true, // Always true for the authenticated user
                    'friend_request_sent' => false, // No friend request is needed for oneself
                ];
            }

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
                'is_friend' => in_array($user->user_id, $authFriendIdsArray) ? true : false, // Set is_friend based on the friend list
                'friend_request_sent' => $friendRequestExists ? true : false, // Set friend_request_sent based on friend request status
            ];
        });

        // Return the page members as a JSON response, including the is_friend and friend_request_sent fields
        return response()->json($users);
    }
}
