<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Groups;
use App\Models\Loves;
use App\Models\User;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\Replies;
use App\Models\UniqeUser;
use App\Models\Unlikes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CommentsController extends Controller
{
    // create Comment any posts
    public function createPostCommment(Request $request, $postId)
    {
        $user = auth()->user();
        $UserId = $user->user_id;
        $request->merge(['postId' => $postId]);
        // Validate the incoming request data
        $request->validate([
            'postId' => 'required|string',
            'comment_text' => 'required|max:10000',
        ]);
        $postId = cleanInput($postId);
        $post = Posts::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not founded']);
        }

        //make unique id
        $commentId = Str::uuid();

        // Create a new comment using the create method
        $comment = Comments::create([
            'comment_id' => $commentId,
            'post_id' => $postId,
            'commenter_id' => $UserId,
            'comment_text' => $request->comment_text,
            'commenter_id' => $UserId
        ]);

        event (new \App\Events\CommentEvent($comment));
        // Return a detailed response
        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => [
                'user_id' => $UserId,
                'comment_text' => $comment->comment_text,
                'created_at' => $comment->created_at,
            ],
        ]);
    }



    /* get comments for specific post */
    public function getComments(Request $request, $postId)
{
    $user = auth()->user();

    $request->merge(['postId' => $postId]);
    // Validate the incoming request data
    $request->validate([
        'postId' => 'required|string|exists:posts,post_id',
    ]);
    $postId = cleanInput($postId);

    // Get pagination parameters from the request, with default values
    $perPage = 7; // Number of items per page

    // Fetch paginated comments
    $comments = Comments::where('post_id', $postId)
        ->with('commenter:user_id,user_fname,user_lname,identifier,profile_picture')
        ->orderBy('created_at', 'desc') // Sort by creation date in descending order
        ->paginate($perPage);

    // Add isLove, isUnlike, totalLove, and totalUnlike to each comment
    $comments->getCollection()->transform(function ($comment) use ($user) {
        // Check if the current user has loved or unliked the comment
        $isLove = Loves::where('love_on_type', 'comment')
            ->where('love_on_id', $comment->comment_id)
            ->where('love_by_id', $user->user_id)
            ->exists();

        $isUnlike = Unlikes::where('unlike_on_type', 'comment')
            ->where('unlike_on_id', $comment->comment_id)
            ->where('unlike_by_id', $user->user_id)
            ->exists();

        // Count the total loves and unlikes for the comment
        $totalLove = Loves::where('love_on_type', 'comment')
            ->where('love_on_id', $comment->comment_id)
            ->count();

        $totalUnlike = Unlikes::where('unlike_on_type', 'comment')
            ->where('unlike_on_id', $comment->comment_id)
            ->count();

        // Count the number of replies for the comment
        $replyCount = Replies::where('comment_id', $comment->comment_id)->count();

        // Add the values to the comment object
        $comment->isLove = $isLove;
        $comment->isUnlike = $isUnlike;
        $comment->totalLove = $totalLove;
        $comment->totalUnlike = $totalUnlike;
        $comment->reply_count = $replyCount; // Add the reply count

        return $comment;
    });

    // Return paginated comments as JSON
    return response()->json($comments);
}



    //Delete Specific Comment
    public function deleteComment($commentId, Request $request)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['commentId' => $commentId]);
        $request->validate([
            'commentId' => 'required|string|max:50',
        ]);
        $commentId = cleanInput($commentId);
        // Find the comment by its ID
        $comment = Comments::find($commentId);

        // Check if the comment exists
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        if ($comment->commenter_id != $userId) {
            return response()->json(['message' => 'You are not then owner of this comment'], 404);
        }


        // Delete the comment
        $comment->delete();

        // Return a success message
        return response()->json(['message' => 'Comment deleted successfully']);
    }


    public function getCommentReplyCount($commentId)
    {
        // Validate the commentId exists in the comments table
        $commentId = cleanInput($commentId);
    
        // Check if the comment exists
        $comment = Comments::find($commentId);
    
        if (!$comment) {
            return response()->json(['error' => 'Comment not found'], 404);
        }
    
        // Get the number of replies to the comment
        $replyCount = $comment->replies()->count();
    
        // Return the reply count as JSON
        return response()->json([
            'comment_id' => $commentId,
            'reply_count' => $replyCount
        ]);
    }
    




}
