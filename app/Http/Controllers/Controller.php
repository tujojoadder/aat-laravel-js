<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

/* // Delete profile update post of male(we will not delete the physical image becase we need the profile picture of user )
    public function destroy(Request $request, $postId)
    {


        $request->merge(['postId' => $postId]);
        $user = auth()->user();
        // Validate request data
        $this->validate($request, [
            'postId' => 'required|exists:posts,post_id',
        ]);

        // Find the post by ID
        $post = Posts::where('post_id', $postId)->first();
        $imagepath = ImagePosts::where('post_id', $postId)->first();
        // Check if the post exists and the authenticated user owns it
                $page = Pages::where('page_id',$post->page_id)->first();
        if ($page) {
            $admin = $page->page_admins;
        }
        
        
        if ($post) {
            // Check the post type
            if ($post->post_type === 'user_profile' && $post->author_id === $user->user_id) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the current profile picture then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $user->profile_picture) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
            } elseif ($post->post_type === 'user_cover' && $post->author_id === $user->user_id) {
                // Delete the post
                $post->delete();
                // if Deleting picture are not the user cover photo then delete the physical picture
                if ($imagepath && $imagepath->post_url !== $user->cover_photo) {
                    // Extract the file name from the URL(only the imagename.jpg/png etc)
                    $fileName = basename($imagepath->post_url);
                    // Delete the physical file from storage
                    Storage::delete('public/upload/images/' . $fileName);
                }
            } elseif ($post->post_type === 'group_profile' && $user->groups->contains($post->group_id)) {
                $group = Groups::where('group_id', $post->group_id)->first();
                if ($group) {
                    // Delete the post
                    $post->delete();
                    // if Deleting picture are not the user cover photo then delete the physical picture

                    if ($imagepath && $imagepath->post_url !== $group->group_picture) {
                        // Extract the file name from the URL(only the imagename.jpg/png etc)
                        $fileName = basename($imagepath->post_url);
                        // Delete the physical file from storage
                        Storage::delete('public/upload/images/' . $fileName);
                    }
                    $message = "Post deleted successfully.";
                    $statusCode = 200; // OK
                } else {
                    $message = "Group not Founded";
                    $statusCode = 404; // Not Found.
                }
            } elseif ($post->post_type === 'group_cover' && $user->groups->contains($post->group_id)) {

                $group = Groups::where('group_id', $post->group_id)->first();
                if ($group) {
                    // Delete the post
                    $post->delete();
                    // if Deleting picture are not the user cover photo then delete the physical picture

                    if ($imagepath && $imagepath->post_url !== $group->group_cover) {
                        // Extract the file name from the URL(only the imagename.jpg/png etc)
                        $fileName = basename($imagepath->post_url);
                        // Delete the physical file from storage
                        Storage::delete('public/upload/images/' . $fileName);
                    }
                    $message = "Post deleted successfully.";
                    $statusCode = 200; // OK
                } else {
                    $message = "You are not Group member or group invalid";
                    $statusCode = 404; // Not Found.
                }
            }elseif ($post->post_type === 'page_profile' && $page && Str::contains($admin, $user->user_id)) {

                if ($page) {
                    // Delete the post
                    $post->delete();
                    // if Deleting picture are not the user cover photo then delete the physical picture

                    if ($imagepath && $imagepath->post_url !== $page->page_profile) {
                        // Extract the file name from the URL(only the imagename.jpg/png etc)
                        $fileName = basename($imagepath->post_url);
                        // Delete the physical file from storage
                        Storage::delete('public/upload/images/' . $fileName);
                    }
                    $message = "Post deleted successfully.";
                    $statusCode = 200; // OK
                } else {
                    $message = "Page is not not found or you are not admin of the Page";
                    $statusCode = 404; // Not Found.
                }
            }
        } else {
            // Post not found or unauthorized access
            $message = "Post not found ";
            $statusCode = 404; // Not Found
        }
        // Return response with appropriate status code
        return response()->json(['message' => $message], $statusCode);
    } */

}
