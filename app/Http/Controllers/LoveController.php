<?php

namespace App\Http\Controllers;

use App\Models\Loves;
use App\Models\UniqeUser;
use App\Models\Unlikes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LoveController extends Controller
{

    /*  $loveOnType= 'post','comment','reply' */
    public function toggleLove(Request $request, $loveOnType, $loveOnId)
    {
        $user = auth()->user();
        $userId = $user->user_id;

        // Merge loveOnType and loveOnId into the request for validation
        $request->merge(['loveOnType' => $loveOnType]);
        $request->merge(['loveOnId' => $loveOnId]);

        // Validate the request
        $request->validate([
            'loveOnType' => 'required|in:post,comment,reply',
            'loveOnId' => 'required|string|max:40',
        ]);

        // Assume cleanInput is a function you have defined elsewhere
        $loveOnType = cleanInput($loveOnType);
        $loveOnId = cleanInput($loveOnId);

        return DB::transaction(function () use ($userId, $loveOnType, $loveOnId) {
            // Find the existing love record
            $love = Loves::where('love_on_type', $loveOnType)
                ->where('love_on_id', $loveOnId)
                ->first();

            // If a love record exists, delete it
            if ($love) {
                $love->delete();
                return response()->json(['data' => 'love removed']);
            } else {
                // Check if there's an unlike record, and delete if found
                $unlike = Unlikes::where('unlike_on_type', $loveOnType)
                    ->where('unlike_on_id', $loveOnId)
                    ->first();

                if ($unlike) {
                    $unlike->delete();
                }

                // Create a new love record
                $loveId = Str::uuid();
                Loves::create([
                    'love_id' => $loveId,
                    'love_on_type' => $loveOnType,
                    'love_on_id' => $loveOnId,
                    'love_by_id' => $userId,
                ]);

                return response()->json(['data' => 'loved successfully']);
            }
        });
    }



    public function toggleUnlike(Request $request, $unlikeOnType, $unlikeOnId)
    {
        $user = auth()->user();
        $userId = $user->user_id;

        // Merge unlikeOnType and unlikeOnId into the request for validation
        $request->merge(['unlikeOnType' => $unlikeOnType]);
        $request->merge(['unlikeOnId' => $unlikeOnId]);

        // Validate the request
        $request->validate([
            'unlikeOnType' => 'required|in:post,comment,reply',
            'unlikeOnId' => 'required|string|max:40',
        ]);

        // Assume cleanInput is a function you have defined elsewhere
        $unlikeOnType = cleanInput($unlikeOnType);
        $unlikeOnId = cleanInput($unlikeOnId);

        return DB::transaction(function () use ($userId, $unlikeOnType, $unlikeOnId) {
            // Find the existing unlike record
            $unlike = Unlikes::where('unlike_on_type', $unlikeOnType)
                ->where('unlike_on_id', $unlikeOnId)
                ->first();

            // If a love record exists, delete it
            if ($unlike) {
                $unlike->delete();
                return response()->json(['data' => 'unlike removed']);
            } else {
                // Check if there's an love record, and delete if found
                $love = Loves::where('love_on_type', $unlikeOnType)
                    ->where('love_on_id', $unlikeOnId)
                    ->first();

                if ($love) {
                    $love->delete();
                }

                // Create a new love record
                $unlikeId = Str::uuid();
                Unlikes::create([
                    'unlike_id' => $unlikeId,
                    'unlike_on_type' => $unlikeOnType,
                    'unlike_on_id' => $unlikeOnId,
                    'unlike_by_id' => $userId,
                ]);

                return response()->json(['data' => 'Unliked successfully']);
            }
        });
    }
}
