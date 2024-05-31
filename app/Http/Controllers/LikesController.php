<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Likes;
use App\Models\Posts;
use App\Models\Replies;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class LikesController extends Controller
{

    // Get primary key accoding your type
    private function getPrimaryKeyForType($likeOnType)
    {
        // Add more cases as needed for other types
        switch ($likeOnType) {
            case 'comments':
                return 'comment_id'; // Replace with the actual primary key column name for the 'group' table
            case 'posts':
                return 'post_id'; // Replace with the actual primary key column name for the 'post' table
            case 'reply':
                return 'reply_id '; // Replace with the actual primary key column name for the 'replies' table
            default:
                return 'id'; // Default to 'id' if the type is not recognized
        }
    }


    public function toggleLike(Request $request, $reactionType, $likeOnType, $likeOnId)
{
    $user = auth()->user();
    $userId = $user->user_id;
    $request->merge(['likeOnType' => $likeOnType]);
    $request->merge(['likeOnId' => $likeOnId]);
    $request->merge(['reactionType' => $reactionType]);

    $request->validate([
        'likeOnType' => 'required|in:post,comment,reply',
        'likeOnId' => 'required|string|max:50',
        'reactionType' => 'required|in:love,sad,angry',
    ]);

    $likeOnType = cleanInput($likeOnType);
    $likeOnId = cleanInput($likeOnId);
    $reactionType = cleanInput($reactionType);

    // Find or create the like record for the user and the item
    $like = Likes::where('like_on_type', $likeOnType)
        ->where('like_on_id', $likeOnId)
        ->where('like_by_id', $userId)
        ->first();

    // If the like record exists
    if ($like) {
        // If the existing reaction type is the same as the provided reaction type
        if ($like->reaction_type === $reactionType) {
            // Delete the like record
            $like->delete();
            return $this->getLikeResponse($likeOnType, $likeOnId, $reactionType, 'Like removed successfully');
        } else {
            // Update the reaction type
            $like->update(['reaction_type' => $reactionType]);
            return $this->getLikeResponse($likeOnType, $likeOnId, $reactionType, ucfirst($reactionType) . ' reaction updated successfully');
        }
    } else {
        // Create a new like record
        $likeId = Str::uuid();
        Likes::create([
            'like_id' => $likeId,
            'like_on_type' => $likeOnType,
            'like_on_id' => $likeOnId,
            'like_by_id' => $userId,
            'reaction_type' => $reactionType,
        ]);
        return $this->getLikeResponse($likeOnType, $likeOnId, $reactionType, ucfirst($reactionType) . ' reaction added successfully');
    }
}

private function getLikeResponse($likeOnType, $likeOnId, $reactionType, $message)
{
    // Get the count of likes for the specified reaction type
    $likeCount = Likes::where('like_on_type', $likeOnType)
        ->where('like_on_id', $likeOnId)
        ->where('reaction_type', $reactionType)
        ->count();

    // Construct the response message
    $response = [
        'message' => $message,
        'like_count' => $likeCount,
    ];

    return response()->json($response);
}

    


public function getUsersWhoLiked(Request $request, $likeOnType, $likeOnId)
{
    $request->merge(['likeOnType' => $likeOnType]);
    $request->merge(['likeOnId' => $likeOnId]);

    // Validate the likeOnType
    $request->validate([
        'likeOnType' => 'required|in:post,comment,reply',
        'likeOnId' => 'required|string|max:50',
    ]);

    $likeOnType = cleanInput($likeOnType);
    $likeOnId = cleanInput($likeOnId);
  
    // Retrieve the likes with users
    $likes = Likes::where('like_on_type', $likeOnType)
        ->where('like_on_id', $likeOnId)
        ->with('liker') // Eager load the liker relationship to get user details
        ->get();

    // Initialize arrays to hold liked by users data for each reaction type
    $likedByUsers = [
        'love' => [],
        'sad' => [],
        'angry' => [],
    ];

    // Initialize counters for reaction-based counts
    $reactionCounts = [
        'love' => 0,
        'sad' => 0,
        'angry' => 0,
    ];

    // Iterate over the likes and format the response data
    foreach ($likes as $like) {
        $reactionType = $like->reaction_type;
        //passing value in array whitch part of obj
        $likedByUsers[$reactionType][] = [ //here we are using  extra[] to indicating that we want to add a new array element
            'user_id' => $like->liker->user_id,
            'full_name' => $like->liker->user_fname. " ".$like->liker->user_lname, // Assuming you have a 'full_name' attribute in your User model
            // Add other user details as needed
        ];

        // Increment the counter for the reaction type
        $reactionCounts[$reactionType]++;
    }

    // Calculate total count
    $totalCount = $likes->count();

    // Add total count to the reaction counts
    $reactionCounts['total_count'] = $totalCount;

    // Add reaction-based counts to the response
    $likedByUsers['reaction_counts'] = $reactionCounts;

    // Return the response with the liked item on top
    return response()->json(['liked_by_users' => $likedByUsers]);
}

}
