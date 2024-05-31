<?php

namespace App\Http\Controllers;

use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DemoRegisterController extends Controller
{
    public function register(Request $request)
    {

        // Validate the form data
        $request->validate([
            'gender' => 'required|in:male,female,others',
            'email' => 'required|email',
            'fname' => 'required|string|max:50',
            'lname' => 'required|string|max:50',
            'password' => 'required|min:8',
            'birthdate' => 'required|date',

        ]);
        // Set the default profile photo based on gender
        $photoPath = '';
        switch ($request->input('gender')) {
            case 'male':
                $photoPath = 'storage/defaultProfile/male.png';
                break;
            case 'female':
                $photoPath = 'storage/defaultProfile/female.png';
                break;
            default:
                $photoPath = 'storage/defaultProfile/others.jpeg';
        }
        return DB::transaction(function () use ($request, $photoPath) {
            $user = User::where('email', $request->email)->first();
            if (!$user) {
                $newUser = User::create([
                    'user_id' => Str::uuid(),
                    'identifier' => Str::uuid(),
                    'cover_photo' => Str::uuid(),
                    'user_fname' => $request->input('fname'),
                    'user_lname' => $request->input('lname'),
                    'email' => $request->input('email'),
                    'profile_picture' => $photoPath,
                    'password' => Hash::make($request->input('password')),
                    'gender' => $request->input('gender'),
                    'birthdate' => $request->birthdate,
                ]);

                $token = $newUser->createToken('user')->plainTextToken;

                return response()->json(['message' => "Register Successfully", 'token' => $token]);
            }

            return response()->json(['message' => "Already has an account"]);
        });
    }


    public function loaddashboard(Request $request){
        $user = $request->user();

        return $user;
    }

    public function chat(Request $request){
        return "Hello";
    }
}
