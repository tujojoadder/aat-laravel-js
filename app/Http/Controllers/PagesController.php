<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\FriendList;
use App\Models\FriendRequest;
use App\Models\Groups;
use App\Models\IAccount;
use App\Models\Loves;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\Replies;
use App\Models\UniqeUser;
use App\Models\Unlikes;
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
            Pages::where('identifier', $baseIdentifier)->exists() ||
            IAccount::where('identifier', $baseIdentifier)->exists()
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
            'page_name' => 'required|string|max:40',
            'page_details' => 'required|string|max:10000',
            'category' => 'required|string|max:40',
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

    /* //Add admins on pages

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
    } */


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

        // Find the page by ID, selecting the fields except 'page_admins'
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
            'email'
        )
            ->where('page_id', $request->id)->firstOrFail();

        // Initialize variables
        $isAdmin = false;
        $joinStatus = false;

        // Check if the page exists
        if ($page) {
            // Fetch the page_admins to process, but not return
            $pageAdmins = Pages::where('page_id', $request->id)->value('page_admins');

            // Check if the authenticated user is an admin of the page
            $isAdmin = str_contains($pageAdmins, $userId);

            // Check if the authenticated user is a member of the page
            $joinStatus = UsersHasPages::where('user_id', $userId)
                ->where('page_id', $request->id)
                ->exists();
        }

        // Convert the page data to an array and add custom flags
        $pageData = $page ? $page->toArray() : [];
        $pageData['isAdmin'] = $isAdmin; // Add isAdmin flag
        $pageData['joinStatus'] = $joinStatus; // Add joinStatus flag

        // Return the response without 'page_admins'
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
            ->with(['page:identifier,page_name,page_picture,page_id', 'textPost', 'imagePost'])
            ->paginate($perPage, ['*'], 'page', $page);
        // Add isLove, isUnlike, totalLove, and totalUnlike to each post
        $posts->getCollection()->transform(function ($post) use ($user) {
            // Check if the current user has loved or unliked the post
            $isLove = Loves::where('love_on_type', 'post')
                ->where('love_on_id', $post->post_id)
                ->where('love_by_id', $user->user_id)
                ->exists();

            $isUnlike = Unlikes::where('unlike_on_type', 'post')
                ->where('unlike_on_id', $post->post_id)
                ->where('unlike_by_id', $user->user_id)
                ->exists();

            // Count the total loves and unlikes for the post
            $totalLove = Loves::where('love_on_type', 'post')
                ->where('love_on_id', $post->post_id)
                ->count();

            $totalUnlike = Unlikes::where('unlike_on_type', 'post')
                ->where('unlike_on_id', $post->post_id)
                ->count();


            // Count the total comments related to the post
            $totalComments = Comments::where('post_id', $post->post_id)->count();

            // Count the total replies related to all comments of the post
            $totalReplies = Replies::whereIn('comment_id', function ($query) use ($post) {
                $query->select('comment_id')->from('comments')->where('post_id', $post->post_id);
            })->count();

            // Add the values to the post object
            $post->isLove = $isLove;
            $post->isUnlike = $isUnlike;
            $post->totalLove = $totalLove;
            $post->totalUnlike = $totalUnlike;
            /*   sum of replies and comments */
            $post->total_comments = $totalComments + $totalReplies;

            return $post;
        });
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


    /* Get all users of a specific Page */
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


    /* public page */
    public function joinPage(Request $request, $pageId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input

        $pageId = cleanInput($pageId);
        // Data transaction
        DB::transaction(function () use ($userId, $pageId) {
            // Check if the group exists
            $page = Pages::where('page_id', $pageId)->first();

            if (!$page) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Group not found');
            }

            // Create a record in the UsersHasPage model
            UsersHasPages::create([
                'user_id' => $userId,
                'page_id' => $pageId
            ]);
        });

        // Handle success response
        return response()->json(['message' => 'Page joined successful'], 200);
    }

    /* public page leave */
    public function leavePage(Request $request, $pageId)
    {


        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input
        $pageId = cleanInput($pageId);
        // Data transaction
        DB::transaction(function () use ($userId, $pageId) {
            // Check if the group exists
            $page = Pages::where('page_id', $pageId)->first();

            if (!$page) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('Page not found');
            }

            // Check if the user is a member of the group
            $membership = UsersHasPages::where('user_id', $userId)->where('page_id', $pageId)->first();

            if (!$membership) {
                // Membership record does not exist
                throw new \Exception('User is not a member of the Page');
            }

            // Remove the user from the group
            UsersHasPages::where('user_id', $userId)->where('page_id', $pageId)->delete();
        });

        // Handle success response
        return response()->json(['message' => 'Successfully left the page'], 200);
    }



    // Update Group name
    public function updatePageName($pageId, Request $request)
    {


        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'name' => 'required|string|max:40',
            'pageId' => 'required|string|max:40',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $name = cleanInput($request->name);

        $pageMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->first();
        if (!$pageMember) {
            return response([
                'message' => 'You are not member of this page'
            ], 422);
        }


        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $page->update(['page_name' => $name]);
        return response()->json(['message' => 'Page name updated successfully']);
    }








    //Update group details
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

        $pageMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->first();
        if (!$pageMember) {
            return response([
                'message' => 'You are not member of this [page]'
            ], 422);
        }


        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $page->update(['page_details' => $details]);
        return response()->json(['message' => 'Group details updated successfully']);
    }




    // Update Group location
    public function updatePageLocation($pageId, Request $request)
    {


        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'location' => 'required|string|max:100',
            'pageId' => 'required|string|max:40',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $location = cleanInput($request->location);

        $pageMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->first();
        if (!$pageMember) {
            return response([
                'message' => 'You are not member of this page'
            ], 422);
        }


        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $page->update(['location' => $location]);
        return response()->json(['message' => 'Page location updated successfully']);
    }


    // Update Group location
    public function updatePagePhone($pageId, Request $request)
    {

        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'phone' => 'required|string|max:30',
            'pageId' => 'required|string|max:40',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $phone = cleanInput($request->phone);

        $pageMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->first();
        if (!$pageMember) {
            return response([
                'message' => 'You are not member of this page'
            ], 422);
        }


        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $page->update(['phone' => $phone]);
        return response()->json(['message' => 'Page phone updated successfully']);
    }


    // Update Group location
    public function updatePageEmail($pageId, Request $request)
    {

        $request->merge(['pageId' => $pageId]);
        $this->validate($request, [
            'email' => 'required|email|max:50',
            'pageId' => 'required|string|max:40',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $pageId = cleanInput($pageId);
        $email = cleanInput($request->email);

        $pageMember = UsersHasPages::where('user_id', $userId)
            ->where('page_id', $pageId)
            ->first();
        if (!$pageMember) {
            return response([
                'message' => 'You are not member of this page'
            ], 422);
        }


        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }
        $page->update(['email' => $email]);
        return response()->json(['message' => 'Page email updated successfully']);
    }


    /* gettAllGroupMemberManage */
    public function gettAllPageMemberManage(Request $request)
    {
        // Clean and get the page ID from the request query
        $pageId = cleanInput($request->query('id'));

        // Find the page using the page_id
        $pages = Pages::where('page_id', $pageId)->first();

        // Check if page exists
        if (!$pages) {
            return response()->json(['error' => 'Page not found.'], 404);
        }

        // Group creator ID
        $pagesCreatorId = $pages->page_creator;

        // Define the number of members per page
        $perPage = $request->query('per_page', 10);
        $page = $request->query('page', 1);

        // Get the list of admin IDs for the group
        $adminIds = explode(',', $pages->page_admins);

        // Retrieve all users associated with this group using pagination
        $users = $pages->user()->paginate($perPage, ['*'], 'page', $page);

        // Get the authenticated user's ID
        $authUserId = auth()->user()->user_id;
        //is auth user is isCreator
        $isAuthIsCreator = auth()->user()->user_id == $pagesCreatorId ? true : false;

        // Iterate over the paginated group members to add the isAdmin, isCreator, and isAuth fields
        $users->getCollection()->transform(function ($user) use ($adminIds, $pageId, $pagesCreatorId, $authUserId, $isAuthIsCreator) {
            return [
                'page_id' => $pageId,
                'user_id' => $user->user_id,
                'user_fname' => $user->user_fname,
                'user_lname' => $user->user_lname,
                'profile_picture' => $user->profile_picture,
                'identifier' => $user->identifier,
                'isAdmin' => in_array($user->user_id, $adminIds) ? true : false, // Set isAdmin to true if the member is an admin, false otherwise
                'isCreator' => $user->user_id == $pagesCreatorId ? true : false, // Set isCreator to true if the member is the group creator, false otherwise
                'isAuth' => $user->user_id == $authUserId ? true : false, // Set isAuth to true if the member is the authenticated user, false otherwise
                'isAuthIsCreator' => $isAuthIsCreator
            ];
        });

        // Return the group members as a JSON response, including the isAdmin, isCreator, and isAuth fields
        return response()->json($users);
    }



    public function addAdmin(Request $request, $pageId, $newMember)
    {
        $user = auth()->user();

        $pageId = cleanInput($pageId);
        $newMember = cleanInput($newMember);
        $isnewMember = User::find($newMember);
        if (!$isnewMember) {
            return response([
                'message' => 'New Member Id not founded'
            ]);
        }

        // Your existing logic for checking if the user is an admin and if the new member is valid
        $page = Pages::where('page_id', $pageId)->first();

        if ($page) {
            $admin = $page->page_admins;
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
                    if ($newmemberId->pages->contains($pageId)) {
                        $admin_list = explode(',', $admin); // Split the string into an array

                        $number_of_admins = count($admin_list);

                        if ($number_of_admins <= 30) {
                            // Update the admin column with the concatenated value
                            $newAdminValue = $admin . ',' . $newMember;
                            $page->update(['page_admins' => $newAdminValue]);

                            return response([
                                'message' => 'Admin updated successfully.'
                            ]);
                        } else {
                            return response([
                                'message' => 'There can be maximum 30 admins for page'
                            ]);
                        }
                    } else {
                        return response([
                            'message' => 'The user is not member of this page'
                        ]);
                    }
                }
            }
        } else {
            return response([
                'message' => 'Page not found'
            ]);
        }
    }



    //Both(public/private) Remove from group
    public function kickOutUser(Request $request, $pageId, $memberId)
    {
        $user = auth()->user(); // The authenticated user (the one performing the action)

        // Clean the input
        $pageId = cleanInput($pageId);
        $memberId = cleanInput($memberId);

        // Find the page
        $page = Pages::where('page_id', $pageId)->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found'], 404);
        }

        // Check if the authenticated user is an admin of the group
        $adminList = explode(',', $page->page_admins);

        if (!in_array($user->user_id, $adminList)) {
            return response()->json(['message' => 'You are not an admin of this page'], 403); // HTTP 403 Forbidden
        }

        // Check if the user being kicked is the group creator
        if ($page->page_creator === $memberId) {
            return response()->json(['message' => 'You cannot kick out the page creator'], 403);
        }

        // Ensure that only the group creator can kick out an admin
        if (in_array($memberId, $adminList) && $user->user_id !== $page->page_creator) {
            return response()->json(['message' => 'Only the page creator can kick out other admins'], 403);
        }

        // Prevent the group creator from kicking themselves out
        if ($user->user_id === $page->page_creator && $page->page_creator === $memberId) {
            return response()->json(['message' => 'You cannot remove yourself as the page creator'], 403);
        }

        // Check if the user to be kicked is a member of the group
        $userInPage = UsersHasPages::where('page_id', $pageId)
            ->where('user_id', $memberId)
            ->first();

        if (!$userInPage) {
            return response()->json(['message' => 'User is not a member of this page'], 404);
        }

        // Begin transaction
        DB::beginTransaction();

        try {
            // Remove the user from the group
            UsersHasPages::where('page_id', $pageId)
                ->where('user_id', $memberId)
                ->delete();

            // If the user is an admin, remove them from the admin list
            if (in_array($memberId, $adminList)) {
                $adminList = array_filter(explode(',', $page->page_admins)); // Filter out empty values
                $adminList = array_diff($adminList, [$memberId]); // Remove the user from the admin list
                $page->page_admins = implode(',', $adminList); // Update the admin list in the group
                $page->save();
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
}
