<?php

namespace App\Http\Controllers;

use App\Models\FprofilePictures;
use App\Models\Loves;
use App\Models\MprofilePicture;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\Unlikes;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
/*     View others Profile */

/* Get posts for a specific user */
public function getSpecificUserPosts(Request $request)
{
    $user = auth()->user();
    $specificUserId = cleanInput($request->query('id'));
    
    // Debug the value of $specificUserId if needed
    $perPage = $request->query('per_page', 5);
    $page = $request->query('page', 1);

    // Fetch posts where author_id matches the specific user ID and group_id, page_id, iaccount_id are NULL
    $posts = Posts::where('author_id', $specificUserId)
        ->whereNull('group_id')
        ->whereNull('page_id')
        ->whereNull('iaccount_id')
        ->with(['author', 'textPost', 'imagePost'])
        ->paginate($perPage, ['*'], 'page', $page);
 // Fetch paginated posts
 $posts = Posts::where('author_id', '!=', $user->user_id)
 ->whereNull('group_id')
 ->whereNull('page_id')
 ->whereNull('iaccount_id')
 ->with(['author', 'textPost', 'imagePost'])
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

 // Add the values to the post object
 $post->isLove = $isLove;
 $post->isUnlike = $isUnlike;
 $post->totalLove = $totalLove;
 $post->totalUnlike = $totalUnlike;

 return $post;
});
    return response()->json($posts);
}

    /* get for others profile all photo */
    public function getSpecificUserPhotos(Request $request)
    {
        // Clean the input and get the user ID from the request query
        $specificUserId = cleanInput($request->query('id'));

        // Set default pagination values, with the option to customize via query parameters
        $perPage = $request->query('per_page', 6); // default to 10 per page
        $page = $request->query('page', 1);

        // Query for the posts with associated image posts for the specific user, paginate the results
        $posts = Posts::where('author_id', $specificUserId)
        ->whereNull('group_id')
        ->whereNull('page_id')
        ->whereNull('iaccount_id')    
        ->with('imagePost') // Eager load the image posts relationship
            ->whereHas('imagePost') // Ensure we only get posts with associated image posts
            ->paginate($perPage, ['*'], 'page', $page);

        // Return the paginated result as JSON
        return response()->json($posts);
    }


  /*   Get all user follower for profile */
    public function getAllUserFollower(Request $request)
    {
        $specificUserId = cleanInput($request->query('id'));
    
        $user = User::where('user_id', $specificUserId)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        $perPage = $request->query('per_page', 7);
        $page = $request->query('page', 1);
    
        $followers = $user->followers()->with(['follower' => function ($query) {
            $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
        }])->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json($followers);
    }



  /*   Get all user following  for profile */
    public function getAllUserFollowing(Request $request)
    {
        $specificUserId = cleanInput($request->query('id'));
    
        $user = User::where('user_id', $specificUserId)->first();
    
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }
    
        $perPage = $request->query('per_page', 7);
        $page = $request->query('page', 1);
    
        $followings = $user->followings()->with(['following' => function ($query) {
            $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
        }])->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json($followings);
    }
    



/*     View Auth user Profile */
    /* get auth user profile posts  */
public function getAuthUserPosts(Request $request)
{
    $user = auth()->user();
    $userId=$user->user_id;
    // Debug the value of $specificUserId
    $perPage = $request->query('per_page', 5);
    $page = $request->query('page', 1);

    $posts = Posts::where('author_id', $userId)
        ->with(['author', 'textPost', 'imagePost'])
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

    // Add the values to the post object
    $post->isLove = $isLove;
    $post->isUnlike = $isUnlike;
    $post->totalLove = $totalLove;
    $post->totalUnlike = $totalUnlike;

    return $post;
});
    return response()->json($posts);
}

 /* get for auth user profile all photo */
 public function getAuthUserPhotos(Request $request)
 {

    $user = auth()->user();
    $userId=$user->user_id;

     // Set default pagination values, with the option to customize via query parameters
     $perPage = $request->query('per_page', 6); // default to 10 per page
     $page = $request->query('page', 1);

     // Query for the posts with associated image posts for the specific user, paginate the results
     $posts = Posts::where('author_id', $userId)
         ->with('imagePost') // Eager load the image posts relationship
         ->whereHas('imagePost') // Ensure we only get posts with associated image posts
         ->paginate($perPage, ['*'], 'page', $page);

     // Return the paginated result as JSON
     return response()->json($posts);
 }

/*   Get all followers for the authenticated user on their profile */
public function getAllAuthUserFollower(Request $request)
{
    // Get the authenticated user
    $authUser = auth()->user();

    if (!$authUser) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    // Set pagination variables
    $perPage = $request->query('per_page', 7);
    $page = $request->query('page', 1);

    // Get the authenticated user's followers, including the follower details
    $followers = $authUser->followers()->with(['follower' => function ($query) {
        $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
    }])->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated list of followers
    return response()->json($followers);
}


/*   Get all following for the authenticated user on their profile */
public function getAllAuthUserFollowing(Request $request)
{
    // Get the authenticated user
    $authUser = auth()->user();

    if (!$authUser) {
        return response()->json(['error' => 'User not authenticated.'], 401);
    }

    // Set pagination variables
    $perPage = $request->query('per_page', 7);
    $page = $request->query('page', 1);

    // Get the authenticated user's followings, including the following details
    $followings = $authUser->followings()->with(['following' => function ($query) {
        $query->select('user_id', 'profile_picture', 'user_fname', 'user_lname', 'identifier');
    }])->paginate($perPage, ['*'], 'page', $page);

    // Return the paginated list of followings
    return response()->json($followings);
}




 /*    Set Cover MProfilePicture */
 public function setMProfilePictuire(Request $request)
 {
    
     // Get authenticated user
     $user = auth()->user();
 
     // Validate the incoming request to ensure 'image_id' is provided
     $request->validate([
         'image_id' => 'required|exists:mprofile_pictures,profile_picture_id' // Ensure the provided image ID exists in the cover_photos table
     ]);

     // Use a database transaction for safety
     $Profilephoto = null; // Initialize the cover photo variable
     DB::transaction(function () use ($request, $user, &$Profilephoto) {
         // Find the cover photo by the provided 'image_id'
         $Profilephoto = MprofilePicture::find($request->image_id);
 
         if ($Profilephoto) {
             // Update the user's cover photo with the new image URL
             $user->profile_picture = $Profilephoto->image_url;
             $user->save();
         }
     });
 
     // Return the updated cover photo as a JSON response
     return response()->json(['data' => $Profilephoto], 200);
 }
 
 
/*    Set Cover FProfilePicture */
public function setFProfilePictuire(Request $request)
{
    // Get authenticated user
    $user = auth()->user();

    // Validate the incoming request to ensure 'image_id' is provided
    $request->validate([
        'image_id' => 'required|exists:fprofile_pictures,profile_picture_id' // Ensure the provided image ID exists in the cover_photos table
    ]);

    // Use a database transaction for safety
    $Profilephoto = null; // Initialize the cover photo variable
    DB::transaction(function () use ($request, $user, &$Profilephoto) {
        // Find the cover photo by the provided 'image_id'
        $Profilephoto = FprofilePictures::find($request->image_id);

        if ($Profilephoto) {
            // Update the user's cover photo with the new image URL
            $user->profile_picture = $Profilephoto->image_url;
            $user->save();
        }
    });

    // Return the updated cover photo as a JSON response
    return response()->json(['data' => $Profilephoto], 200);
}



}
