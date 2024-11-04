<?php

namespace App\Http\Controllers;

use App\Models\CoverPhoto;
use App\Models\FprofilePictures;
use App\Models\ImagePosts;
use App\Models\Pages;
use App\Models\Posts;
use App\Models\UniqeUser;
use Illuminate\Support\Facades\File; // Import the File facade at the top

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
    // Validate the incoming request for multiple images
    $this->validate($request, [
        'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $messages = [];
    $uploadedImages = [];

    // Define specific names for which we want to use the original file names
    $specificNames = ['group', 'page', 'iaccount', 'user'];

    // Check if any images are provided
    if ($request->hasFile('images')) {

        foreach ($request->file('images') as $image) {
            try {
                // Get the original file name
                $originalFileName = $image->getClientOriginalName();
                // Generate a unique filename for each image
                $fileNameToUse = $originalFileName;

                // Check if the original file name matches any of the specific names
                if (in_array(pathinfo($originalFileName, PATHINFO_FILENAME), $specificNames)) {
                    // Use the original file name if it matches the specific names
                    $fileNameToUse = $originalFileName;
                } else {
                    // Generate a unique filename for other images
                    $fileNameToUse = $image->hashName();
                }

                // Move the image to the storage directory
                $path = $image->move(public_path('storage/cover_photo/'), $fileNameToUse);

                // Generate a public URL for the stored image
                $imageUrl = 'storage/cover_photo/' . $fileNameToUse;

                // Save the image record to the database
                $data = CoverPhoto::create([
                    'cover_photo_id' => Str::uuid(),
                    'image_url' => $imageUrl,
                ]);

                // Add the image details to the array
                $uploadedImages[] = [
                    'file_name' => $fileNameToUse,
                    'url' => $imageUrl,
                ];

                // Add a success message for each image
                $messages[] = 'Image successfully stored: ' . $imageUrl;
            } catch (\Exception $e) {
                // Add an error message if storing an image fails
                $messages[] = 'Failed to store image: ' . $e->getMessage();
            }
        }
    } else {
        // Add a message indicating that no images were provided
        $messages[] = 'No images provided';
    }

    // Return the response including the messages and details of uploaded images
    return response()->json([
        'messages' => $messages,
        'uploaded_images' => $uploadedImages,
    ]);
}


    public function view(Request $request)
    {
        // Determine the number of items per page (default to 5 if not specified)
        $perPage = $request->input('per_page', 5);  // Default to 5 items per page if not provided
        $page = $request->input('page', 1);  // Default to page 1 if not provided

        // Retrieve the paginated profile picture data from the mprofile_picture table
        $profilePictures = CoverPhoto::paginate($perPage, ['*'], 'page', $page);

        // Return the paginated response
        return response()->json($profilePictures);
    }



 /*    Set Cover Photo */
   public function setCoverPhoto(Request $request)
{
    // Get authenticated user
    $user = auth()->user();

    // Validate the incoming request to ensure 'image_id' is provided
    $request->validate([
        'image_id' => 'required|exists:cover_photos,cover_photo_id' // Ensure the provided image ID exists in the cover_photos table
    ]);

    // Use a database transaction for safety
    $coverPhoto = null; // Initialize the cover photo variable
    DB::transaction(function () use ($request, $user, &$coverPhoto) {
        // Find the cover photo by the provided 'image_id'
        $coverPhoto = CoverPhoto::find($request->image_id);

        if ($coverPhoto) {
            // Update the user's cover photo with the new image URL
            $user->cover_photo = $coverPhoto->image_url;
            $user->save();
        }
    });

    // Return the updated cover photo as a JSON response
    return response()->json(['data' => $coverPhoto], 200);
}







    /*  BlueTik logic */
    /* public function setcoverphotoBlueTic(Request $request)
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
 */


    public function destroy(Request $request)
    {
        // Delete all records from the fprofile_picture table
        CoverPhoto::truncate();

     // Define the directory path where images are stored
     $directory = public_path('storage/cover_photo/');

     // Get all files in the directory using File facade
     $files = File::files($directory);
 
     // Delete each file individually
     foreach ($files as $file) {
         File::delete($file); // Deletes the file
     }
 
     // Return a success response after deletion
     return response()->json(['data' => 'All FProfile picture  deleted']);
  
    }
}
