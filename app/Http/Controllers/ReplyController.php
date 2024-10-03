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

}
