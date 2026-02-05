<?php

namespace App\Http\Controllers;

use App\Models\IAccount;
use App\Models\iAccountFollowers;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class IAccountFollowersController extends Controller
{
    //Follow or unfollow any Iaccount 
    public function followOrUnFollowIaccount(Request $request, $iaccountId)
    {
        // Retrieve the authenticated user
        $user = auth()->user();
        $userId = $user->user_id;
        $request->merge(['iaccountId' => $iaccountId]);
        $this->validate($request, [
            'iaccountId' => 'required|string|max:50',
        ]);
        $iaccountId = cleanInput($iaccountId);
        $iaccount = IAccount::where('iaccount_id', $iaccountId)->first();



        if (!$iaccount) {
            return response()->json(['message' => 'IAccount not found'], 404);
        }

        // Check if the user is already a member of the page
        $isMember = iAccountFollowers::where('iaccount_id', $iaccountId)
            ->where('follower_id', $userId)
            ->exists();

        if (!$isMember) {
            // If the user is not a member, create the association
            iAccountFollowers::create([
                'iaccount_followers_id' => Str::uuid(),
                'iaccount_id' => $iaccountId,
                'follower_id' => $userId
            ]);
            return response()->json(['message' => 'You have followed the IAccount.']);
        } else {
            // If the user is a member, remove the association
            iAccountFollowers::where('iaccount_id', $iaccountId)
                ->where('follower_id', $userId)
                ->delete();
            return response()->json(['message' => 'You have unfollowed the page']);
        }
    }
}
