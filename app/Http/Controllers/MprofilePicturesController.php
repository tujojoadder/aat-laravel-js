<?php

namespace App\Http\Controllers;

use App\Models\BluetikPost;
use App\Models\ImagePosts;
use App\Models\MprofilePicture;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Import the Rule class
use Illuminate\Support\Facades\Storage;

class MprofilePicturesController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $messages = []; // Initialize $messages variable

        // Check if any images are provided
        if ($request->hasFile('images')) {
            DB::transaction(function () use ($request, &$messages) {
                foreach ($request->file('images') as $image) {
                    // Generate a unique filename for each image
                    $customFileName = $image->hashName();

                    // Store the image
                    $path = $image->storeAs('public/mprofile_picture', $customFileName);

                    // Generate a URL for the stored image
                    $imageUrl = Storage::url($path);

                    // Create a new record for each image in the database
                    MprofilePicture::create([
                        'profile_picture_id' => Str::uuid(),
                        'image_url' => $imageUrl,
                    ]);

                    // Add success message for each image
                    $messages[] = 'Image successfully stored';
                }
            });
        } else {
            // Add a message indicating that no images were provided
            $messages[] = 'No images provided';
        }

        // Return the response after the transaction completes
        return response()->json(['messages' => $messages]);
    }

    public function view(Request $request)
    {

        // Retrieve the profile picture data from the mprofile_picture table
        $profilePictures = MprofilePicture::all(); // Assuming you want to retrieve all records

        // Pass the profile picture data to the view
        return view('mprofile_picture', ['profilePictures' => $profilePictures]);
    }

    public function setmpicture(Request $request)
    {
        $request->validate([
            'image' =>  'required|exists:mprofile_pictures,image_url',
            //change
            'user_id' => 'required'
        ]);
        $isUser = User::where('user_id', $request->user_id)
            ->where('gender', 'male')
            ->first();

        if (!$isUser) {
            return response([
                'message'=>"User not founded"
            ]);
        }

        // Perform your operation here
        $message = '';
        DB::transaction(function () use ($request, &$message) {
            // Get the authenticated user
            $authUser = auth()->user();
            // Get the user by ID from the request
            $user = User::where('user_id', $request->user_id)->first();

            // Check if the user exists
            if ($user) {
                //Delete the old profile physical file if it has no post
                $oldProfilePost = ImagePosts::where('post_url', $user->profile_picture)->first();
                if (!$oldProfilePost) {
                    $fileName = basename($user->profile_picture);
                    Storage::delete('public/upload/images/' . $fileName);
                }

                // Update the user's profile picture
                $user->update(['profile_picture' => $request->image]);
                $message = 'Profile Picture Updated Successfully';
            } else {
                // Handle the case where the user does not exist
                $message = 'User not found.';
            }
        });

        // Return the message
        return $message;
    }

    public function destroy(Request $request)
    {

        // Delete all records from the mprofile_picture table
        MprofilePicture::truncate();

        // Get all files in the mprofile_picture directory
        $files = Storage::files('public/mprofile_picture');

        // Delete each file individually
        foreach ($files as $file) {
            Storage::delete($file);
        }

        // Retrieve the profile pictures data again after deletion
        $profilePictures = MprofilePicture::all();

        // Return the mprofile_picture view with a deleted message and profile pictures data
        return view('mprofile_picture', ['profilePicturess' => $profilePictures, 'message' => 'All images have been deleted.']);
    }
}
