<?php

namespace App\Http\Controllers;

use App\Models\CoverPhoto;
use App\Models\FprofilePictures;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule; // Import the Rule class
use Illuminate\Support\Facades\Storage;

class CoverPhotoController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $messages = []; // Initialize $messages variable

        // Check if any images are provided
        if ($request->hasFile('images')) {
            DB::transaction(function () use ($request, &$messages) {
                foreach ($request->file('images') as $image) {
                    // Generate a unique filename for each image
                    $customFileName = $image->hashName();

                    // Store the image
                    $path = $image->storeAs('public/cover_photo', $customFileName);

                    // Generate a URL for the stored image
                    $imageUrl = Storage::url($path);

                    // Create a new record for each image in the database
                    CoverPhoto::create([
                        'cover_photo_id' => Str::uuid(),
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

        // Retrieve the profile picture data from the fprofile_picture table
        $CoverPhoto = CoverPhoto::all(); // Assuming you want to retrieve all records

        // Pass the profile picture data to the view
        return view('cover_photo', ['CoverPhotos' => $CoverPhoto]);
    }

    public function setcoverphoto(Request $request)
    {
        $request->validate([
            'image' =>  'required|exists:cover_photos,image_url',
        //change
            'user_id' => 'required',
        ]);


        // Perform your operation here
        DB::transaction(function () use ($request, &$message) {
            $user = User::where('user_id', $request->user_id)->first();

            if ($user) {
                //Delete the old cover photo physical file if it has no post
                $oldProfilePost = ImagePosts::where('post_url', $user->cover_photo)->first();
                if (!$oldProfilePost) {
                    $fileName = basename($user->cover_photo);
                    Storage::delete('public/upload/images/' . $fileName);
                }

                // Update the user's cover photo
                $user->update(['cover_photo' => $request->image]);
                $message = 'Cover Photo Updated Succesfully';
            } else {
                // Handle the case where the user does not exist
                $message = 'User not found.';
            }
        });
        return response()->json(['message' => $message]);
    }
    public function destroy(Request $request)
    {
        // Delete all records from the fprofile_picture table
        CoverPhoto::truncate();

        // Get all files in the fprofile_picture directory
        $files = Storage::files('public/cover_photo');

        // Delete each file individually
        foreach ($files as $file) {
            Storage::delete($file);
        }

        // Retrieve the profile pictures data again after deletion
        $CoverPhotos = CoverPhoto::all();

        // Return the fprofile_picture view with a deleted message and profile pictures data
        return view('cover_photo', ['CoverPhotos' => $CoverPhotos, 'message' => 'All images have been deleted.']);
    }
}
