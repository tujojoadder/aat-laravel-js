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


}
