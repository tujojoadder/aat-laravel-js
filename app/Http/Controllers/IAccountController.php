<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\TextPosts;
use App\Models\UniqeUser;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
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


        $this->validate($request, [
            'iaccount_name' => 'required|string|max:50'
        ]);


        $iaccountName = cleanInput($request->iaccount_name);

        $group_nameidentifierBase = preg_replace('/[^\p{L}0-9]+/u', '', $iaccountName);
        $identifierBase = strtolower($group_nameidentifierBase);
        // Generate the identifier
        $identifier = $this->generateIdentifier($identifierBase);

        IAccount::create([
            'iaccount_id' => $iaccountId,
            'identifier' => $identifier,
            'iaccount_name' => $iaccountName,
            'iaccount_creator' => $userId,
            'iaccount_picture' => 'storage/defaultProfile/iaccount.jpg',
            'iaccount_cover' => 'storage/defaultCover/iaccount.jpg',

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
            $post= Posts::where('iaccount_id', $iaccountId)->first();
            if ($post) {
                $author=User::find($post->author_id);
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
            'user_id' =>$author->user_id,
            'full_name' => $author->user_fname . " " . $author->user_lname, // Assuming there's a full_name attribute for the author
            // Include other author details as needed
        ];

        // Return the formatted posts data
        return response()->json(['iAccount' => $iaccount->iaccount_id, 'author' =>$author , 'posts' => $formattedPosts]);
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



}
