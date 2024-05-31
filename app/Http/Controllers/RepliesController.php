<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Replies;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RepliesController extends Controller
{
    //make reply to any Comment
    public function createReplytoComment(Request $request, $commentId)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        // Validate the incoming request data
        $request->merge(['commentId' => $commentId]);
        $request->validate([
            'reply_text' => 'required|string|max:255',
            'commentId' => 'required|string|max:50',
        ]);

        $commentId = cleanInput($commentId);
        $replyText = cleanInput($request->reply_text);

        // Find the comment by ID
        $comment = Comments::find($commentId);

        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }
        //create reply id
        $repalyId = Str::uuid();

        Replies::create([
            'reply_id' => $repalyId,
            'comment_id' => $commentId,
            'replied_by_id' => $userId,
            'reply_text' => $replyText,
        ]);

        // Return a success response
        return response()->json(['message' => 'Reply created successfully'], 201);
    }



    // Retrieve all replies for a specific comment
    public function getRepliesForComment($commentId, Request $request)
    {
        $request->merge(['commentId' => $commentId]);
        $request->validate([
            'commentId' => 'required|string|max:50',
        ]);


        // Clean input
        $commentId = cleanInput($commentId);

        // Find the comment by ID
        $comment = Comments::find($commentId);

        // Check if the comment exists
        if (!$comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        // Retrieve all replies for the comment along with the user details
        $replies = Replies::where('comment_id', $commentId)
            ->with('repliedBy') // Eager load the repliedBy relationship
            ->orderBy('created_at', 'desc') // Order by creation date in descending order
            ->get();

        // Format the replies data
        $formattedReplies = [];

        foreach ($replies as $reply) {
            $formattedReply = [
                'reply_id' => $reply->reply_id,
                'reply_text' => $reply->reply_text,
                'created_at' => $reply->created_at,
                'user' => [
                    'user_id' => $reply->repliedBy->user_id,
                    'user_fullName' => $reply->repliedBy->user_fname . ' ' . $reply->repliedBy->user_lname,
                    'user_profile' => $reply->repliedBy->profile_picture,
                    // Add other user attributes you may need here
                ],
            ];

            $formattedReplies[] = $formattedReply;
        }

        // Return the formatted replies data
        return response()->json(['replies' => $formattedReplies]);
    }



    public function sendReplytoReply(Request $request, $parentReplyId)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['parentReplyId' => $parentReplyId]);
        // Validate the incoming request data
        $request->validate([
            'parentReplyId' => 'required|string|max:50',
            'reply_text' => 'required|string',
        ]);
        $parentReplyId = cleanInput($parentReplyId);
        $replyText = cleanInput($request->reply_text);
        // Find the parent reply based on the parent_reply_id
        $parentReply = Replies::find($parentReplyId);


        // Check if the parent reply exists
        if (!$parentReply) {
            return response()->json(['message' => 'Parent reply not found'], 404);
        }
        $replyId = Str::uuid();

        Replies::create([
            'reply_id' => $replyId,
            'comment_id' => $parentReply->comment_id,
            'parent_reply_id' => $parentReplyId,
            'replied_by_id' => $userId,
            'reply_text' => $replyText,
        ]);

        // Return a success response
        return response()->json(['message' => 'Reply created successfully'], 201);
    }

    //getReplyReplies

    public function getReplyReplies(Request $request, $replyId)
    {
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['replyId' => $replyId]);
        $request->validate([
            'replyId' => 'required|string|max:50',
        ]);
        $replyId = cleanInput($replyId);

        // Find the reply based on the replyId
        $reply = Replies::find($replyId);

        // Check if the reply exists
        if (!$reply) {
            return response()->json(['message' => 'Reply not found'], 404);
        }

        // Retrieve all reply replies for the given reply with their respective user details
        $replyReplies = Replies::where('parent_reply_id', $replyId)
            ->with('repliedBy') // Assuming you have defined the relationship in the Replies model
            ->orderBy('created_at', 'asc') // Order by creation date in ascending order
            ->get();

        // Define an array to hold the formatted reply replies data
        $formattedReplyReplies = [];

        // Iterate through each reply reply and format the data
        foreach ($replyReplies as $replyReply) {
            // Format the reply reply data including user details
            $formattedReplyReply = [
                'reply_id' => $replyReply->reply_id,
                'reply_text' => $replyReply->reply_text,
                'created_at' => $replyReply->created_at,
                'replied_by' => [
                    'user_id' => $replyReply->repliedBy->user_id,
                    'full_name' => $replyReply->repliedBy->user_fname." ".$replyReply->repliedBy->user_lname, // Assuming there's a full_name attribute for the user
                    // Include other user details as needed
                ]
                
            ];

            // Add the formatted reply reply to the array
            $formattedReplyReplies[] = $formattedReplyReply;
        }

        // Return the formatted reply replies data
        return response()->json(['reply' => $reply->reply_text, 'reply_replies' => $formattedReplyReplies]);
    }
}
