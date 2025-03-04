<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\Loves;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\Replies;
use App\Models\TextPosts;
use App\Models\UniqeUser;
use App\Models\Unlikes;
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
                $imageUrl =  'storage/upload/images/' . $customFileName;

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
                $imageUrl =  'storage/upload/images/' . $customFileName;

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
    public function createGroupPost(Request $request)
    {
        $user = auth()->user();

        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'text' => 'nullable|max:10000',
            'groupId' => 'required',
            'image_or_text' => 'required_without_all:image,text',
        ]);

        $groupId = cleanInput($request->groupId);
        $group = Groups::find($groupId);

        if (!$group) {
            return response()->json(['message' => "Group not found"]);
        }

        $text = cleanInput($request->text);

        // Check if the user is a member of the group
        if (!$user->groups->contains($groupId)) {
            return response()->json(['message' => 'You are not a member of this group.'], 403);
        }

        $post_id = Str::uuid();
        $postData = [
            'post_id' => $post_id,
            'author_id' => auth()->user()->user_id,
            'group_id' => $groupId,
            'post_type' => 'general',
        ];

        // Handle group audience logic
        if ($group->audience == 'private') {
            // Check if the user is an admin of the group
            if (!Str::contains($group->group_admins, $user->user_id)) {
                // The user is a member but not an admin, require post approval
                $postData['approval'] = false;
            }
        }

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
                $imageUrl =  'storage/upload/images/' . $customFileName;

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
                $imageUrl =  'storage/upload/images/' . $customFileName;

                ImagePosts::create([
                    'image_posts_id' => Str::uuid(),
                    'post_id' => $post_id,
                    'post_url' => $imageUrl,
                ]);

                return response()->json(['message' => 'Image successfully stored']);
            }
        });
    }


    // create page post
    //Create a group post
    public function createPagePost(Request $request)
    {
        $user = auth()->user();

        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'text' => 'nullable|max:10000',
            'pageId' => 'required',
            'image_or_text' => 'required_without_all:image,text',
        ]);

        $pageId = cleanInput($request->pageId);
        $page = Pages::find($pageId);

        if (!$page) {
            return response()->json(['message' => "Page not found"]);
        }

        // Check if the user is an admin of the group
        if (!Str::contains($page->page_admins, $user->user_id)) {
            return response()->json(['message' => "You are not page admin"]);
        }

        $text = cleanInput($request->text);
        $post_id = Str::uuid();
        $postData = [
            'post_id' => $post_id,
            'author_id' => auth()->user()->user_id,
            'page_id' => $pageId,
            'post_type' => 'general',
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
                $imageUrl =  'storage/upload/images/' . $customFileName;

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
                $imageUrl =  'storage/upload/images/' . $customFileName;

                ImagePosts::create([
                    'image_posts_id' => Str::uuid(),
                    'post_id' => $post_id,
                    'post_url' => $imageUrl,
                ]);

                return response()->json(['message' => 'Image successfully stored']);
            }
        });
    }

    public function createIAccountPost(Request $request)
    {
        $userId = auth()->user()->user_id;

        $this->validate($request, [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'text' => 'nullable|max:10000',
            'iChannelId' => 'required',
            'image_or_text' => 'required_without_all:image,text',
        ]);

        $iChannelId = cleanInput($request->iChannelId);

        $iaccount = IAccount::where('iaccount_id', $iChannelId)
        ->where('iaccount_creator', $userId)
        ->first();
        if (!$iaccount) {
            return response([
                'message' => 'You are not creator of this iaccount'
            ], 422);
        }


        $text = cleanInput($request->text);
        $post_id = Str::uuid();
        $postData = [
            'post_id' => $post_id,
            'author_id' => auth()->user()->user_id,
            'iaccount_id' => $iChannelId,
            'post_type' => 'general',
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
                $imageUrl =  'storage/upload/images/' . $customFileName;

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
                $imageUrl =  'storage/upload/images/' . $customFileName;

                ImagePosts::create([
                    'image_posts_id' => Str::uuid(),
                    'post_id' => $post_id,
                    'post_url' => $imageUrl,
                ]);

                return response()->json(['message' => 'Image successfully stored']);
            }
        });
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
                if ($imagepath->post_url !== $iaccount->iaccount_picture) {
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
    $perPage = $request->query('per_page', 10); // Number of items per page
    $page = $request->query('page', 1); // Current page

    // Fetch paginated posts
    $posts = Posts::where('author_id', '!=', $user->user_id)
        ->whereNull('group_id')
        ->whereNull('page_id')
        ->whereNull('iaccount_id')
        ->with(['author', 'textPost', 'imagePost']) // Eager load only necessary relations
        ->paginate($perPage, ['*'], 'page', $page);

    // Transform the post data to match the required structure
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

        // Count the total comments and replies related to the post
        $totalComments = Comments::where('post_id', $post->post_id)->count();
        $totalReplies = Replies::whereIn('comment_id', function ($query) use ($post) {
            $query->select('comment_id')->from('comments')->where('post_id', $post->post_id);
        })->count();

        // Transform the post to match the required structure
        return [
            'author' => [
                'identifier' => $post->author->identifier,
                'profile_picture' => $post->author->profile_picture,
                'user_fname' => $post->author->user_fname,
                'user_lname' => $post->author->user_lname,
                'user_id' => $post->author->user_id,
            ],
            'created_at' => $post->created_at,
            'image_post' => $post->imagePost ? [
                'post_url' => $post->imagePost->post_url,
            ] : null,
            'isLove' => $isLove,
            'isUnlike' => $isUnlike,
            'post_id' => $post->post_id,
            'text_post' => $post->textPost ? [
                'post_text' => $post->textPost->post_text,
            ] : null,
            'totalLove' => $totalLove,
            'totalUnlike' => $totalUnlike,
            'total_comments' => $totalComments + $totalReplies,
        ];
    });

    // Return paginated posts as JSON
    return response()->json($posts);
}

    
    




}
