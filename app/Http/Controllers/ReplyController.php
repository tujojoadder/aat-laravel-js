<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Loves;
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

class ReplyController extends Controller
{

    public function createCommmentReply(Request $request, $commentId)
{
    $user = auth()->user();
    $userId = $user->user_id;

    // Merge the comment ID into the request and validate it
    $request->merge(['commentId' => $commentId]);
    $request->validate([
        'commentId' => 'required|string',
        'reply_text' => 'required|max:10000',
    ]);

    // Clean and find the comment
    $commentId = cleanInput($commentId);
    $comment = Comments::find($commentId);
    if (!$comment) {
        return response()->json(['message' => 'Comment not found'], 404);
    }

    // Generate a unique reply ID
    $replyId = Str::uuid();

    // Create a new reply
    $reply = Replies::create([
        'reply_id' => $replyId,
        'comment_id' => $commentId,
        'replied_by_id' => $userId,
        'reply_text' => $request->reply_text,
    ]);

    event (new \App\Events\ReplyEvent($reply));

    // Return a detailed response
    return response()->json([
        'message' => 'Reply created successfully',
        'reply' => [
            'reply_id' => $replyId,
            'user_id' => $userId,
            'reply_text' => $reply->reply_text,
            'created_at' => $reply->created_at,
        ],
    ]);
}






public function createRepliesToReply(Request $request, $commentId)
{
    $user = auth()->user();
    $userId = $user->user_id;

    // Merge the comment ID into the request and validate it
    $request->merge(['commentId' => $commentId]);
    $request->validate([
        'commentId' => 'required|string|max:50',
        'parent_reply_id' => 'required|max:50|string',
        'reply_text' => 'required|max:10000',
    ]);

    // Clean and find the comment
    $commentId = cleanInput($commentId);
    $parentReplyId = cleanInput($request->parent_reply_id);
    $replyText = cleanInput($request->reply_text);
    $comment = Comments::find($commentId);
    if (!$comment) {
        return response()->json(['message' => 'Comment not found'], 404);
    }

    // Generate a unique reply ID
    $replyId = Str::uuid();

    // Create a new reply
    $reply = Replies::create([
        'reply_id' => $replyId,
        'comment_id' => $commentId,
        'replied_by_id' => $userId,
        'reply_text' => $replyText,
        'parent_reply_id' => $parentReplyId,
    ]);

    // Return a detailed response
    return response()->json([
        'message' => 'Reply created successfully',
        'reply' => [
            'reply_id' => $replyId,
            'user_id' => $userId,
            'parent_reply_id' => $parentReplyId,

            'reply_text' => $reply->reply_text,
            'created_at' => $reply->created_at,
        ],
    ]);
}




/* get comments for specific post */
public function getReplies(Request $request, $CommentID)
{
    $user = auth()->user();

    $request->merge(['CommentID' => $CommentID]);
    // Validate the incoming request data
    $request->validate([
        'CommentID' => 'required|string|exists:comments,comment_id',

    ]);
    $CommentID = cleanInput($CommentID);

    // Get pagination parameters from the request, with default values
    $perPage = 7; // Number of items per page

    // Fetch paginated posts
    $replies = Replies::where('comment_id', $CommentID)
        ->with('repliedBy:user_id,user_fname,user_lname,identifier,profile_picture')
        ->paginate($perPage);

    // Add isLove, isUnlike, totalLove, and totalUnlike to each post
    $replies->getCollection()->transform(function ($reply) use ($user) {
        // Check if the current user has loved or unliked the post
        $isLove = Loves::where('love_on_type', 'reply')
            ->where('love_on_id', $reply->reply_id)
            ->where('love_by_id', $user->user_id)
            ->exists();

        $isUnlike = Unlikes::where('unlike_on_type', 'reply')
            ->where('unlike_on_id', $reply->reply_id)
            ->where('unlike_by_id', $user->user_id)
            ->exists();

        // Count the total loves and unlikes for the post
        $totalLove = Loves::where('love_on_type', 'reply')
            ->where('love_on_id', $reply->reply_id)
            ->count();

        $totalUnlike = Unlikes::where('unlike_on_type', 'reply')
            ->where('unlike_on_id', $reply->reply_id)
            ->count();

        // Add the values to the post object
        $reply->isLove = $isLove;
        $reply->isUnlike = $isUnlike;
        $reply->totalLove = $totalLove;
        $reply->totalUnlike = $totalUnlike;

        return $reply;
    });

    // Return paginated posts as JSON
    return response()->json($replies);
}








}
