<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\TextPosts;
use App\Models\UniqeUser;
use App\Models\User;
use App\Models\UsersHasGroups;
use App\Models\UsersHasPages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use PHPUnit\TextUI\XmlConfiguration\Group;

class PostsController extends Controller
{
        //Create a user post
        public function createUserPost(Request $request)
        {
            $user = auth()->user();
            $userId = $user->user_id;
    
            $this->validate($request, [
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
                'text' => 'nullable|max:20000|string',
                'image_or_text' => 'required_without_all:image,text',
                'audience' => 'required|in:public,private,only_me', // Validation for audience
            ]);
    
            $text = cleanInput($request->text);
            $audience = cleanInput($request->audience);
    
            $post_id = Str::uuid();
            $postData = [
                'post_id' => $post_id,
                'author_id' => $userId,
                'post_type' => 'general',
                'timeline_ids' => $userId,
                'audience' => $audience
            ];
    
            return DB::transaction(function () use ($request, $post_id, $text, $postData) {
                // Create the main post
                Posts::create($postData);
    
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
    
                    // Move file to public directory directly
                    $path = $request->file('image')->move(public_path('storage/upload/images/'), $customFileName);
                    $imageUrl = asset('storage/upload/images/' . $customFileName); // Generate a public URL
    
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $post_id,
                        'post_url' => $imageUrl,
                    ]);
    
                    return response()->json(['message' => 'Text and Image successfully stored']);
                } elseif ($request->filled('text') && !$request->hasFile('image')) {
                    TextPosts::create([
                        'text_post_id' => Str::uuid(),
                        'post_id' => $post_id,
                        'post_text' => $text,
                    ]);
                    return response()->json(['message' => 'Text successfully stored']);
                } elseif (!$request->filled('text') && $request->hasFile('image')) {
                    $customFileName = $request->file('image')->hashName();
    
                    // Move file to public directory directly
                    $path = $request->file('image')->move(public_path('storage/upload/images/'), $customFileName);
                    $imageUrl = asset('storage/upload/images/' . $customFileName); // Generate a public URL
    
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $post_id,
                        'post_url' => $imageUrl,
                    ]);
    
                    return response()->json(['message' => 'Image successfully stored']);
                }
            });
        }
    
    //Create a group post
    public function createGroupPost(Request $request, $groupId)
    {
        $request->merge(['groupId' => $groupId]);

        $user = auth()->user();

        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text' => 'nullable|max:10000',
            'groupId' => 'required',
            'image_or_text' => 'required_without_all:image,text',
        ]);

        $groupId = cleanInput($groupId);
        $group = Groups::find($groupId);
        if (!$group) {
            return response()->json(['message' => "Group not founded"]);
        }
        $text = cleanInput($request->text);

        // Check if the user is a member of the group
        if (!$user->groups->contains($groupId)) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        } else {
            $group = Groups::where('group_id', $groupId)->first();

            if (!$group) {
                return response()->json(['message' => 'Group not found.'], 404);
            }

            $post_id = Str::uuid();
            $postData = [
                'post_id' => $post_id,
                'author_id' => auth()->user()->user_id,
                'group_id' => $groupId,
                'audience' => $group->audience,
                'post_type' => 'general'
            ];

            // Create the main post
            $post = Posts::create($postData);

            // Handle both text and image
            if ($request->filled('text') && $request->hasFile('image')) {
                // Handle text
                TextPosts::create([
                    'text_post_id' => Str::uuid(),
                    'post_id' => $post_id,
                    'post_text' => $request->text,
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
                    'post_text' => $request->text,
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
    }

    // create page post
    //Create a group post
    public function createPagePost(Request $request, $pageId)
    {
        $request->merge(['pageId' => $pageId]);

        $user = auth()->user();
        $userId = $user->user_id;

        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'text' => 'nullable|max:10000|string',
            'pageId' => 'required|max:50',
            'image_or_text' => 'required_without_all:image,text',
        ]);

        $pageId = cleanInput($pageId);
        $text = cleanInput($request->text);
        $page = Pages::find($pageId);
        if (!$page) {
            return response()->json(['message' => 'Page not found'], 403);
        }
        $admin = $page->page_admins;
        if (!Str::contains($admin, $userId)) {
            return response([
                'message' => 'You are not admin'
            ]);
        }


        $post_id = Str::uuid();
        $postData = [
            'post_id' => $post_id,
            'author_id' => $userId,
            'page_id' => $pageId,
            'post_type' => 'general'
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


    // if auth want to delete his user post then it have to author_user=auth
    //if you want to delete group post then you have to member of the group
    //if you want to delete page post then you have to admin of the page
    public function destroy(Request $request, $postId)
    {

        $post = Posts::where('post_id', $postId)->first();
        if (!$post) {
            return response()->json(['message' => 'Post not founded']);
        }
        $message = ''; // Initialize $message variable
        $request->merge(['postId' => $postId]);
        $user = auth()->user();
        $userId = $user->user_id;

        // Validate request data
        $this->validate($request, [
            'postId' => 'required|string|max:50',
        ]);


        // Find the post by ID
        $imagepath = ImagePosts::where('post_id', $postId)->first();
        $group = Groups::where('group_id', $post->group_id)->first();
        // Check if the post exists and the authenticated user owns it
        $page = Pages::where('page_id', $post->page_id)->first();
        // Check if the post exists and the authenticated user owns it
        $iaccount = IAccount::where('iaccount_id', $post->iaccount_id)->first();

        // is user member of the group
        if ($group) {
            $isMember = UsersHasGroups::where('user_id', $userId)
                ->where('group_id', $post->group_id)
                ->first();

            if (!$isMember) {
                return response()->json(['message' => 'You are not group member']);
            }
        }

        if ($page) {
            $admin = $page->page_admins;
            if (!Str::contains($admin, $userId)) {
                return response([
                    'message' => 'You are not admin'
                ]);
            }
        }

        if (!$iaccount) {
            return response([
                'message' => 'IAccount not founded'
            ]);
        }
        if ($iaccount->iaccount_creator != $userId) {
            return response([
                'message' => 'You are not the owner of this IAccount'
            ]);
        }

        if ($post) {
            // Check the post type
            if ($post->post_type === 'user_profile' && $post->author_id === $user->user_id) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the current profile picture then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $user->profile_picture) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "User Post deleted sucessfully";
                $statusCode = 404; // Not Found.
            } elseif ($post->post_type === 'user_cover' && $post->author_id === $user->user_id) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the user cover photo then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $user->cover_photo) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "User Cover Post deleted sucessfully";
                $statusCode = 404; // Not Found.
            } elseif ($post->post_type === 'group_profile' && $user->groups->contains($post->group_id)) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the user cover photo then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $group->group_picture) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "Group picture post deleted successfully.";
                $statusCode = 200; // OK

            } elseif ($post->post_type === 'group_cover' && $user->groups->contains($post->group_id)) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the user cover photo then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $group->group_cover) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "Group Cover picture post deleted successfully.";
                $statusCode = 200; // OK

            } elseif ($post->post_type === 'page_profile') {

                // User is admin of the page, proceed with deletion
                $post->delete();
                if ($imagepath && $imagepath->post_url !== $page->page_picture) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "Page Profile deleted successfully.";
                $statusCode = 200; // OK

            } elseif ($post->post_type === 'page_cover') {

                // User is admin of the page, proceed with deletion
                $post->delete();
                if ($imagepath && $imagepath->post_url !== $page->page_cover) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
                $message = "Page Cover deleted successfully.";
                $statusCode = 200; // OK

            } elseif ($post->post_type === 'general' && !$group && !$page) { //user post/islamic
                // Check if the current user is the author of the post
                if ($post->author_id !== $userId) {
                    // Set message indicating that the user is not the author
                    $message = "You are not the author of this post.";
                    $statusCode = 403; // Forbidden
                } else {
                    // User is the author of the post, proceed with deletion
                    $post->delete();
                    // Extract the file name from the URL (only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                    // Set the success message and status code
                    $message = "Post deleted successfully.";
                    $statusCode = 200; // OK
                }
            } elseif ($iaccount  && $post->post_type === 'iaccount_profile') { //user post/islamic
                // Check if the current user is the author of the post
               
                    // User is the author of the post, proceed with deletion
                    $post->delete();
                    if ( $imagepath->post_url !== $iaccount->iaccount_picture) {
                     // Extract the file name from the URL (only the imagename.jpg/png etc)
                     $fileName = basename($imagepath->post_url);
                     // Delete the physical file from storage
                     Storage::delete('public/upload/images/' . $fileName);
                    }                
                    // Set the success message and status code
                    $message = "Post deleted successfully.";
                    $statusCode = 200; // OK

               
            } else {
                $post->delete();
                // Extract the file name from the URL(only the imagename.jpg/png etc)
                $fileName = basename($imagepath->post_url);
                // Delete the physical file from storage
                Storage::delete('public/upload/images/' . $fileName);
                $message = "post deleted successfully.";
                $statusCode = 200; // OK
            }
        } else {
            // Post not found or unauthorized access
            $message = "Post not found ";
            $statusCode = 404; // Not Found
        }
        // Return response with appropriate status code
        return response()->json(['message' => $message]);
    }




/* get for home feed  */
    public function getPosts(Request $request)
    {
        $user = auth()->user();
    
        // Get pagination parameters from the request, with default values
        $perPage = $request->query('per_page',5); // Number of items per page
        $page = $request->query('page', 1); // Current page
    
        // Fetch paginated posts
        $posts = Posts::where('author_id', $user->user_id)
                      ->with(['author', 'textPost', 'imagePost'])
                      ->paginate($perPage, ['*'], 'page', $page);
    
        // Return paginated posts as JSON
        return response()->json($posts);
    }








}
