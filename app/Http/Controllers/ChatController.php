<?php

namespace App\Http\Controllers;

use App\Events\MessageEvent;
use App\Models\Chat;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {

        // Validate the request
        $request->validate([
            'receiver_id' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        // Use a database transaction to ensure atomicity
        DB::transaction(function () use ($request, &$response) {
            $user_id = auth()->user()->user_id;

            // Create the chat message
            $chat = Chat::create([
                'id' => Str::uuid(),
                'sender_id' => $user_id,
                'receiver_id' => $request->receiver_id,
                'message' => $request->message,
            ]);

            // Set the response data directly
            $response = [
                'message' => "Success",
                'data' => $chat,
            ];
        });

        // Laravel's exception handling mechanism will automatically catch any exceptions
        // thrown during the transaction and return an appropriate error response.

        // Return the response after the transaction

        event(new MessageEvent($response));
        return response()->json($response, 201);
    }



    public function loadChat(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|string|max:46',
        ]);
        $perPage = 10;
        $auth_user = auth()->user()->user_id;
    
        // Use query parameters instead of request body
        $receiver_id = $request->query('receiver_id');
    
        // Fetch the chat messages based on sender and receiver
        $chats = Chat::where(function ($query) use ($auth_user, $receiver_id) {
            $query->where('sender_id', $auth_user)
                  ->orWhere('sender_id', $receiver_id);
        })
        ->where(function ($query) use ($auth_user, $receiver_id) {
            $query->where('receiver_id', $auth_user)
                  ->orWhere('receiver_id', $receiver_id);
        })
        ->orderBy('created_at', 'asc') // Change 'asc' if you want ascending order
        ->paginate($perPage);
    
        // Return the chat messages in JSON format
        return response()->json(['chat' => $chats]);
    } 

    public function deleteChat(Request $request)
    {
        $request->validate([
            'id' => 'required|string|max:255',
        ]);
        Chat::where('id', $request->id)->delete();

     /*    event(new MessageDeleteEvent($request->id)); */

        return response()->json(['id' => $request->id]);
    }
}
