<?php

namespace App\Http\Controllers;

use App\Models\BluetikPost;
use App\Models\ImagePosts;
use App\Models\MprofilePicture;
use App\Models\Posts;
use Illuminate\Support\Facades\File; // Import the File facade at the top

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
        // Validate the incoming request for multiple images
        $this->validate($request, [
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
    
        $messages = [];
        $uploadedImages = [];
    
        // Check if any images are provided
        if ($request->hasFile('images')) {
    
            foreach ($request->file('images') as $image) {
                try {
                    // Generate a unique filename or use a specific name if the condition is met
                    $originalFileName = $image->getClientOriginalName();
                    $fileName = 'default_' . $image->hashName();
    
                    if (stripos($originalFileName, 'male') !== false) {
                        $fileName = 'male_' . $image->hashName();
                    }
    
                    // Move the image to the storage directory
                    $path = $image->move(public_path('storage/mprofile_picture/'), $fileName);
    
                    // Generate a public URL for the stored image
                    $imageUrl = asset('storage/mprofile_picture/' . $fileName);
    
                    // Save the image record to the database
                    $data = MprofilePicture::create([
                        'profile_picture_id' => Str::uuid(),
                        'image_url' => $imageUrl,
                    ]);
    
                    // Add the image details to the array
                    $uploadedImages[] = [
                        'file_name' => $fileName,
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
        $profilePictures = MprofilePicture::paginate($perPage, ['*'], 'page', $page);
    
        // Return the paginated response
        return response()->json($profilePictures);
    }
    
  /*   public function setmpicture(Request $request)
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
    } */

    
public function destroy(Request $request)
{
    // Delete all records from the mprofile_picture table
    MprofilePicture::truncate();

    // Define the directory path where images are stored
    $directory = public_path('storage/mprofile_picture/');

    // Get all files in the directory using File facade
    $files = File::files($directory);

    // Delete each file individually
    foreach ($files as $file) {
        File::delete($file); // Deletes the file
    }

    // Return a success response after deletion
    return response()->json(['data' => 'All Fprofile photos deleted']);
}





}
