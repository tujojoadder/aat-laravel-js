<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Loves;
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
        $postId = $comment->post_id;
        // Generate a unique reply ID
        $replyId = Str::uuid();

        // Create a new reply
        $reply = Replies::create([
            'reply_id' => $replyId,
            'comment_id' => $commentId,
            'replied_by_id' => $userId,
            'reply_text' => $request->reply_text,
        ]);

        // Count the total comments related to the post
        $totalComments = Comments::where('post_id', $postId)->count();

        // Count the total replies related to the comment
        // Count the total replies related to all comments of the post
        $totalReplies = Replies::whereIn('comment_id', function ($query) use ($postId) {
            $query->select('comment_id')->from('comments')->where('post_id', $postId);
        })->count();
        // Compute the sum of total comments and replies
        $totalActivity = $totalComments + $totalReplies;

        $replyData = [

            'replied_by_id' => $userId,
            'post_id' => $postId,
            'reply_text' => $reply->reply_text,
            'created_at' => $reply->created_at,
            'reply_id' => $reply->reply_id,
            'total_comment' => $totalActivity // Optional: If you want to return this in the response
        ];

        event(new \App\Events\ReplyEvent($replyData));

        // Return a detailed response
        return response()->json([
            'message' => 'Reply created successfully',
            'reply' => $replyData,
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
        //PostId
        $postId = $comment->post_id;
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

       // Count the total comments related to the post
       $totalComments = Comments::where('post_id', $postId)->count();

       // Count the total replies related to the comment
       // Count the total replies related to all comments of the post
       $totalReplies = Replies::whereIn('comment_id', function ($query) use ($postId) {
           $query->select('comment_id')->from('comments')->where('post_id', $postId);
       })->count();
       // Compute the sum of total comments and replies
       $totalActivity = $totalComments + $totalReplies;

       $replyData = [

           'replied_by_id' => $userId,
           'post_id' => $postId,
           'reply_text' => $reply->reply_text,
           'created_at' => $reply->created_at,
           'total_comment' => $totalActivity // Optional: If you want to return this in the response
       ];

       event(new \App\Events\ReplyEvent($replyData));

       // Return a detailed response
       return response()->json([
           'message' => 'Reply created successfully',
           'reply' => $replyData,
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
            ->orderBy('created_at', 'desc') // Sort by creation date in descending order
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
