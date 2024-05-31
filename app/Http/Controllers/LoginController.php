<?php

namespace App\Http\Controllers;

use App\Models\Groups;
use App\Models\Pages;
use App\Models\UniqeUser;
use App\Models\User;
use Carbon\Carbon;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{
    public function googleLogin()
    {

        return Socialite::driver('google')->redirect();
    }
    public function googleHandle()
    {
        $user = Socialite::driver('google')->user();
        $existingUser = User::where('email', $user->email)->first();

        if ($existingUser) {
            // User already exists, you can redirect or perform any other action
            return response()->json(['message' => "Already has an account"]);
        }

        // Check if additional information is required
        else {
            // Additional information is required, show the form
            return view('google.register', ['user' => $user]);
        }
    }

    public function registerWithAdditionalInfo(Request $request)
    {
        // Validate the form data
        $request->validate([
            'gender' => 'required|in:male,female,others',
            'email' => 'required|email|unique:users,email',
            'fname' => 'required|string|max:50',
            'lname' => 'required|string|max:50',
            'birthdate_day' => 'required|integer|between:1,31',
            'birthdate_month' => 'required|integer|between:1,12',
            'birthdate_year' => [
                'required',
                'integer',
                'date_format:Y',
                function ($attribute, $value, $fail) {
                    // Check if the birth year is within a reasonable range
                    $currentYear = now()->year;
                    $minValidYear = $currentYear - 150; // Adjust the range as needed
                    $sevenYearsAgo = $currentYear - 5; // Calculate the year 7 years ago
                    if ($value < $minValidYear || $value > $sevenYearsAgo) {
                        $fail('The ' . $attribute . ' must be a valid year within the past 150 years and at least 7 years ago.');
                    }
                },
            ],
        ]);


        // Concatenate birthdate components into a single date format
        $birthdate = $request->input('birthdate_year') . '-' . $request->input('birthdate_month') . '-' . $request->input('birthdate_day');

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



        // Create the user
        return DB::transaction(function () use ($request, $photoPath, $birthdate) {

            $fname = cleanInput($request->input('fname'));
            $lname = cleanInput($request->input('lname'));
            $email = cleanInput($request->input('email'));
            $profile_picture = cleanInput($photoPath);
            $password = cleanInput($request->input('password'));
            $gender = cleanInput($request->input('gender'));
            $birthdate = cleanInput($birthdate);
            // Concatenate first name and last name and remove spaces
            $fullName = $fname . $lname;

            // Remove spaces and unwanted characters using regular expression
            $fullName = preg_replace('/[^\p{L}0-9]+/u', '', $fullName);

            // Convert to lowercase
            $fullName = strtolower($fullName);

            // Generate the identifier
            $identifier = $this->generateIdentifier($fullName);
            $newUser = User::create([
                'user_id' => Str::uuid(),
                'user_fname' => $fname,
                'user_lname' => $lname,
                'email' => $email,
                'profile_picture' => $profile_picture,
                'password' => Hash::make($password),
                'gender' => $gender,
                'birthdate' => $birthdate,
                'identifier' => $identifier,
                'cover_photo' => 'storage/defaultCover/user.jpg'
            ]);

            $token = $newUser->createToken('user')->plainTextToken;

            return view('home')->with([
                'message' => 'Registration successful',
                'token' => $token
            ]);
        });
    }
    // Function to generate a unique identifier with at least three numbers appended
    private function generateIdentifier($baseIdentifier)
    {
        // If baseIdentifier is empty, generate a random six-letter string
        if (empty($baseIdentifier)) {
            $baseIdentifier = '';
            for ($i = 0; $i < 6; $i++) {
                $baseIdentifier .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
            }
        }

        // Append an underscore (_) followed by two random letters
        $letters = '_';
        for ($i = 0; $i < 2; $i++) {
            $letters .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
        }
        $baseIdentifier .= $letters;

        // Check if the generated identifier already exists
        while (
            User::where('identifier', $baseIdentifier)->exists() ||
            Groups::where('identifier', $baseIdentifier)->exists() ||
            Pages::where('identifier', $baseIdentifier)->exists()
        ) {
            // If it does, append new random letters
            $letters = '_';
            for ($i = 0; $i < 2; $i++) {
                $letters .= chr(rand(97, 122)); // ASCII codes for lowercase letters (a-z)
            }
            $baseIdentifier .= $letters;
        }

        return $baseIdentifier;
    }
}
