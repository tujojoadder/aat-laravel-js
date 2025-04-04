<?php

namespace App\Http\Controllers;

use App\Models\BluetikPost;
use App\Models\IAccount;
use App\Models\Pages;
use App\Models\UniqeUser;
use Illuminate\Support\Str;
use App\Models\UploadRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class UploadRequestController extends Controller
{

    public function userprofile_request(Request $request)
{
    $this->validate($request, [
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        'visibility' => 'required|in:public,private,only_me',
    ]);

    $user = auth()->user();
    $isBlueTikActive = $user->blueticks && $user->bluetik_activated_at && now()->diffInDays($user->bluetik_activated_at) <= 30;
    
    // Define max uploads based on Blue Tik status
    $maxUploadsAllowed = $isBlueTikActive ? 15 : 1;
    $timeFrame = $isBlueTikActive ? $user->bluetik_activated_at : Carbon::now()->subDays(30);
    
    // Delete old non-pending requests before the defined timeframe
    UploadRequest::where('uploadrequest_by', $user->user_id)
        ->where('uploadrequest_on_id', $user->user_id)
        ->where('uploadrequest_on_type', 'user')
        ->where('type', 'user_profile')
        ->whereNot('status', 'pending')
        ->where('created_at', '<', $timeFrame)
        ->delete();
    
    // Count uploads within the active period
    $uploadsCurrentPeriod = UploadRequest::where('uploadrequest_by', $user->user_id)
        ->where('uploadrequest_on_id', $user->user_id)
        ->where('uploadrequest_on_type', 'user')
        ->where('type', 'user_profile')
        ->whereNot('status', 'pending')
        ->where('posted_at', '>=', $timeFrame)
        ->count();
    
    if ($uploadsCurrentPeriod >= $maxUploadsAllowed) {
        return response()->json(['message' => 'You have reached the maximum upload limit for this period'], 400);
    }
    
    DB::transaction(function () use ($request, $user) {
        // Check for an existing pending request and delete the associated image
        $existingRequest = UploadRequest::where('uploadrequest_by', $user->user_id)
            ->where('uploadrequest_on_id', $user->user_id)
            ->where('uploadrequest_on_type', 'user')
            ->where('type', 'user_profile')
            ->where('status', 'pending')
            ->first();
        
        if ($existingRequest) {
            Storage::delete('public/upload/images/' . basename($existingRequest->photo_url));
        }
        
        // Store the new image
        $fileName = $request->file('image')->hashName();
        $path = $request->file('image')->storeAs('public/upload/images', $fileName);
        
        // Create new upload request
        UploadRequest::create([
            'uploadrequest_id' => Str::uuid(),
            'uploadrequest_on_id' => $user->user_id,
            'uploadrequest_on_type' => 'user',
            'uploadrequest_by' => $user->user_id,
            'photo_url' => Storage::url($path),
            'type' => 'user_profile',
            'status' => 'pending',
            'audience' => $request->visibility
        ]);
    });
    
    return response()->json(['message' => 'Profile picture request successful']);
}

/* We need to modify all below code accoding userprofile_request controller  */

    //User Cover Photo Request
    public function usercover_request(Request $request)
    {

        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik and is male
        if ($user->blueticks) {
            // Check if the user has already uploaded 3 profile pictures in the last 30 days
            $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                ->where('post_type', 'user_cover')
                ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                ->count();
            // User can upload profile picture for 5 times
            $maxUploadsAllowed = 100;

            if ($uploadsLast30Days < $maxUploadsAllowed) {
                // Start a database transaction
                DB::transaction(function () use ($request, $user) {
                    // Check if there is an existing pending request with type 'user_profile' for the user
                    $existingRequest = UploadRequest::where('uploadrequest_on_id', $user->user_id)
                        ->where('uploadrequest_on_type', 'user')
                        ->where('type', 'user_cover')
                        ->first();

                    // If an existing request is found, delete it and its associated physical image
                    if ($existingRequest) {
                        // Extract the file name from the photo URL
                        $fileName = basename($existingRequest->photo_url);

                        // Delete the physical image from storage
                        Storage::delete('public/upload/images/' . $fileName);

                        // Delete the existing request
                        $existingRequest->delete();
                    }

                    // Generate a unique filename for the image
                    $customFileName = $request->file('image')->hashName();

                    // Store the image
                    $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                    // Generate a URL for the stored image
                    $imageUrl = Storage::url($path);

                    // Create the new image post request
                    UploadRequest::create([
                        'uploadrequest_id' => Str::uuid(),
                        'uploadrequest_on_id' => $user->user_id,
                        'uploadrequest_by' => $user->user_id,
                        'uploadrequest_on_type' => 'user',
                        'photo_url' => $imageUrl,
                        'type' => 'user_cover',
                        'audience' => $request->visibility
                    ]);

                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $user->user_id,
                        'post_type' => 'user_cover',
                        'request_for' => $user->user_id,
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                    ]);
                });

                // Return success response
                return response()->json(['message' => 'Your Cover Photo request successful']);
            } else {
                // Return error response if the user has already uploaded 5 times this month
                return response()->json(['message' => 'You have reached the maximum cover photo upload limit for this month'], 400);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick'], 400);
        }
    }



    // group photo upload request 
    public function groupphoto_request(Request $request, $groupId)
    {

        // Merge the $uploadrequestId into the request object
        $request->merge(['groupId' => $groupId]);
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me',
            'groupId' => 'required|string|max:50',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik and is male
        if ($user->blueticks) {
            //if user are member of the group
            if ($user->groups->contains($groupId)) {
                // Check if the user has already uploaded 3 group profile for specific group in the last 30 days
                $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                    ->where('post_type', 'group_profile')
                    ->where('request_for', $groupId)
                    ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                    ->count();

                // User can uploaded 3 group profile for specific group in the last 30 days
                $maxUploadsAllowed = 100;

                if ($uploadsLast30Days < $maxUploadsAllowed) {
                    // Start a database transaction
                    DB::transaction(function () use ($request, $user, $groupId, &$message) {

                        // Check if any other  group member already requested
                        $otherMemberRequest = UploadRequest::where('uploadrequest_on_id', $groupId)
                            ->where('uploadrequest_on_type', 'group')
                            ->where('type', 'group_profile')
                            ->where('uploadrequest_by', '!=', $user->user_id)
                            ->first();
                        if ($otherMemberRequest) {
                            $message = 'Already Requested by other group member';
                        } else {

                            // Check if there is an existing pending request with type 'user_profile' for the user
                            $existingRequest = UploadRequest::where('uploadrequest_on_id', $groupId)
                                ->where('uploadrequest_on_type', 'group')
                                ->where('type', 'group_profile')
                                ->where('uploadrequest_by', $user->user_id)
                                ->first();
                            // If an existing request is found, delete it and its associated physical image
                            if ($existingRequest) {
                                // Extract the file name from the photo URL
                                $fileName = basename($existingRequest->photo_url);

                                // Delete the physical image from storage
                                Storage::delete('public/upload/images/' . $fileName);

                                // Delete the existing request
                                $existingRequest->delete();
                            }

                            // Generate a unique filename for the image
                            $customFileName = $request->file('image')->hashName();

                            // Store the image
                            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                            // Generate a URL for the stored image
                            $imageUrl = Storage::url($path);

                            // Create the new group profile image post request
                            UploadRequest::create([
                                'uploadrequest_id' => Str::uuid(),
                                'uploadrequest_on_id' => $groupId,
                                'uploadrequest_on_type' => 'group',
                                'uploadrequest_by' => $user->user_id,
                                'photo_url' => $imageUrl,
                                'type' => 'group_profile',
                                'audience' => $request->visibility
                            ]);

                            // Create a new BluetikPost record
                            BluetikPost::create([
                                'bluetik_post_id' => Str::uuid(),
                                'author_id' => $user->user_id,
                                'post_type' => 'group_profile',
                                'request_for' => $groupId,
                                'posted_at' => Carbon::now(),
                            ]);

                            $message = 'Group Profile picture request successful';
                        }
                    });

                    // Return success response
                    return response()->json(['message' => $message]);
                } else {
                    // Return error response if the user has already uploaded 5 times this month
                    return response()->json(['message' => 'You have reached the maximum upload limit for group profile on this month'], 400);
                }
            } else {
                return response(['message' => 'You are not member of this group']);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick '], 400);
        }
    }


    // group photo upload request 
    public function groupcover_request(Request $request, $groupId)
    {

        // Merge the $uploadrequestId into the request object
        $request->merge(['groupId' => $groupId]);
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me',
            'groupId' => 'required|string|max',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik and is male
        if ($user->blueticks) {
            //if user are member of the group
            if ($user->groups->contains($groupId)) {
                // Check if the user has already uploaded 3 group profile for specific group in the last 30 days
                $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                    ->where('post_type', 'group_cover')
                    ->where('request_for', $groupId)
                    ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                    ->count();

                // User can uploaded 3 group profile for specific group in the last 30 days
                $maxUploadsAllowed = 100;

                if ($uploadsLast30Days < $maxUploadsAllowed) {
                    // Start a database transaction
                    DB::transaction(function () use ($request, $user, $groupId, &$message) {

                        // Check if any other  group member already requested
                        $otherMemberRequest = UploadRequest::where('uploadrequest_on_id', $groupId)
                            ->where('uploadrequest_on_type', 'group')
                            ->where('type', 'group_cover')
                            ->where('uploadrequest_by', '!=', $user->user_id)
                            ->first();
                        if ($otherMemberRequest) {
                            $message = 'Already Requested by other group member';
                        } else {

                            // Check if there is an existing pending request with type 'user_profile' for the user
                            $existingRequest = UploadRequest::where('uploadrequest_on_id', $groupId)
                                ->where('uploadrequest_on_type', 'group')
                                ->where('type', 'group_cover')
                                ->where('uploadrequest_by', $user->user_id)
                                ->first();
                            // If an existing request is found, delete it and its associated physical image
                            if ($existingRequest) {
                                // Extract the file name from the photo URL
                                $fileName = basename($existingRequest->photo_url);

                                // Delete the physical image from storage
                                Storage::delete('public/upload/images/' . $fileName);

                                // Delete the existing request
                                $existingRequest->delete();
                            }

                            // Generate a unique filename for the image
                            $customFileName = $request->file('image')->hashName();

                            // Store the image
                            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                            // Generate a URL for the stored image
                            $imageUrl = Storage::url($path);

                            // Create the new group profile image post request
                            UploadRequest::create([
                                'uploadrequest_id' => Str::uuid(),
                                'uploadrequest_on_id' => $groupId,
                                'uploadrequest_on_type' => 'group',
                                'uploadrequest_by' => $user->user_id,
                                'photo_url' => $imageUrl,
                                'type' => 'group_cover',
                                'audience' => $request->visibility
                            ]);

                            // Create a new BluetikPost record
                            BluetikPost::create([
                                'bluetik_post_id' => Str::uuid(),
                                'author_id' => $user->user_id,
                                'post_type' => 'group_cover',
                                'request_for' => $groupId,
                                'posted_at' => Carbon::now(),
                            ]);

                            $message = 'Group Cover picture request successful';
                        }
                    });

                    // Return success response
                    return response()->json(['message' => $message]);
                } else {
                    // Return error response if the user has already uploaded 5 times this month
                    return response()->json(['message' => 'You have reached the maximum upload limit for group profile on this month'], 400);
                }
            } else {
                return response(['message' => 'You are not member of this group']);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick'], 400);
        }
    }



    // page profile photo upload request 

    public function pageprofile_request(Request $request, $pageId)
    {

        // Merge the $uploadrequestId into the request object
        $request->merge(['pageId' => $pageId]);
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me',
            'pageId' => 'required|string|max:50',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik 
        if ($user->blueticks) {


            $page = Pages::where('page_id', $pageId)->first();
            if ($page) {
                $admin = $page->page_admins;
            }


            //if user are Admin of the page 
            if ($page && Str::contains($admin, $user->user_id)) {
                // Check if the user has already uploaded 3 page profile for specific group in the last 30 days
                $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                    ->where('post_type', 'page_profile')
                    ->where('request_for', $pageId)
                    ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                    ->count();

                // User can uploaded 3 group profile for specific group in the last 30 days
                $maxUploadsAllowed = 100;

                if ($uploadsLast30Days < $maxUploadsAllowed) {
                    // Start a database transaction
                    DB::transaction(function () use ($request, $user, $pageId, &$message) {

                        // Check if any other  group member already requested
                        $otherMemberRequest = UploadRequest::where('uploadrequest_on_id', $pageId)
                            ->where('uploadrequest_on_type', 'page')
                            ->where('type', 'page_profile')
                            ->where('uploadrequest_by', '!=', $user->user_id)
                            ->first();
                        if ($otherMemberRequest) {
                            $message = 'Already Requested by other admin';
                        } else {

                            // Check if there is an existing pending request with type 'page_profile' for the user
                            $existingRequest = UploadRequest::where('uploadrequest_on_id', $pageId)
                                ->where('uploadrequest_on_type', 'page')
                                ->where('type', 'page_profile')
                                ->where('uploadrequest_by', $user->user_id)
                                ->first();
                            // If an existing request is found, delete it and its associated physical image
                            if ($existingRequest) {
                                // Extract the file name from the photo URL
                                $fileName = basename($existingRequest->photo_url);

                                // Delete the physical image from storage
                                Storage::delete('public/upload/images/' . $fileName);

                                // Delete the existing request
                                $existingRequest->delete();
                            }

                            // Generate a unique filename for the image
                            $customFileName = $request->file('image')->hashName();

                            // Store the image
                            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                            // Generate a URL for the stored image
                            $imageUrl = Storage::url($path);

                            // Create the new group profile image post request
                            UploadRequest::create([
                                'uploadrequest_id' => Str::uuid(),
                                'uploadrequest_on_id' => $pageId,
                                'uploadrequest_on_type' => 'page',
                                'uploadrequest_by' => $user->user_id,
                                'photo_url' => $imageUrl,
                                'type' => 'page_profile',
                                'audience' => $request->visibility
                            ]);

                            // Create a new BluetikPost record
                            BluetikPost::create([
                                'bluetik_post_id' => Str::uuid(),
                                'author_id' => $user->user_id,
                                'post_type' => 'page_profile',
                                'request_for' => $pageId,
                                'posted_at' => Carbon::now(),
                            ]);

                            $message = 'Page Profile picture request successful';
                        }
                    });

                    // Return success response
                    return response()->json(['message' => $message]);
                } else {
                    // Return error response if the user has already uploaded 5 times this month
                    return response()->json(['message' => 'You have reached the maximum upload limit for group profile on this month'], 400);
                }
            } else {
                return response(['message' => 'You are not admin of this page']);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick '], 400);
        }
    }

    // page cover photo upload request 
    public function pagecover_request(Request $request, $pageId)
    {

        // Merge the $uploadrequestId into the request object
        $request->merge(['pageId' => $pageId]);
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'visibility' => 'required|in:public,private,only_me',
            'pageId' => 'required|string|max:50',
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Check if the user has a blue_tik 
        if ($user->blueticks) {
            $page = Pages::where('page_id', $pageId)->first();
            if ($page) {
                $admin = $page->page_admins;
            }
            //if user are Admin of the page 
            if ($page && Str::contains($admin, $user->user_id)) {
                // Check if the user has already uploaded 3 group profile for specific group in the last 30 days
                $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                    ->where('post_type', 'page_cover')
                    ->where('request_for', $pageId)
                    ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                    ->count();

                // User can uploaded 3 group profile for specific group in the last 30 days
                $maxUploadsAllowed = 100;

                if ($uploadsLast30Days < $maxUploadsAllowed) {
                    // Start a database transaction
                    DB::transaction(function () use ($request, $user, $pageId, &$message) {

                        // Check if any other  group member already requested
                        $otherMemberRequest = UploadRequest::where('uploadrequest_on_id', $pageId)
                            ->where('uploadrequest_on_type', 'page')
                            ->where('type', 'page_cover')
                            ->where('uploadrequest_by', '!=', $user->user_id)
                            ->first();
                        if ($otherMemberRequest) {
                            $message = 'Already Requested by other Page admin';
                        } else {

                            // Check if there is an existing pending request with type 'user_profile' for the user
                            $existingRequest = UploadRequest::where('uploadrequest_on_id', $pageId)
                                ->where('uploadrequest_on_type', 'page')
                                ->where('type', 'page_cover')
                                ->where('uploadrequest_by', $user->user_id)
                                ->first();
                            // If an existing request is found, delete it and its associated physical image
                            if ($existingRequest) {
                                // Extract the file name from the photo URL
                                $fileName = basename($existingRequest->photo_url);

                                // Delete the physical image from storage
                                Storage::delete('public/upload/images/' . $fileName);

                                // Delete the existing request
                                $existingRequest->delete();
                            }

                            // Generate a unique filename for the image
                            $customFileName = $request->file('image')->hashName();

                            // Store the image
                            $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                            // Generate a URL for the stored image
                            $imageUrl = Storage::url($path);

                            // Create the new group profile image post request
                            UploadRequest::create([
                                'uploadrequest_id' => Str::uuid(),
                                'uploadrequest_on_id' => $pageId,
                                'uploadrequest_on_type' => 'page',
                                'uploadrequest_by' => $user->user_id,
                                'photo_url' => $imageUrl,
                                'type' => 'page_cover',
                                'audience' => $request->visibility
                            ]);

                            // Create a new BluetikPost record
                            BluetikPost::create([
                                'bluetik_post_id' => Str::uuid(),
                                'author_id' => $user->user_id,
                                'post_type' => 'page_cover',
                                'request_for' => $pageId,
                                'posted_at' => Carbon::now(),
                            ]);

                            $message = 'Page Cover picture request successful';
                        }
                    });

                    // Return success response
                    return response()->json(['message' => $message]);
                } else {
                    // Return error response if the user has already uploaded 5 times this month
                    return response()->json(['message' => 'You have reached the maximum upload limit for group profile on this month'], 400);
                }
            } else {
                return response(['message' => 'You are not admin of this Page']);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick'], 400);
        }
    }

    //IAccount Profile Request
    public function iaccountProfileRequest(Request $request, $iaccountId)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['groupId' => $iaccountId]);
        // Validate the request data
        $this->validate($request, [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        $iaccountId = cleanInput($iaccountId);
        $isiaccountId=IAccount::find($iaccountId);
      if (!$isiaccountId) {
        return response()->json(['message' => 'IAccount not founded'], 400);
    }
        if ($isiaccountId->iaccount_creator != $userId) {
            return response()->json(['message' => 'You are not owner of this IAccount'], 400);
        }


        // Check if the user has a blue_tik and is male
        if ($user->blueticks) {
            // Check if the user has already uploaded 3 profile pictures in the last 30 days
            $uploadsLast30Days = BluetikPost::where('author_id', $user->user_id)
                ->where('post_type', 'iaccount_profile')
                ->whereDate('posted_at', '>=', now()->subDays(30)) // Filter posts within the last 30 days
                ->count();

            // User can upload profile picture for 5 times
            $maxUploadsAllowed = 3;

            if ($uploadsLast30Days < $maxUploadsAllowed) {
                // Start a database transaction
                DB::transaction(function () use ($request, $user, $userId,$iaccountId) {
                    // Check if there is an existing pending request with type 'user_profile' for the user
                    $existingRequest = UploadRequest::where('uploadrequest_on_id', $iaccountId)
                        ->where('uploadrequest_on_type', 'iaccount')
                        ->where('type', 'iaccount_profile')
                        ->first();

                    // If an existing request is found, delete it and its associated physical image
                    if ($existingRequest) {
                        // Extract the file name from the photo URL
                        $fileName = basename($existingRequest->photo_url);

                        // Delete the physical image from storage
                        Storage::delete('public/upload/images/' . $fileName);

                        // Delete the existing request
                        $existingRequest->delete();
                    }

                    // Generate a unique filename for the image
                    $customFileName = $request->file('image')->hashName();

                    // Store the image
                    $path = $request->file('image')->storeAs('public/upload/images', $customFileName);

                    // Generate a URL for the stored image
                    $imageUrl = Storage::url($path);

                    // Create the new image post request
                    UploadRequest::create([
                        'uploadrequest_id' => Str::uuid(),
                        'uploadrequest_on_id' => $iaccountId,
                        'uploadrequest_by' => $user->user_id,
                        'uploadrequest_on_type' => 'iaccount',
                        'photo_url' => $imageUrl,
                        'type' => 'iaccount_profile',
                    ]);

                    // Create a new BluetikPost record
                    BluetikPost::create([
                        'bluetik_post_id' => Str::uuid(),
                        'author_id' => $user->user_id,
                        'post_type' => 'iaccount_profile',
                        'request_for' => $iaccountId,
                        'posted_at' => Carbon::now(), // Set posted_at with current timestamp
                    ]);
                });

                // Return success response
                return response()->json(['message' => 'IAccount Profile picture request successful']);
            } else {
                // Return error response if the user has already uploaded 5 times this month
                return response()->json(['message' => 'You have reached the maximum upload limit for this month'], 400);
            }
        } else {
            // Return error response if the user is not male or does not have a blue tick
            return response()->json(['message' => 'You do not have a blue tick'], 400);
        }
    }
}
