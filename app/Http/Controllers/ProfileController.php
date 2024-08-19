<?php

namespace App\Http\Controllers;

use App\Models\Posts;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class ProfileController extends Controller
{
  

/* get for home feed  */
public function getSpecificUserPosts(Request $request)
{
    $user = auth()->user();
    $specificUserId = cleanInput($request->query('id'));
    // Debug the value of $specificUserId
    $perPage = $request->query('per_page', 2);
    $page = $request->query('page', 1);

    $posts = Posts::where('author_id', $specificUserId)
                  ->with(['author', 'textPost', 'imagePost'])
                  ->paginate($perPage, ['*'], 'page', $page);

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
                   ->with('imagePost') // Eager load the image posts relationship
                   ->whereHas('imagePost') // Ensure we only get posts with associated image posts
                   ->paginate($perPage, ['*'], 'page', $page);

     // Return the paginated result as JSON
 return response()->json($posts);
}

}
