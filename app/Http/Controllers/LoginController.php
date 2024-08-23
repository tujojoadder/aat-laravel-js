<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Groups;
use App\Models\Pages;
use App\Models\PasswordReset;
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
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;


class LoginController extends Controller
{


    /*  google login  */
    public function googleHandle(Request $request)
    {

        $client = new Google_Client(['client_id' => '921280622729-651dvf4na3lejbnqn7tbsutvirne3hn2.apps.googleusercontent.com']);
        $payload = $client->verifyIdToken($request->token);

        if ($payload) {
            $email = $payload['email'];
            $user = User::where('email', $email)->first();

            //if user already have account
            if ($user) {
                // Generate token for the user
                $token = $user->createToken('user')->plainTextToken;
                return response()->json(['message' => 'have account', 'token' => $token]);
            }
            // Here, you can find or create a user in your database using the user information from $payload
            return response()->json(['message' => 'no account', 'email' => $email]);
        } else {
            return response()->json(['message' => 'Invalid token'], 401);
        }
    }

    public function additionalinformation(Request $request)
    {


        // Validate the form data
        $validatedData = $request->validate([
            'formData.gender' => 'required|in:male,female,others',
            'email' => 'required|email|unique:users,email',
            'formData.fname' => 'required|string|max:50',
            'formData.lname' => 'required|string|max:50',
            'formData.password' => 'required|min:8',
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
        
        
        $formData = $validatedData['formData'];
        // Set the default profile photo based on gender
        $photoPath = '';
        switch (cleanInput($formData['gender'])) {
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
        return DB::transaction(function () use ($request, $photoPath, $birthdate, $validatedData,$formData) {

            // If validation passes, extract the validated data
          
            $email = cleanInput($request->input('email'));

            // Other data
            $fname = cleanInput($formData['fname']);
            $lname = cleanInput($formData['lname']);
            $profile_picture = cleanInput($photoPath);
            $password = cleanInput($formData['password']);
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
           $userId=Str::uuid();
            // Create the user
            $newUser = User::create([
                'user_id' =>$userId ,
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


            About::create([
                'about_id' => Str::uuid(),
                'user_id' => $userId,
                'relationship_status' =>'single',
                
            ]);



            // Generate token for the user
            $token = $newUser->createToken('user')->plainTextToken;

            return response()->json(['message' => 'Registration successful', 'token' => $token,'data'=>$newUser]);
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



    /* Normal login */

    public function login(Request $request)
    {

        $validatedData = $request->validate([

            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);
        $email = cleanInput($request->email);
        $password = cleanInput($request->password);
        $user = User::where('email', $request->email)->first();
        if ($user) {
            if (!Hash::check($password, $user->password)) {
                return response()->json(['message' => 'passwordInvalid']);
            } else {
                $token = $user->createToken('login')->plainTextToken;
                return response()->json(['message' => 'sucessful', 'token' => $token,'data'=>$user]);
            }
        } else {
            return response()->json(['message' => 'noEmail']);
        }
    }


    //forgot password
    public function forgotpassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);
        $email = cleanInput($request->email);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email does not exist']);
        }

        // Delete the old password reset request
        $passwordResetRequest = PasswordReset::where('email', $email)->first();
        if ($passwordResetRequest) {
            $passwordResetRequest->delete();
        }

        // Create new token and password reset record
        $token = Str::uuid();
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
            'created_at' => Carbon::now()
        ]);

        // Send Email with Reset Link
        Mail::send('R_reset', ['token' => $token], function (Message $message) use ($email) {
            $message->subject('Reset Your Password');
            $message->to($email);
        });

        return response()->json(['message' => 'Email sent to your mail']);
    }

    //reset password

    public function resetpassword(Request $request)
    {

        $token = cleanInput($request->token);
$emailuser=PasswordReset::where('token',$token)->first();

        //we will move 404 
        PasswordReset::where('token',$token)->firstOrFail();

        //Delete Token older then 1 minute
        $formatted = Carbon::now()->subMinutes(1);
        $user = PasswordReset::where('created_at', '<=', $formatted)->first();

        if ($user) {
            $user->delete();
            return response()->json(['message' => 'Token validation time over']);
        }else{
            //user accessing before 1 minute 
            return response()->json(['success' => 'success','email'=>$emailuser->email]);

        }

    }
    public function confirmpassword(Request $request) {


        $password=cleanInput($request->password);
        $confirm_password=cleanInput($request->confirm_password);
if ($password ==$confirm_password ) {
    $user=User::where('email',$request->email)->first();
    if ($user) { 
        $user->password = Hash::make($password);
        $user->save();
        return response()->json(['message' => 'sucess'], 200);
    } else {
        return response()->json(['message' => 'User not found'], 404);
    }
}else{
    return response()->json(['message' =>'Passwords do not match']);
}

    }



    //logut
    public function logOut(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully'], 200);
    }


}
