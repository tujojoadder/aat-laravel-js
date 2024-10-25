<?php

namespace App\Http\Controllers;

use App\Models\BluetikPost;
use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\MprofilePicture;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use App\Models\UsersHasGroups;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Import the Rule class
use Illuminate\Support\Facades\Storage;

class BluetickUserController extends Controller
{
    public function uploadprofilepicture(Request $request)
    {
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me'
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik and is male
        if ($user->blueticks) {
            // Check if the user has already uploaded 3 profile pictures in the last 30 days
            $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                ->where('post_type', 'profile_picture')
                ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                ->count();

            if ($uploadsLast30Days < 5) {
                // Start a database transaction
                DB::transaction(function () use ($request, $user) {
                    //Delete the old profile physical file if that has no post
                    $oldProfilePost = ImagePosts::where('post_url', $user->profile_picture)->first();

                    // Check if the old profile post exists
                    if (!$oldProfilePost) {
                        // Extract the file name from the profile picture URL (only the image name with extension)
                        $fileName = basename($user->profile_picture);
                        // Delete the physical file from storage
                        Storage::delete('public/upload/images/' . $fileName);
                    }

                    // Generate a UUID for the main post
                    $postId = Str::uuid();

                    // Create post data
                    $postData = [
                        'post_id' => $postId,
                        'author_id' => $user->user_id,
                        'timeline_ids' => $user->user_id, // Because profile pictures cannot be tagged
                        'visibility' => cleanInput($request->visibility),
                        'post_type' => 'profile_picture'
                    ];

                    // Create the main post
                    $post = Posts::create($postData);

                    // Generate a unique filename for the image
                    $customFileName = $request->file('image')->hashName();

                    // Store the image
                    $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                    // Generate a URL for the stored image
                    $imageUrl = Storage::url($path);

                    // Create the image post
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $postId, // Use the same post_id generated for the main post
                        'post_url' => $imageUrl,
                        'author_id' => $user->user_id
                    ]);
                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $user->user_id,
                        'post_type' => 'profile_picture',
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                    ]);

                    // Update profile picture on user table
                    $user->update(['profile_picture' => $imageUrl]);
                });

                // Return success response
                return response()->json(['message' => 'Image successfully uploaded']);
            } else {
                // Return error response if user has already uploaded 3 times this month
                return response()->json(['message' => 'You have reached the maximum upload limit for this month'], 400);
            }
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }



    // Delete profile update post of male(we will not delete the physical image becase we need the profile picture of user )
    public function destroyppicturePost(Request $request)
    {
        $user = auth()->user();
        // Validate request data
        $this->validate($request, [
            'post_id' => 'required|string',
        ]);
        // Find the post by ID
        $post = Posts::where('post_id', $request->post_id)->first();
        if (!$post) {
            return response()->json(['message' => 'Post not founded']);
        }
        $imagepath = ImagePosts::where('post_id', $request->post_id)->first();
        // Check if the post exists and the authenticated user owns it
        if ($post && $post->author_id === $user->user_id) {
            // Delete the post
            $post->delete();
            // if Deleting picture are not current profile picture then delete the physical picture
            if ($imagepath && $imagepath->post_url !== $user->profile_picture) {
                // Extract the file name from the URL(only the imagename.jpg/png etc)
                $fileName = basename($imagepath->post_url);

                // Delete the physical file from storage
                Storage::delete('public/upload/images/' . $fileName);
            }

            $message = "Post deleted successfully.";
            $statusCode = 200; // OK
        } else {
            // Post not found or unauthorized access
            $message = "Post not found or unauthorized access.";
            $statusCode = 404; // Not Found
        }
        // Return response with appropriate status code
        return response()->json(['message' => $message], $statusCode);
    }


    //Update user Identifire
    public function updateUserIdentifier(Request $request)
    {
        $user = auth()->user();
        if ($user->blueticks) {

            $this->validate($request, [
                'identifier' => 'required|string|max:50',
            ]);

            $userId = $user->user_id;
            $identifiercln = cleanInput($request->identifier);
            $identifierremove = preg_replace('/[^\p{L}0-9]+/u', '', $identifiercln);
            $identifierBase = strtolower($identifierremove);
            // Check if the identifier exists in any of the tables
            if (
                User::where('identifier', $identifierBase)->exists() ||
                Groups::where('identifier', $identifierBase)->exists() ||
                Pages::where('identifier', $identifierBase)->exists()
            ) {
                // Identifier already exists
                return response()->json(['message' => 'The identifier has taken,Try another'], 422);
            }


            $user->update(['identifier' => $identifierBase]);
            return response()->json(['message' => 'User Identifier updated successfully']);
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }
    //Group Identifire

    public function updateGroupIdentifier($groupId, Request $request)
    {
        $user = auth()->user();
        if ($user->blueticks) {
            $request->merge(['groupId' => $groupId]);
            $this->validate($request, [
                'identifier' => 'required|string|max:50',
                'groupId' => 'required',
            ]);

            $userId = $user->user_id;
            $groupId = cleanInput($groupId);
            $identifiercln = cleanInput($request->identifier);
            $identifierremove = preg_replace('/[^\p{L}0-9]+/u', '', $identifiercln);
            $identifierBase = strtolower($identifierremove);

            if (
                User::where('identifier', $identifierBase)->exists() ||
                Groups::where('identifier', $identifierBase)->exists() ||
                Pages::where('identifier', $identifierBase)->exists()
            ) {
                // Identifier already exists
                return response()->json(['message' => 'The identifier is already in use.'], 422);
            }

            $group = Groups::where('group_id', $groupId)->first();

            if ($group) {
                $admin = $group->group_admins;
                if (!Str::contains($admin, $user->user_id)) {
                    return response([
                        'message' => 'You are not admin'
                    ]);
                }
            } else {
                return response([
                    'message' => 'No group founded'
                ]);
            }

            $group->update(['identifier' => $identifierBase]);
            return response()->json(['message' => 'Group Identifier updated successfully']);
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }

    //Update Page Identifire
    public function updatePageIdentifier($pageId, Request $request)
    {
        $user = auth()->user();
        if ($user->blueticks) {

            $request->merge(['pageId' => $pageId]);
            $this->validate($request, [
                'identifier' => 'required|string|max:50',
                'pageId' => 'required',
            ]);

            $userId = $user->user_id;

            $pageId = cleanInput($pageId);
            $identifiercln = cleanInput($request->identifier);
            $identifierremove = preg_replace('/[^\p{L}0-9]+/u', '', $identifiercln);
            $identifierBase = strtolower($identifierremove);


            if (
                User::where('identifier', $identifierBase)->exists() ||
                Groups::where('identifier', $identifierBase)->exists() ||
                Pages::where('identifier', $identifierBase)->exists()
            ) {
                // Identifier already exists
                return response()->json(['message' => 'The identifier is already in use.'], 422);
            }

            $page = Pages::where('page_id', $pageId)->first();

            if ($page) {

                $admin = $page->page_admins;

                if (!Str::contains($admin, $userId)) { // Add a closing parenthesis here
                    return response([
                        'message' => 'You are not admin'
                    ]);
                }
            } else {
                return response()->json(['message' => 'Page not found.'], 404);
            }

            $page->update(['identifier' => $identifierBase]);
            return response()->json(['message' => 'Group Identifier updated successfully']);
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }

    //Update Iaccount Identifire
    public function updateIAccountIdentifier($iaccount, Request $request)
    {
        $user = auth()->user();
        if ($user->blueticks) {
            $request->merge(['iaccount' => $iaccount]);
            $this->validate($request, [
                'identifier' => 'required|string|max:200',
                'iaccount' => 'required',
            ]);
            $userId = $user->user_id;
            $iaccount = cleanInput($iaccount);
            $identifiercln = cleanInput($request->identifier);
            $identifierremove = preg_replace('/[^\p{L}0-9]+/u', '', $identifiercln);
            $identifierBase = strtolower($identifierremove);

            if (
                User::where('identifier', $identifierBase)->exists() ||
                Groups::where('identifier', $identifierBase)->exists() ||
                Pages::where('identifier', $identifierBase)->exists()
            ) {
                // Identifier already exists
                return response()->json(['message' => 'The identifier is already in use.'], 422);
            }

            $iaccountId = IAccount::where('iaccount_id',$iaccount)
            ->where('iaccount_creator',$userId)
            ->first();
            if (!$iaccountId) {
                return response()->json(['message' => 'You are not owner of this IAccount']);
            }
            $iaccountId->update(['identifier' => $identifierBase]);
            return response()->json(['message' => 'IAccountIdentifier updated successfully']);
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }
    public function updaeIAccountIdentifier($iaccount, Request $request)
    {
        $user = auth()->user();
        if ($user->blueticks) {
            $request->merge(['iaccount' => $iaccount]);
            $this->validate($request, [
                'identifier' => 'required|string|max:200',
                'iaccount' => 'required',
            ]);
            $userId = $user->user_id;
            $iaccount = cleanInput($iaccount);
            $identifiercln = cleanInput($request->identifier);
            $identifierremove = preg_replace('/[^\p{L}0-9]+/u', '', $identifiercln);
            $identifierBase = strtolower($identifierremove);

            if (
                User::where('identifier', $identifierBase)->exists() ||
                Groups::where('identifier', $identifierBase)->exists() ||
                Pages::where('identifier', $identifierBase)->exists()
            ) {
                // Identifier already exists
                return response()->json(['message' => 'The identifier is already in use.'], 422);
            }

            $iaccountId = IAccount::where('iaccount_id',$iaccount)
            ->where('iaccount_creator',$userId)
            ->first();
            if (!$iaccountId) {
                return response()->json(['message' => 'You are not owner of this IAccount']);
            }
            $iaccountId->update(['identifier' => $identifierBase]);
            return response()->json(['message' => 'IAccountIdentifier updated successfully']);
        } else {
            // Return error response if user is not male or does not have blue_tik
            return response()->json(['message' => 'You does not have blue tik'], 400);
        }
    }
}
