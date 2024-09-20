<?php

namespace App\Http\Controllers;

use App\Models\FriendList;
use App\Models\FriendRequest;
use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\TextPosts;
use App\Models\UniqeUser;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\UsersHasIAccounts;
use App\Models\UsersHasPages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IAccountController extends Controller
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
    public function createIAccount(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $iaccountId = Str::uuid();

        // Check if the user already has 3 iAccounts
        $iaccountCount = IAccount::where('iaccount_creator', $userId)->count();

        if ($iaccountCount >= 3) {
            return response()->json([
                'message' => 'You have reached the maximum limit of 3 iAccounts.'
            ], 403); // 403 Forbidden status code
        }

        // Validation rules with a custom message for exceeding the 30 character limit
        $this->validate($request, [
            'iaccount_name' => 'required|string|max:30'
        ], [
            'iaccount_name.max' => 'The iAccount name may not be greater than 30 characters.'
        ]);

        $iaccountName = cleanInput($request->iaccount_name);

        $i_Chanel_nameidentifierBase = preg_replace('/[^\p{L}0-9]+/u', '', $iaccountName);
        $identifierBase = strtolower($i_Chanel_nameidentifierBase);

        // Generate the identifier
        $identifier = $this->generateIdentifier($identifierBase);

        // Create the iAccount
        IAccount::create([
            'iaccount_id' => $iaccountId,
            'identifier' => $identifier,
            'iaccount_name' => $iaccountName,
            'iaccount_creator' => $userId,
            'iaccount_picture' => 'http://127.0.0.1:8000/storage/mprofile_picture/iaccount.jpg',
            'iaccount_cover' => 'http://127.0.0.1:8000/storage/cover_photo/iaccount.jpg',
        ]);

        return response()->json([
            'message' => 'IAccount is created successfully'
        ]);
    }





    //Retrive Iaccount Followers
    public function getIaccountFollower(Request $request, $iaccountId)
    {
        // Find the IAccount by its ID
        $iaccount = IAccount::find($iaccountId);

        // Check if the IAccount was found
        if (!$iaccount) {
            return response()->json(['message' => 'IAccount not found'], 404);
        }

        // Retrieve the followers for the IAccount
        $followers = $iaccount->followers;

        // Extract the follower IDs from the collection
        $followerIds = $followers->pluck('follower_id')->toArray();

        // Count the number of followers
        $followerCount = count($followerIds);

        // Return the follower IDs and the follower count
        return [
            'follower_ids' => $followerIds,
            'follower_count' => $followerCount
        ];
    }


    //Retrive how many iaccount user following

    public function getfollowingIAccounts(Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;

        // Retrieve the user following iaccount
        $followingIAccounts = $user->iAccountFollowers;

        // Extract the follower IDs from the collection
        $followingIAccountsIds = $followingIAccounts->pluck('iaccount_id')->toArray();

        // Count the number of followers
        $followingIAccountsCounts = count($followingIAccountsIds);

        // Return the follower IDs and the follower count
        return [
            'following_iaccounts' => $followingIAccountsIds,
            'following_iaccounts_count' => $followingIAccountsCounts
        ];
    }



    // create IAccount Post


    public function createIAccountPost(Request $request, $iaccountId)
    {

        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['iaccountId' => $iaccountId]);
        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text' => 'nullable|max:10000|string',
            'iaccountId' => 'required|string|max:50',
            'image_or_text' => 'required_without_all:image,text',
        ]);


        $text = cleanInput($request->text);
        $iaccountId = cleanInput($iaccountId);
        $isIAccount = IAccount::find($iaccountId);
        if (!$isIAccount) {
            return response()->json(['message' => 'IAccount not found']);
        }
        if ($isIAccount->iaccount_creator != $userId) {
            return response()->json(['message' => 'You are not the owner of this IAccount']);
        }

        $post_id = Str::uuid();

        $postData = [
            'post_id' => $post_id,
            'author_id' => $userId,
            'iaccount_id' => $iaccountId,
            'post_type' => 'iaccount',

        ];

        // Create the main post
        $post = Posts::create($postData);

        // Handle both text and image
        if ($request->filled('text') && $request->hasFile('image')) {
            // Handle text
            TextPosts::create([
                'text_post_id' => Str::uuid(),
                'post_id' => $post_id,
                'post_text' => $text,
            ]);

            // Handle image
            $customFileName = $request->file('image')->hashName();
            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);
            $imageUrl = Storage::url($path);
            ImagePosts::create([
                'image_posts_id' => Str::uuid(),
                'post_id' => $post_id,
                'post_url' => $imageUrl,
            ]);

            $message = 'Text and Image successfully stored';
        } elseif ($request->filled('text') && !$request->hasFile('image')) {

            TextPosts::create([
                'post_id' => $post_id,
                'post_text' => $text,
                'text_post_id' => Str::uuid(),
            ]);
            $message = 'Text successfully stored';
        } elseif (!$request->filled('text') && $request->hasFile('image')) {

            $customFileName = $request->file('image')->hashName();
            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);
            $imageUrl = Storage::url($path);
            ImagePosts::create([
                'image_posts_id' => Str::uuid(),
                'post_id' => $post_id,
                'post_url' => $imageUrl,
            ]);
            $message = 'Image successfully stored';
        }
        return response()->json(['message' => $message]);
    }



    //Retrive all post from Spacific IAccount
    public function getIAccountPosts($iaccountId)
    {


        $user = auth()->user();
        $userId = $user->user_id;


        $iaccount = IAccount::find($iaccountId);

        // Check if the group exists
        if (!$iaccount) {
            return response()->json(['message' => 'IAccount not found'], 404);
        }

        $iaccountId = cleanInput($iaccountId);
        // Retrieve all posts for the group with their respective text or image posts and authors
        $posts = Posts::where('iaccount_id', $iaccountId)
            ->with(['textPost', 'imagePost', 'author'])
            ->orderBy('created_at', 'desc') // Order by creation date in descending order
            ->get();
        $post = Posts::where('iaccount_id', $iaccountId)->first();
        if ($post) {
            $author = User::find($post->author_id);
        }

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

            ];

            // Add the formatted post to the array
            $formattedPosts[] = $formattedPost;
        }
        $author = [
            'user_id' => $author->user_id,
            'full_name' => $author->user_fname . " " . $author->user_lname, // Assuming there's a full_name attribute for the author
            // Include other author details as needed
        ];

        // Return the formatted posts data
        return response()->json(['iAccount' => $iaccount->iaccount_id, 'author' => $author, 'posts' => $formattedPosts]);
    }


    //getFollowingIAccountsPosts

    public function getFollowingIAccountsPosts(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Retrieve the user's following iaccounts
        $followingIAccounts = $user->iAccountFollowers;

        // Extract the iaccount IDs from the collection
        $followingIAccountsIds = $followingIAccounts->pluck('iaccount_id')->toArray();

        // Retrieve all posts from the following iaccounts
        $posts = Posts::whereIn('iaccount_id', $followingIAccountsIds)
            ->with(['textPost', 'imagePost', 'author', 'iaccount'])
            ->get();

        // Shuffle the posts randomly
        $shuffledPosts = $posts->shuffle();

        // Format the shuffled posts data
        $formattedPosts = [];
        foreach ($shuffledPosts as $post) {
            // Format the post data
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

            // Format the post data including author and iaccount details
            $formattedPost = [
                'post_id' => $post->post_id,
                'post_content' => $postContent,
                'author' => [
                    'user_id' => $post->author->user_id,
                    'full_name' => $post->author->user_fname . ' ' . $post->author->user_lname,
                    // Include other author details as needed
                ],
                'iaccount' => [
                    'iaccount_id' => $post->iaccount->iaccount_id,
                    'profile_url' => $post->iaccount->iaccount_picture,
                    // Include other iaccount details as needed
                ],
                // Include other post details as needed
            ];

            $formattedPosts[] = $formattedPost;
        }

        // Return the shuffled posts data
        return response()->json(['posts' => $formattedPosts]);
    }


    //get All IAccount Posts
    public function getAllAccountsPosts(Request $request)
    {

        $posts = Posts::with(['textPost', 'imagePost', 'author', 'iaccount'])->get();

        // Shuffle the posts randomly
        $shuffledPosts = $posts->shuffle();

        // Format the shuffled posts data
        $formattedPosts = [];
        foreach ($shuffledPosts as $post) {
            // Format the post data
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

            // Format the post data including author and iaccount details
            $formattedPost = [
                'post_id' => $post->post_id,
                'post_content' => $postContent,
                'author' => [
                    'user_id' => $post->author->user_id,
                    'full_name' => $post->author->user_fname . ' ' . $post->author->user_lname,
                    // Include other author details as needed
                ],
                'iaccount' => [
                    'iaccount_id' => $post->iaccount->iaccount_id,
                    'profile_url' => $post->iaccount->iaccount_picture,
                    // Include other iaccount details as needed
                ],
                // Include other post details as needed
            ];

            $formattedPosts[] = $formattedPost;
        }

        // Return the shuffled posts data
        return response()->json(['posts' => $formattedPosts]);
    }


    public function index()
    {
        return view('lang');
    }
    public function change(Request $request)
    {
        App::setLocale($request->lang);
        session()->put('locale', $request->lang);

        return redirect()->back();
    }





    /* get posts where group_id is not null */
    public function getRandomIaccountPosts(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->query('per_page', 5);
        $iaccount = $request->query('page', 1);

        // Fetch posts where group_id is not null
        $posts = Posts::whereNotNull('iaccount_id')
            ->with([
                'iaccount:iaccount_id,identifier,iaccount_name,iaccount_picture',
                'textPost',
                'imagePost',
                'group:group_id,group_name,group_picture' // Only select group_id and group_picture from the group table
            ])
            ->paginate($perPage, ['*'], 'page', $iaccount);

        return response()->json($posts);
    }


    public function getYourIaccounts(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Define pagination
        $perPage = $request->input('per_page', 5);
        $iaccount = $request->input('page', 1);

        // Fetch iAccounts created by the user with pagination
        $iAccounts = IAccount::where('iaccount_creator', $user->user_id)
            ->select('iaccount_id', 'iaccount_name', 'iaccount_picture', 'iaccount_cover', 'identifier')
            ->paginate($perPage, ['*'], 'page', $iaccount);

        // Return the paginated response
        return response()->json([
            'data' => $iAccounts->items(), // Paginated items
            'current_page' => $iAccounts->currentPage(),
            'per_page' => $iAccounts->perPage(),
            'total' => $iAccounts->total(),
            'total_pages' => $iAccounts->lastPage(),
        ]);
    }



    public function getLikedIaccount(Request $request)
    {
        // Get the authenticated user
        $user = auth()->user();

        // Define how many items per page (or get it from the request)
        $perPage = $request->input('per_page', 8); // Default to 2 items per page
        $iaccount = $request->input('page', 1); // Default to page 1

        // Retrieve the iAccount IDs the user is linked to from 'users_has_iaccount' table
        $userIAccountIds = UsersHasIAccounts::where('user_id', $user->user_id)
            ->pluck('iaccount_id'); // Get just the IDs

        // Paginate the corresponding iAccounts from the 'iaccounts' table
        $iAccounts = IAccount::whereIn('iaccount_id', $userIAccountIds)
            ->paginate($perPage, ['*'], 'page', $iaccount);

        // Return the paginated response
        return response()->json([
            'data' => $iAccounts->items(),          // Paginated items
            'current_page' => $iAccounts->currentPage(),
            'per_page' => $iAccounts->perPage(),
            'total' => $iAccounts->total(),
            'total_pages' => $iAccounts->lastPage(),
        ]);
    }




    public function iaccountDetails(Request $request)
    {
        // Get Authenticated user
        $user = auth()->user();
        $userId = $user->user_id;

        // Find the page by ID, selecting the fields except 'page_admins'
        $iaccount = IAccount::select(
            'iaccount_id',
            'iaccount_name',
            'identifier',
            'iaccount_picture',
            'iaccount_cover',
            'iaccount_creator',

        )
            ->where('iaccount_id', $request->id)->firstOrFail();

        // Initialize variables
        $isCreator = false;
        $joinStatus = false;

        // Check if the page exists
        if ($iaccount) {

            // Check if the authenticated user is an creator of the page
            $isCreator = ($iaccount->iaccount_creator === $user->user_id);

            // Check if the authenticated user is a member of the page
            $joinStatus = UsersHasIAccounts::where('user_id', $userId)
                ->where('iaccount_id', $request->id)
                ->exists();
        }

        // Convert the page data to an array and add custom flags
        $iaccountData = $iaccount ? $iaccount->toArray() : [];
        $iaccountData['isCreator'] = $isCreator; // Add isCreator flag
        $iaccountData['joinStatus'] = $joinStatus; // Add joinStatus flag

        // Return the response without 'page_admins'
        return response()->json(['data' => $iaccountData]);
    }





    /* get for specific group posts  */
    public function getSpecificIaccountPosts(Request $request)
    {
        $user = auth()->user();
        $specificIaccountId = cleanInput($request->query('id'));
        // Debug the value of $specificIaccountId
        $perPage = $request->query('per_page', 5);
        $page = $request->query('page', 1);

        $posts = Posts::where('iaccount_id', $specificIaccountId)
            ->with(['iaccount', 'textPost', 'imagePost'])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($posts);
    }


    /* get for specific group all photo */
    public function getSpecificIAccountPhotos(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificIAccountId = cleanInput($request->query('id'));

        // Set default pagination values, with the option to customize via query parameters
        $perPage = $request->query('per_page', 6); // default to 10 per page
        $page = $request->query('page', 1);

        // Query for the posts with associated image posts for the specific user, paginate the results
        $posts = Posts::where('iaccount_id', $specificIAccountId)
            ->with('imagePost') // Eager load the image posts relationship
            ->whereHas('imagePost') // Ensure we only get posts with associated image posts
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated result as JSON
        return response()->json($posts);
    }

    public function getIAccountFollowrDetails(Request $request)
    {
        // Clean the input and get the iAccount ID from the request query
        $iAccountId = cleanInput($request->query('id'));

        // Define the number of friends per page
        $perPage = 10;

        // Get the authenticated user's ID
        $authUserId = auth()->id();

        // Retrieve the friend list for the authenticated user
        $authFriendList = FriendList::where('user_id', $authUserId)->first();

        // Retrieve all users who are associated with the specified iAccount ID
        $iAccountUsers = UsersHasIAccounts::where('iaccount_id', $iAccountId)->pluck('user_id');

        if ($iAccountUsers->isNotEmpty()) {
            // Retrieve the authenticated user's friend IDs if available
            $authFriendIdsArray = [];
            if ($authFriendList && !empty($authFriendList->user_friends_ids)) {
                $authFriendIdsArray = explode(',', $authFriendList->user_friends_ids);
            }

            // Retrieve details of users who have the iAccount and paginate them
            $usersWithIAccount = User::whereIn('user_id', $iAccountUsers)
                ->select('user_id', 'user_fname', 'user_lname', 'profile_picture', 'identifier')
                ->paginate($perPage);

            // Iterate over the paginated users to add the is_friend and friend_request_sent fields
            $usersWithIAccount->getCollection()->transform(function ($user) use ($authFriendIdsArray, $authUserId) {
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

            // Return the user details as a JSON response, including the is_friend and friend_request_sent fields
            return response()->json($usersWithIAccount);
        } else {
            // Handle the case where no users are associated with the given iAccount
            return response()->json(['message' => 'No users found for the specified iAccount.'], 404);
        }
    }



    public function joinIAccount(Request $request, $iChannelId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input

        $iChannelId = cleanInput($iChannelId);
        // Data transaction
        DB::transaction(function () use ($userId, $iChannelId) {
            // Check if the group exists
            $iaccount = IAccount::where('iaccount_id', $iChannelId)->first();

            if (!$iaccount) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('IChannel not found');
            }

            // Create a record in the UsersHasGroups model
            UsersHasIAccounts::create([
                'user_id' => $userId,
                'iaccount_id' => $iChannelId
            ]);
        });

        // Handle success response
        return response()->json(['message' => 'join successful'], 200);
    }


    public function leaveIAccount(Request $request, $iChannelId)
    {
        // Get the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        // Clean the input
        $iChannelId = cleanInput($iChannelId);
        // Data transaction
        DB::transaction(function () use ($userId, $iChannelId) {
            // Check if the group exists
            $iaccount = IAccount::where('iaccount_id', $iChannelId)->first();

            if (!$iaccount) {
                // Group does not exist, throw an exception to trigger transaction rollback
                throw new \Exception('IChannel not found');
            }

            // Check if the user is a member of the group
            $membership = UsersHasIAccounts::where('user_id', $userId)->where('iaccount_id', $iChannelId)->first();

            if (!$membership) {
                // Membership record does not exist
                throw new \Exception('User is not a member of the iChannel');
            }

            // Remove the user from the group
            UsersHasIAccounts::where('user_id', $userId)->where('iaccount_id', $iChannelId)->delete();
        });

        // Handle success response
        return response()->json(['message' => 'Successfully left the iChannel'], 200);
    }



    public function updateIAccountName($iaccountId, Request $request)
    {

        $request->merge(['iChannelId' => $iaccountId]);
        $this->validate($request, [
            'name' => 'required|string|max:40',
            'iChannelId' => 'required|string|max:40',
        ]);
        $user = auth()->user();
        $userId = $user->user_id;
        $iaccountId = cleanInput($iaccountId);
        $name = cleanInput($request->name);

        $iaccount = IAccount::where('iaccount_creator', $userId)->first();
        if (!$iaccount) {
            return response([
                'message' => 'You are not creator of this iaccount'
            ], 422);
        }

        $iaccount->update(['iaccount_name' => $name]);
        return response()->json(['message' => 'IAccount name updated successfully']);
    }



}
