<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use App\Models\Groups;
use App\Models\User;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;

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
            'comment_text' => 'required|max:2000',
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
        ]);

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



    //get Comment of Specific Posts
    public function getPostComments($postId, Request $request)
    {
        $request->merge(['postId' => $postId]);
        $request->validate([
            'postId' => 'required|string|max:50',
        ]);

        // Clean input
        $postId = cleanInput($postId);

        // Retrieve the post
        $post = Posts::find($postId);

        // Check if the post exists
        if (!$post) {
            return response()->json(['message' => 'Post not found']);
        }

        // Retrieve the comments for the post along with the user details
        $comments = Comments::where('post_id', $postId)
            ->with('commenter') // Eager load the commenter relationship
            ->orderBy('created_at', 'desc') // Order by creation date in descending order
            ->get();

        // Format the comments data
        $formattedComments = [];

        foreach ($comments as $comment) {
            $formattedComment = [
                'comment_id' => $comment->comment_id,
                'comment_text' => $comment->comment_text,
                'created_at' => $comment->created_at,
                'user' => [
                    'user_id' => $comment->commenter->user_id, // Access the user_id attribute
                    'user_fullName' => $comment->commenter->user_fname . " " . $comment->commenter->user_lname, // Access the user_id attribute
                    'user_profile' => $comment->commenter->profile_picture, // Access the user_id attribute
                    // Add other user attributes you may need here
                ],
            ];

            $formattedComments[] = $formattedComment;
        }

        // Return the formatted comments data
        return response()->json(['comments' => $formattedComments]);
    }

    //Delete Specific Comment
    public function deleteComment($commentId,Request $request)
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
}
