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
use Google_Client;
class LoginController extends Controller
{
   
    public function googleHandle(Request $request)
    {
     
        $client = new Google_Client(['client_id' => '921280622729-651dvf4na3lejbnqn7tbsutvirne3hn2.apps.googleusercontent.com']);
        $payload = $client->verifyIdToken($request->token);

        if ($payload) {
            $userid = $payload['sub'];
            // Here, you can find or create a user in your database using the user information from $payload
            return response()->json(['message' => 'Login successful', 'user' => $payload]);
        } else {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }

    public function additionalinformation(Request $request)
    {
        

        // Validate the form data
        $validatedData =$request->validate([
            'formData.gender' => 'required|in:male,female,others',
            'email' => 'required|email|unique:users,email',
            'formData.fname' => 'required|string|max:50',
            'formData.lname' => 'required|string|max:50',
            'formData.birthdate_day' => 'required|integer|between:1,31',
            'formData.birthdate_month' => 'required|integer|between:1,12',
            'formData.birthdate_year' => [
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
        $birthdate = $request->formData['birthdate_year'] . '-' . $request->formData['birthdate_month'] . '-' . $request->formData['birthdate_day'];

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
        return DB::transaction(function () use ($request, $photoPath, $birthdate,$validatedData) {

 // If validation passes, extract the validated data
    $formData = $validatedData['formData'];
    $email = cleanInput($request->input('email'));

    // Other data
    $fname = cleanInput($formData['fname']);
    $lname = cleanInput($formData['lname']);
    $profile_picture = cleanInput($photoPath);
    $password = cleanInput($request->input('password'));
    $gender = cleanInput($formData['gender']);
    $birthdate = cleanInput($birthdate);

    // Concatenate first name and last name and remove spaces
    $fullName = $fname . $lname;

    // Remove spaces and unwanted characters using regular expression
    $fullName = preg_replace('/[^\p{L}0-9]+/u', '', $fullName);

    // Convert to lowercase
    $fullName = strtolower($fullName);

    // Generate the identifier
    $identifier = $this->generateIdentifier($fullName);

    // Create the user
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

    // Generate token for the user
    $token = $newUser->createToken('user')->plainTextToken;

    return response()->json(['message' => 'Registration successful', 'token' => $token]);
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
