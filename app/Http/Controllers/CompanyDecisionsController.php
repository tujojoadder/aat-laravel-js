<?php

namespace App\Http\Controllers;

use App\Models\BluetikPost;
use App\Models\Groups;
use App\Models\IAccount;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\UploadRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class CompanyDecisionsController extends Controller
{
    public function userprofile_request_handler(Request $request, $uploadrequestId)
    {
        // Validate the request data, including the existence of $uploadrequestId
        $request->validate([
            'decision' => 'required|in:approved,rejected,accepted',
        ]);

        // Retrieve the requested post
        $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
            ->where('type', 'user_profile')
            ->first();

        // Check if the requested post exists
        if (!$requested_post) {
            return response()->json(['message' => 'Requested post not found'], 404);
        }

        // Check the decision
        if ($request->decision === 'approved') {
            // Proceed with the transaction if validation passes
            DB::transaction(function () use ($requested_post, &$message) {
                // Retrieve the user
                $user = User::where('user_id',$requested_post->uploadrequest_by)->first();

                // Delete the old profile physical file if it has no post associated with it
                $oldCoverPost = ImagePosts::where('post_url', $user->profile_picture)->first();
                if (!$oldCoverPost) {
                    $fileName = basename($user->profile_picture);
                    Storage::delete('public/upload/images/' . $fileName);
                }

                // Generate a UUID for the main post
                $postId = Str::uuid();

                // Create post data
                $postData = [
                    'post_id' => $postId,
                    'author_id' => $requested_post->uploadrequest_on_id,
                    'timeline_ids' => $requested_post->uploadrequest_on_id, // Because profile pictures cannot be tagged
                    'audience' => cleanInput($requested_post->audience),
                    'post_type' => 'user_profile'
                ];

                // Create the main post
                $post = Posts::create($postData);

                // Create the image post
                ImagePosts::create([
                    'image_posts_id' => Str::uuid(),
                    'post_id' => $postId,
                    'post_url' => $requested_post->photo_url,
                    'author_id' => $requested_post->uploadrequest_on_id
                ]);

                // Create a new BluetikPost record
                BluetikPost::create([
                    'bluetik_post_id' => Str::uuid(),
                    'author_id' => $requested_post->uploadrequest_on_id,
                    'post_type' => $requested_post->type,
                    'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                    'request_for'=>$requested_post->uploadrequest_on_id
                ]);

                // Update profile picture on the user table
                $user->update(['profile_picture' => $requested_post->photo_url]);

                // Delete the request from request table 
                $requested_post->delete();
            });
            $message = "Posted Successfully";
        } else {
            // Delete the physical image and the request from the request table 
            $fileName = basename($requested_post->photo_url);
            Storage::delete('public/upload/images/' . $fileName);
            $requested_post->delete();

            // Return success response
            $message = "Request is deleted";
        }
        return response()->json(['message' => $message]);
    }

    //Cover photo decision
    public function usercover_request_handler(Request $request, $uploadrequestId)
    {
        // Validate the request data, including the existence of $uploadrequestId
        $request->validate([
            'decision' => 'required|in:approved,rejected,accepted',
        ]);

        // Retrieve the requested post
        $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
            ->where('type', 'user_cover')
            ->first();
        // Check if the requested post exists
        if (!$requested_post) {
            return response()->json(['message' => 'Requested post not found'], 404);
        }

        // Check the decision
        if ($request->decision === 'approved') {
            // Proceed with the transaction if validation passes
            DB::transaction(function () use ($requested_post, &$message) {
                // Retrieve the user

                /*  $oldgroupPhoto = $group->group_picture;
                    // Delete the old group profile photo physical file if it has no post associated with it
                    $oldCoverPost = ImagePosts::where('post_url', $oldgroupPhoto)->first();
                    if (!$oldCoverPost) {
                        $fileName = basename($oldgroupPhoto);
                        Storage::delete('public/upload/images/' . $fileName);
                    } */
                $user = User::where('user_id',$requested_post->uploadrequest_by)->first();

                // Delete the old profile physical file if it has no post associated with it
                $oldCoverPost = ImagePosts::where('post_url', $user->cover_photo)->first();
                if (!$oldCoverPost) {
                    $fileName = basename($user->cover_photo);
                    Storage::delete('public/upload/images/' . $fileName);
                }

                // Generate a UUID for the main post
                $postId = Str::uuid();

                // Create post data
                $postData = [
                    'post_id' => $postId,
                    'author_id' => $requested_post->uploadrequest_on_id,
                    'timeline_ids' => $requested_post->uploadrequest_on_id, // Because profile pictures cannot be tagged
                    'visibility' => cleanInput($requested_post->audience),
                    'post_type' => 'user_cover'
                ];

                // Create the main post
                $post = Posts::create($postData);

                // Create the image post
                ImagePosts::create([
                    'image_posts_id' => Str::uuid(),
                    'post_id' => $postId,
                    'post_url' => $requested_post->photo_url,
                    'author_id' => $requested_post->uploadrequest_on_id
                ]);

                // Create a new BluetikPost record
                BluetikPost::create([
                    'bluetik_post_id' => Str::uuid(),
                    'author_id' => $requested_post->uploadrequest_on_id,
                    'post_type' => $requested_post->type,
                    'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                    'request_for'=>$requested_post->uploadrequest_on_id
                ]);

                // Update profile picture on the user table
                $user->update(['cover_photo' => $requested_post->photo_url]);

                // Delete the request from request table 
                $requested_post->delete();
            });
            $message = "Posted Successfully";
        } else {
            // Delete the physical image and the request from the request table 
            $fileName = basename($requested_post->photo_url);
            Storage::delete('public/upload/images/' . $fileName);
            $requested_post->delete();

            // Return success response
            $message = "Request is deleted";
        }
        return response()->json(['message' => $message]);
    }

    //Group photo request handler

    public function groupphoto_request_handler(Request $request, $uploadrequestId)
    {
        // Validate the request data, including the existence of $uploadrequestId
        $request->validate([
            'decision' => 'required|in:approved,rejected,accepted',
        ]);

        // Retrieve the requested post
        $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
            ->where('type', 'group_profile')
            ->first();

        // Check if the requested post exists
        if (!$requested_post) {
            return response()->json(['message' => 'Requested post not found'], 404);
        }
        // Check the decision
        if ($request->decision === 'approved') {
            $group = Groups::where('group_id', $requested_post->uploadrequest_on_id)->first();
            if ($group) {
                // Proceed with the transaction if validation passes
                DB::transaction(function () use ($requested_post, &$message, &$group) {
                    // Retrieve the user
                    $user = User::find($requested_post->uploadrequest_by);

                    //retrive the group

                    //old group profile photo
                    $oldgroupPhoto = $group->group_picture;
                    // Delete the old group profile photo physical file if it has no post associated with it
                    $oldCoverPost = ImagePosts::where('post_url', $oldgroupPhoto)->first();
                    if (!$oldCoverPost) {
                        $fileName = basename($oldgroupPhoto);
                        Storage::delete('public/upload/images/' . $fileName);
                    }

                    // Generate a UUID for the main post
                    $postId = Str::uuid();

                    // Create post data
                    $postData = [
                        'post_id' => $postId,
                        'author_id' => $requested_post->uploadrequest_by,
                        'group_id'=>$group->group_id,
                        'audience' => cleanInput($requested_post->audience),
                        'post_type' => 'group_profile'
                    ];

                    // Create the main post
                    $post = Posts::create($postData);

                    // Create the image post
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $postId,
                        'post_url' => $requested_post->photo_url,
                        'author_id' => $requested_post->uploadrequest_by
                    ]);

                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $requested_post->uploadrequest_by,
                        'post_type' => $requested_post->type,
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                        'request_for'=>$requested_post->uploadrequest_on_id
                    ]);

                    // Update profile picture on the user table
                    $group->update(['group_picture' => $requested_post->photo_url]);

                    // Delete the request from request table 
                    $requested_post->delete();
                });
                $message = "Group Photo Uploaded Successfully";
            } else {
                $message = "Group not founded";
            }
        } else {
            // Delete the physical image and the request from the request table 
            $fileName = basename($requested_post->photo_url);
            Storage::delete('public/upload/images/' . $fileName);
            $requested_post->delete();

            // Return success response
            $message = "Group Photo Request deleted";
        }
        return response()->json(['message' => $message]);
    }
    //Group Cover photo request handler

    public function groupcover_request_handler(Request $request, $uploadrequestId)
    {
        
        // Validate the request data, including the existence of $uploadrequestId
        $request->validate([
            'decision' => 'required|in:approved,rejected,accepted',
        ]);

        // Retrieve the requested post
        $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
            ->where('type', 'group_cover')
            ->first();

        // Check if the requested post exists
        if (!$requested_post) {
            return response()->json(['message' => 'Requested post not found'], 404);
        }
        // Check the decision
        if ($request->decision === 'approved') {
            $group = Groups::where('group_id', $requested_post->uploadrequest_on_id)->first();
            if ($group) {
                // Proceed with the transaction if validation passes
                DB::transaction(function () use ($requested_post, &$message, &$group) {
                    // Retrieve the user
                    $user = User::find($requested_post->uploadrequest_by);

                    //retrive the group

                    //old group profile photo
                    $oldgroupPhoto = $group->group_cover;
                    // Delete the old group profile photo physical file if it has no post associated with it
                    $oldCoverPost = ImagePosts::where('post_url', $oldgroupPhoto)->first();
                    if (!$oldCoverPost) {
                        $fileName = basename($oldgroupPhoto);
                        Storage::delete('public/upload/images/' . $fileName);
                    }

                    // Generate a UUID for the main post
                    $postId = Str::uuid();

                    // Create post data
                    $postData = [
                        'post_id' => $postId,
                        'author_id' => $requested_post->uploadrequest_by,
                        'group_id'=>$group->group_id,
                        'audience' => cleanInput($requested_post->audience),
                        'post_type' => 'group_cover'
                    ];

                    // Create the main post
                    $post = Posts::create($postData);

                    // Create the image post
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $postId,
                        'post_url' => $requested_post->photo_url,
                        'author_id' => $requested_post->uploadrequest_by
                    ]);

                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $requested_post->uploadrequest_by,
                        'post_type' => $requested_post->type,
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                        'request_for'=>$requested_post->uploadrequest_on_id
                    ]);

                    // Update profile picture on the user table
                    $group->update(['group_cover' => $requested_post->photo_url]);

                    // Delete the request from request table 
                    $requested_post->delete();
                });
                $message = "Group Cover Photo Uploaded Successfully";
            } else {
                $message = "Group not founded";
            }
        } else {
            // Delete the physical image and the request from the request table 
            $fileName = basename($requested_post->photo_url);
            Storage::delete('public/upload/images/' . $fileName);
            $requested_post->delete();

            // Return success response
            $message = "Group Cover Request deleted";
        }
        return response()->json(['message' => $message]);
    }

     //Page Profile photo request handler

     public function pageprofile_request_handler(Request $request, $uploadrequestId)
     {
         // Validate the request data, including the existence of $uploadrequestId
         $request->validate([
             'decision' => 'required|in:approved,rejected,accepted',
         ]);
 
         // Retrieve the requested post
         $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
             ->where('type', 'page_profile')
             ->first();
 
         // Check if the requested post exists
         if (!$requested_post) {
             return response()->json(['message' => 'Requested post not found'], 404);
         }
         // Check the decision
         if ($request->decision === 'approved') {
             $page = Pages::where('page_id', $requested_post->uploadrequest_on_id)->first();
             if ($page) {
                 // Proceed with the transaction if validation passes
                 DB::transaction(function () use ($requested_post, &$message, &$page) {
                     // Retrieve the user
                     $user = User::find($requested_post->uploadrequest_by);
 
                     //retrive the group
 
                     //old group profile photo
                     $oldpagePhoto = $page->page_picture;
                     // Delete the old page profile photo physical file if it has no post associated with it
                     $oldCoverPost = ImagePosts::where('post_url', $oldpagePhoto)->first();
                     if (!$oldCoverPost) {
                         $fileName = basename($oldpagePhoto);
                         Storage::delete('public/upload/images/' . $fileName);
                     }
 
                     // Generate a UUID for the main post
                     $postId = Str::uuid();
 
                     // Create post data
                     $postData = [
                         'post_id' => $postId,
                         'author_id' => $requested_post->uploadrequest_by,
                         'page_id'=>$page->page_id,
                         'audience' => cleanInput($requested_post->audience),
                         'post_type' => 'page_profile'
                     ];
 
                     // Create the main post
                     $post = Posts::create($postData);
 
                     // Create the image post
                     ImagePosts::create([
                         'image_posts_id' => Str::uuid(),
                         'post_id' => $postId,
                         'post_url' => $requested_post->photo_url,
                         'author_id' => $requested_post->uploadrequest_by
                     ]);
 
                     // Create a new BluetikPost record
                     BluetikPost::create([
                         'bluetik_post_id' => Str::uuid(),
                         'author_id' => $requested_post->uploadrequest_by,
                         'post_type' => $requested_post->type,
                         'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                         'request_for'=>$requested_post->uploadrequest_on_id
                     ]);
 
                     // Update profile picture on the user table
                     $page->update(['page_picture' => $requested_post->photo_url]);
 
                     // Delete the request from request table 
                     $requested_post->delete();
                 });
                 $message = "Page Photo Uploaded Successfully";
             } else {
                 $message = "Page not founded";
             }
         } else {
             // Delete the physical image and the request from the request table 
             $fileName = basename($requested_post->photo_url);
             Storage::delete('public/upload/images/' . $fileName);
             $requested_post->delete();
 
             // Return success response
             $message = "Page Photo Request deleted";
         }
         return response()->json(['message' => $message]);
     }

     //Page Cover photo request handler

    public function pagecover_request_handler(Request $request, $uploadrequestId)
    {
        
        // Validate the request data, including the existence of $uploadrequestId
        $request->validate([
            'decision' => 'required|in:approved,rejected,accepted',
        ]);

        // Retrieve the requested post
        $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
            ->where('type', 'page_cover')
            ->first();

        // Check if the requested post exists
        if (!$requested_post) {
            return response()->json(['message' => 'Requested post not found'], 404);
        }
        // Check the decision
        if ($request->decision === 'approved') {
            $page = Pages::where('page_id', $requested_post->uploadrequest_on_id)->first();
            if ($page) {
                // Proceed with the transaction if validation passes
                DB::transaction(function () use ($requested_post, &$message, &$page) {
                    // Retrieve the user
                    $user = User::find($requested_post->uploadrequest_by);

                

                    //old group profile photo
                    $oldgroupPhoto = $page->page_cover;
                    // Delete the old group profile photo physical file if it has no post associated with it
                    $oldCoverPost = ImagePosts::where('post_url', $oldgroupPhoto)->first();
                    if (!$oldCoverPost) {
                        $fileName = basename($oldgroupPhoto);
                        Storage::delete('public/upload/images/' . $fileName);
                    }

                    // Generate a UUID for the main post
                    $postId = Str::uuid();

                    // Create post data
                    $postData = [
                        'post_id' => $postId,
                        'author_id' => $requested_post->uploadrequest_by,
                        'page_id'=>$page->page_id,
                        'audience' => cleanInput($requested_post->audience),
                        'post_type' => 'page_cover'
                    ];

                    // Create the main post
                    $post = Posts::create($postData);

                    // Create the image post
                    ImagePosts::create([
                        'image_posts_id' => Str::uuid(),
                        'post_id' => $postId,
                        'post_url' => $requested_post->photo_url,
                        'author_id' => $requested_post->uploadrequest_by
                    ]);

                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $requested_post->uploadrequest_by,
                        'post_type' => $requested_post->type,
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                        'request_for'=>$requested_post->uploadrequest_on_id
                    ]);

                    // Update profile picture on the user table
                    $page->update(['page_cover' => $requested_post->photo_url]);

                    // Delete the request from request table 
                    $requested_post->delete();
                });
                $message = "Page Cover Photo Uploaded Successfully";
            } else {
                $message = "Page not founded";
            }
        } else {
            // Delete the physical image and the request from the request table 
            $fileName = basename($requested_post->photo_url);
            Storage::delete('public/upload/images/' . $fileName);
            $requested_post->delete();

            // Return success response
            $message = "Page Cover Request deleted";
        }
        return response()->json(['message' => $message]);
    }


    //

 public function iaccount_profile_request_handler(Request $request, $uploadrequestId)
{
    // Validate the request data, including the existence of $uploadrequestId
    $request->validate([
        'decision' => 'required|in:approved,rejected,accepted',
    ]);

    // Retrieve the requested post
    $requested_post = UploadRequest::where('uploadrequest_id', $uploadrequestId)
        ->where('type', 'iaccount_profile')
        ->first();

    // Check if the requested post exists
    if (!$requested_post) {
        return response()->json(['message' => 'Requested post not found'], 404);
    }

    // Check the decision
    if ($request->decision === 'approved') {
        // Proceed with the transaction if validation passes
        DB::transaction(function () use ($requested_post, &$message) {
            // Retrieve the user
            $iaccount = IAccount::where('iaccount_id',$requested_post->uploadrequest_on_id )->first();

            // Delete the old profile physical file if it has no post associated with it
            $oldProfilePost = ImagePosts::where('post_url', $iaccount->iaccount_picture)->first();
            if (!$oldProfilePost) {
                $fileName = basename($iaccount->iaccount_picture);
                Storage::delete('public/upload/images/' . $fileName);
            }
            // Generate a UUID for the main post
            $postId = Str::uuid();

            // Create post data
            $postData = [
                'post_id' => $postId,
                'author_id' => $requested_post->uploadrequest_by,
                'audience' => cleanInput($requested_post->audience),
                'post_type' => 'iaccount_profile',
                'iaccount_id' =>$requested_post->uploadrequest_on_id,
            ];

            // Create the main post
            $post = Posts::create($postData);

            // Create the image post
            ImagePosts::create([
                'image_posts_id' => Str::uuid(),
                'post_id' => $postId,
                'post_url' => $requested_post->photo_url,
                'author_id' => $requested_post->uploadrequest_by 
            ]);

            // Create a new BluetikPost record
            BluetikPost::create([
                'bluetik_post_id' => Str::uuid(),
                'author_id' => $requested_post->uploadrequest_by,
                'post_type' => $requested_post->type,
                'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                'request_for'=>$requested_post->uploadrequest_on_id 
            ]);

            // Update profile picture on the user table
            $iaccount->update(['iaccount_picture' => $requested_post->photo_url]);

            // Delete the request from request table 
            $requested_post->delete();
        });
        $message = "Posted Successfully";
    } else {
        // Delete the physical image and the request from the request table 
        $fileName = basename($requested_post->photo_url);
        Storage::delete('public/upload/images/' . $fileName);
        $requested_post->delete();

        // Return success response
        $message = "Request is deleted";
    }
    return response()->json(['message' => $message]);
}

}
