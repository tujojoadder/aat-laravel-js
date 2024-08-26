<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BluetickUserController;
use App\Http\Controllers\CommentsController;
use App\Http\Controllers\CompanyDecisionsController;
use App\Http\Controllers\CoverPhotoController;
use App\Http\Controllers\DemoRegisterController;
use App\Http\Controllers\FprofilePicturesController;
use App\Http\Controllers\FriendListController;
use App\Http\Controllers\FriendRequestController;
use App\Http\Controllers\GroupJoinRequestController;
use App\Http\Controllers\GroupsController;
use App\Http\Controllers\HadithController;
use App\Http\Controllers\HiController;
use App\Http\Controllers\IAccountController;
use App\Http\Controllers\IAccountFollowersController;
use App\Http\Controllers\IfollowController;
use App\Http\Controllers\LikesController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MprofilePicturesController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePictureController;
use App\Http\Controllers\RepliesController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UploadRequestController;
use App\Http\Controllers\UserController;
use App\Models\GroupJoinRequest;
use App\Models\IAccount;
use App\Models\Pages;
use App\Models\Replies;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use PHPUnit\TextUI\XmlConfiguration\GroupCollection;




//<-- public Routes -->

/* for google */

Route::post('/googlehandle', [LoginController::class, 'googlehandle']);
Route::post('/additionalinformation', [LoginController::class, 'additionalinformation']);

/* normal login */
Route::post('/login', [LoginController::class, 'login']);
//forgot password
Route::post('/forgotpassword', [LoginController::class, 'forgotpassword']);
//password reset
Route::post('/resetpassword', [LoginController::class, 'resetpassword']);
//confirm password to reset
Route::post('/confirmpassword', [LoginController::class, 'confirmpassword']);





Broadcast::routes(['middleware' => ['auth:sanctum']]);

Route::middleware('auth:sanctum')->post('/user', function (Request $request) {
       // Get the authenticated user
       $user = $request->user();

       // Check if the user is authenticated
       if (!$user) {
              return response()->json(['message' => 'Unauthenticated'], 403);
       }

       // Authorize the user for private channel access
       return Broadcast::auth($request);
});

Route::middleware('auth:sanctum')->get('/profile', function (Request $request) {

       // Get the authenticated user
       $authUser = $request->user();

       // Get all users except the authenticated user
       $users = User::where('user_id', '!=', $authUser->user_id)->get();

       return response()->json($users);
});

//Story and Ques

/* insert Hadith with laravel */

Route::post('/hadith/insert', [HadithController::class, 'hadithInsert'])
       ->name('hadithInsert');

//<-- no auth now
Route::post('/storyandques/create', [HadithController::class, 'createHadithQues'])
       ->name('createhadithandques');








       


/* <---  Demo registration   ---> */
//public-demo registration api
Route::post('/register', [DemoRegisterController::class, 'register']);
//public-add additional field for google login
Route::post('/google/register/additional', [LoginController::class, 'registerWithAdditionalInfo'])->name('google.register.additional');
//protected-store fprofile piictures
//<-- For Female and Others -->
//<-- no auth now
//demo register
Route::post('/demoregister', [DemoRegisterController::class, 'register'])->name('demoregister');

//trial for upload request
Route::get('/request', [PagesController::class, 'request'])->name('request');



/* <---  Fprofile Picture   ---> */
//store female profile pictures
Route::post('/fpicturestore', [FprofilePicturesController::class, 'store'])->name('fpstore');
//protected-   view all fprofile piictures
//view Fprofile picture 
Route::get('/viewfpicturestore', [FprofilePicturesController::class, 'view'])->name('viewfp');
//protected-set fprofile piicture as profile picture
//<-- no auth now
//delete Fprofile picture 
Route::delete('/deletefprofilepicture', [FprofilePicturesController::class, 'destroy'])->name('fprofilePicture.delete');
//<-- no auth now
//set Fprofile picture to user
Route::post('/setfpicture', [FprofilePicturesController::class, 'setfpicture'])->name('setfpicture');

/* <---  Mprofile Picture   ---> */
//store male profile pictures
Route::post('/mpicturestore', [MprofilePicturesController::class, 'store'])->name('mpstore');
//protected-   view all mprofile piictures
//view mprofile picture 
Route::get('/viewmpicturestore', [MprofilePicturesController::class, 'view'])->name('viewmp');
//protected-set mprofile piicture as profile picture
//<-- no auth now
//delete mprofile picture 
Route::delete('/deletemprofilepicture', [MprofilePicturesController::class, 'destroy'])->name('mprofilePicture.delete');
//<-- no auth now

/* <---  Cover Photo   ---> */
//store cover photo --.from laravel view
Route::post('/coverphotostore', [CoverPhotoController::class, 'store'])->name('cpstore');
//protected-   view all cover photo

//protected-set fprofile piicture as profile picture
//<-- no auth now
//delete cover photo --->from laravel view
Route::delete('/deletecoverphoto', [CoverPhotoController::class, 'destroy'])->name('cp.delete');
//<-- no auth now






/* Private Routes */

Route::middleware('auth:sanctum')->group(function () {


Route::get('/userdetails', [UserController::class, 'userDetails'])->name('userDetails');


       //<-- Bluetick Users (Everyone gender) -->
       Route::post('/uploadprofilepicture', [BluetickUserController::class, 'uploadprofilepicture'])->name('uploadpp');
       //delete profile update post of male(we will not delete the physical image becase we need the profile picture of user )
       Route::delete('/destroyppicturepost', [BluetickUserController::class, 'destroympicturePost'])->name('destroympicturePost');
       //update user profile name (every gender)
       Route::put('/users/updateusername', [UserController::class, 'update_username'])->name('updateusername');
       //api for update gender male to  the female/others
       Route::put('/users/updategender', [UserController::class, 'update_gender'])->name('updategender');
       //update birthdate
       Route::put('/users/updatebirthdate', [UserController::class, 'update_birthdate'])->name('updatebirthdate');
       //update user privacy_setting
       Route::put('/users/updateprivacysetting', [UserController::class, 'updateprivacy_setting'])->name('updateprivacysetting');
       //view any users profile
       Route::get('/profile/{user_id}', [UserController::class, 'view_profile'])->name('profile.show');

       /* <--- Friend  ---> */
     //get friend sugestion 7 record for home
       Route::get('/friendsuggestionhome', [FriendRequestController::class, 'getFriendSuggestionHome']);
//get User info for show others profile 
Route::get('/userdetails/{id}', [FriendRequestController::class, 'getUserInfo']);


       
       //send friend request
       Route::post('/sendfriendrequest', [FriendRequestController::class, 'send_friendrequest']);
       // view auth user friend requests
       Route::get('/users/friendrequestlist', [FriendRequestController::class, 'friend_requestlist'])->name('friendrequestlist');



       // Manage friend request(Accepted,rejected)
       Route::post('/users/managefriendrequest/{requested_id}', [FriendRequestController::class, 'manageFriendRequest'])->name('manageFriendRequest');
       // unfriend any friend
       Route::post('/users/unfrienduser/{useridtoremove}', [FriendRequestController::class, 'unfriendUser'])->name('unfriendUser');
       // Retrive all friends of auth user
       Route::get('/users/getfriendids', [FriendRequestController::class, 'getFriendIds'])->name('getFriendIds');

       /* <---  Post ---> */

       // Create User Post
       Route::post('post/create', [PostsController::class, 'createUserPost'])
              ->name('createUserPost');
       //Create group post
       Route::post('/group/{groupId}/post/create', [PostsController::class, 'createGroupPost'])
              ->name('createGroupPost');
       //Create page post
       Route::post('/page/{pageId}/post/create', [PostsController::class, 'createPagePost'])
              ->name('createPagePost');
       //Delete any post 
       Route::delete('/post/{postid}/delete', [PostsController::class, 'destroy'])
              ->name('post.delete');

       /* 
        // Update Post API
        Route::put('/{postId}/update', [PostsController::class, 'update'])->name('updatePost');

        // Delete Post API
        Route::delete('/{postId}/delete', [PostsController::class, 'delete'])->name('deletePost');

        // Get Post API
        Route::get('/{postId}', [PostsController::class, 'getPost'])->name('getPost');

        // Like/Unlike Post API
        // Like post
        Route::post('/{postId}/like', [PostsController::class, 'like'])->name('likePost');
        // Unlike post
        Route::delete('/{postId}/unlike', [PostsController::class, 'unlike'])->name('unlikePost');

        // Comment on Post API
        Route::post('/{postId}/comments', [CommentsController::class, 'create'])->name('createComment');

        // Share Post API
        Route::post('/{postId}/share', [PostsController::class, 'share'])->name('sharePost');

        // Report Post API
        Route::post('/{postId}/report', [PostsController::class, 'report'])->name('reportPost'); */


       /* <--- user Blueticks Request --->  */

       //User Profile Picture Request
       Route::post('/users/profilepicture/uploadrequest', [UploadRequestController::class, 'userprofile_request'])
              ->name('user.profilepicture.upload');

       // User Profile Picture Request Handler
       Route::post('/users/{reuestid}/profilepicture/handle', [CompanyDecisionsController::class, 'userprofile_request_handler'])
              ->name('user.profilepicture.handle');

       //User User Cover Photo Request
       Route::post('/users/coverphoto/uploadrequest', [UploadRequestController::class, 'usercover_request'])
              ->name('user.coverphoto.upload');

       // User User Cover Photo Request Handler
       Route::post('/users/{reuestid}/coverphoto/handle', [CompanyDecisionsController::class, 'usercover_request_handler'])
              ->name('user.coverphoto.handle');

       //upadete user identifier 
       Route::put('/user/update/identifier', [BluetickUserController::class, 'updateUserIdentifier'])
              ->name('upadte.identifier.user');


       /* <--- Group Blueticks Request --->  */

       //Group Profile Picture Request
       Route::post('/groups/{groupid}/photo/uploadrequest', [UploadRequestController::class, 'groupphoto_request'])
              ->name('group.photo.upload');

       // Group Profile Picture Request Handler
       Route::post('/groups/{reuestid}/grouppicture/handle', [CompanyDecisionsController::class, 'groupphoto_request_handler'])
              ->name('group.profilepicture.handle');

       //Group Cover Photo Request
       Route::post('/group/coverphoto/{groupid}/uploadrequest', [UploadRequestController::class, 'groupcover_request'])
              ->name('group.coverphoto.upload');

       // Group Cover Photo Request Handler
       Route::post('/group/{reuestid}/groupcoverphoto/coverphoto/handle', [CompanyDecisionsController::class, 'groupcover_request_handler'])
              ->name('group.coverphoto.handle');

       //upadete Group identifier 
       Route::put('/groups/{groupId}/update/identifier', [BluetickUserController::class, 'updateGroupIdentifier'])
              ->name('upadte.identifier.group');





       /* <--- Page Blueticks Request --->  */

       //Page Profile Photo  Request
       Route::post('/page/{groupid}/photo/uploadrequest', [UploadRequestController::class, 'pageprofile_request'])
              ->name('page.profilepicture.upload');

       //Page Profile Photo  Request Handler
       Route::post('/page/{reuestid}/pageprofile/handle', [CompanyDecisionsController::class, 'pageprofile_request_handler'])
              ->name('page.pageprofile.handle');

       //Page Cover Photo Request
       Route::post('/page/coverphoto/{groupid}/uploadrequest', [UploadRequestController::class, 'pagecover_request'])
              ->name('page.coverphoto.upload');

       // User User Cover Photo Request Handler
       Route::post('/page/{reuestid}/coverphoto/handle', [CompanyDecisionsController::class, 'pagecover_request_handler'])
              ->name('page.coverphoto.handle');

       //upadete Group identifier 
       Route::put('/page/{pageId}/update/identifier', [BluetickUserController::class, 'updatePageIdentifier'])
              ->name('upadte.identifier.page');






       /* <--- IAccount Blueticks Request --->  */

       //IAccount Profile Photo  Request
       Route::post('/iaccount/{iaccountId}/photo/uploadrequest', [UploadRequestController::class, 'iaccountProfileRequest'])
              ->name('iaccount.profilepicture.upload');

       //Page Profile Photo  Request Handler
       Route::post('/iaccount/{reuestid}/iaccountprofile/handle', [CompanyDecisionsController::class, 'iaccount_profile_request_handler'])
              ->name('iaccount.profile.handle');





       /* <--- User ---> */

       // Create user Post
       Route::post('user/post/create', [PostsController::class, 'createUserPost'])
              ->name('createPost.user');






       /* <--- Group ---> */

       // Create Group API
       Route::post('group/create', [GroupsController::class, 'createGroup'])
              ->name('createGroup');
       // Add any user to group
       Route::post('group/addmember/{groupId}/{newMember}', [GroupsController::class, 'addMember'])
              ->name('addMember.group');
       // Set new admins group(the new admin have to member of the group)
       Route::post('group/addadmin/{groupId}/{newAdmin}', [GroupsController::class, 'addAdmin'])
              ->name('addAdmin.group');
       // send request to join group
       Route::post('group/joinrequest/{groupId}', [GroupJoinRequestController::class, 'send_groupjoin_request'])
              ->name('joinrequest.group');
       // manage request to join group
       Route::post('group/managejoinrequest/{requestedId}', [GroupJoinRequestController::class, 'manageGroupjoinRequest'])
              ->name('managejoinrequest.group');
       //Retrive Group members
       Route::get('/groups/{groupId}/members', [GroupsController::class, 'getGroupMembers'])
              ->name('members.group');
       //Retrive all post from group
       Route::get('/groups/{groupId}/posts', [GroupsController::class, 'getGroupPosts'])
              ->name('getposts.group');
       //upadete group name 
       Route::put('/groups/{groupId}/update/name', [GroupsController::class, 'updateGroupName'])
              ->name('upadte.name.group');
       //upadete group details 
       Route::put('/groups/{groupId}/update/details', [GroupsController::class, 'updateGroupDetails'])
              ->name('upadte.details.group');



       /* <--- Page ---> */

       // Create Page
       Route::post('page/create', [PagesController::class, 'createPage'])
              ->name('createPage');
       // FollowOrUnFollow Page
       Route::post('page/{pageId}/followorunfollow', [PagesController::class, 'FollowOrUnFollowPage'])
              ->name('followorunfollow.page');
       // add admins on Page
       Route::post('page/addadmin/{pageId}/{newAdminId}', [PagesController::class, 'addAdmin'])
              ->name('addadmin.page');
       //Retrive Page members
       Route::get('/page/{pageId}/members', [PagesController::class, 'getPageMembers'])
              ->name('addmember.page');
       //create Page post
       Route::post('/page/post/create/{pageId}', [PostsController::class, 'createPagePost'])
              ->name('createpost.page');
       //Retrive Page posts
       Route::get('/page/{pageId}/post', [PagesController::class, 'getPagePosts'])
              ->name('retrivePost.page');
       //upadete page name 
       Route::put('/page/{pageId}/update/name', [PagesController::class, 'updatePageName'])
              ->name('upadte.name.page');
       //upadete page details 
       Route::put('/page/{pageId}/update/details', [PagesController::class, 'updatePageDetails'])
              ->name('upadte.details.page');



       /*  <<< --- IAccount --->>> */


       //Create IAccount
       Route::post('/iaccount/create', [IAccountController::class, 'createIAccount'])
              ->name('createIAccount.iaccount');

       //Retrive Iaccount followers
       Route::get('/iaccount/getfollowers/{iaccountId}', [IAccountController::class, 'getIaccountFollower'])
              ->name('getfollowers.iaccount');
       //Retrive how many iaccount user following
       Route::get('/user/get/followingiaccount', [IAccountController::class, 'getfollowingIAccounts'])
              ->name('getfollowingIAccounts.iaccount');
       //Follow or Unfollow IAccounts
       Route::post('/iaccount/{iaccountId}/followorunfollow', [IAccountFollowersController::class, 'followOrUnFollowIaccount'])
              ->name('getfollowIAccounts.iaccount');
       //upadete Group identifier 
       Route::put('/iaccount/{iaccountId}/update/identifier', [BluetickUserController::class, 'updateIAccountIdentifier'])
              ->name('upadte.identifier.iaccount');

       // Create iaccount Post
       Route::post('iaccount/{iaccountId}/post/create', [IAccountController::class, 'createIAccountPost'])
              ->name('createPost.iaccount');

       // Retrive Specific IAccount Posts
       Route::get('iaccount/{iaccountId}/getpost', [IAccountController::class, 'getIAccountPosts'])
              ->name('retrivePost.islamic');

       //getFollowingIAccountsPosts
       Route::get('iaccount/followingaccounts/getposts', [IAccountController::class, 'getFollowingIAccountsPosts'])
              ->name('followingaccounts.getposts.islamic');

       //getAll_IAccountsPosts
       Route::get('iaccount/getposts', [IAccountController::class, 'getAllAccountsPosts'])
              ->name('getAllAccountsPosts.islamic');



       /*  <<< --- Comments --->>> */

       //create comment to any post      
       Route::post('/post/{postId}/createcomment', [CommentsController::class, 'createPostCommment'])
              ->name('createPostCommment.comment');

       //create specific post comments     
       Route::get('/post/{postId}/getcomment', [CommentsController::class, 'getPostComments'])
              ->name('getPostComments.comment');

       //delete specific comments     
       Route::delete('/post/{postId}/deletecomment', [CommentsController::class, 'deleteComment'])
              ->name('deleteComment.comment');




       /*  <<< --- Replies --->>> */

       //Reply any Comment  
       Route::post('/comments/{commentId}/createreplies', [RepliesController::class, 'createReplytoComment'])
              ->name('createReplytoComment.replies');

       //retrive any Comments  Replies 
       Route::get('/comments/{commentId}/getreplies', [RepliesController::class, 'getRepliesForComment'])
              ->name('getRepliesForComment.replies');

       //Reply any reply  
       Route::post('/reply/{parentReplyId}/createreply', [RepliesController::class, 'sendReplytoReply'])
              ->name('sendReplytoReply.replies');

       //retrive any Reply  Replies 
       Route::get('/reply/{replyId}/getreplies', [RepliesController::class, 'getReplyReplies'])
              ->name('getReplyReplies.replies');



       /*  <<< --- Likes --->>> */

       //toggle Like 
       Route::post('/like/toggle/{reactionType}/{likeOnType}/{likeOnId}', [LikesController::class, 'toggleLike'])
              ->name('toggleLike.likes');
       //getUsersWhoLiked 
       Route::get('/like/wholiked/{likeOnType}/{likeOnId}', [LikesController::class, 'getUsersWhoLiked'])
              ->name('getUsersWhoLiked.likes');


      /* <--- LogOut --> */
      Route::post('/logout', [LoginController::class, 'logOut'])
      ->name('logOut');
       /*  <<< --- Report --->>> */

       //Report any Model
       Route::post('/report/create/{ReportOnType}/{ReportOnId}', [ReportController::class, 'createReport'])
              ->name('createReport.report');
       //getUsersWithReports
       Route::get('/report/getuserswithreports', [ReportController::class, 'getUsersWithReports'])
              ->name('getUsersWithReports.report');



       /*  <<< --- Report --->>> */

       Route::get('/message', [DemoRegisterController::class, 'loaddashboard']);
       /*    <---- chat ----> */
       Route::post('/chat', [DemoRegisterController::class, 'chat']);

/* <---- Hadith ----> */
/* Hadith for show in hadith box */
Route::get('/get-random-hadith', [HadithController::class, 'getRandomHadith'])
->name('getRandomhadith');


/*<---- Day Hadith ---> */

/* setdayhadith */
Route::post('/setdayhadith', [HadithController::class, 'setDayHadith'])
->name('setDayHadith');
/*  Get all user day hadith  */
Route::get('/getdayhadiths', [HadithController::class, 'getDayHadiths'])
->name('getDayHadiths');
/* like day hadith */
Route::post('/likedayhadith', [HadithController::class, 'likeDayHadith'])
->name('likeDayHadith');
/* Day hadith Details  */
Route::post('/dayhadithdetails', [HadithController::class, 'dayHadithDetails']);



/* Post */
Route::get('/getposts', [PostsController::class, 'getPosts']);



        /* Others Profile   */

Route::get('/getspecificuserposts', [ProfileController::class, 'getSpecificUserPosts']);
Route::get('/getspecificuserphotos', [ProfileController::class, 'getSpecificUserPhotos']);
/* get all followers for specific user on profile*/
Route::get('/getspecificuserfollower', [ProfileController::class, 'getAllUserFollower']);
/* Following User */
Route::get('/getspecificuserfollowing', [ProfileController::class, 'getAllUserFollowing']);
// view specific user friend list
Route::get('/getspecificuserfriendids', [FriendRequestController::class, 'getSpecificUserFriendDetails'])->name('getSpecificUserFriendIds');



            /* Auth user Profile   */

Route::get('/getauthuserposts', [ProfileController::class, 'getAuthUserPosts']);
Route::get('/getauthuserphotos', [ProfileController::class, 'getAuthUserPhotos']);
/* get all followers for specific user on profile*/
Route::get('/getauthuserfollower', [ProfileController::class, 'getAllAuthUserFollower']);
/* Following User */
Route::get('/getauthuserfollowing', [ProfileController::class, 'getAllAuthUserFollowing']);
// view auth user friend list
Route::get('/getauthuserfriendids', [FriendRequestController::class, 'getAuthUserFriendDetails']);
//set Mprofile picture to user
Route::post('/setmprofile', [ProfileController::class, 'setMProfilePictuire'])->name('setmpicture');
//set Fprofile picture to user
Route::post('/setfprofile', [ProfileController::class, 'setFProfilePictuire']);









/*  About */
Route::post('/about/createorupdate', [AboutController::class, 'storeOrUpdate']);
Route::get('/getabout', [AboutController::class, 'getAbout']);

/*<---- Cover photo ----> */
//view cover photo
Route::get('/viewcoverphotostore', [CoverPhotoController::class, 'view'])->name('viewcp');
//set cover photo to user
Route::post('/setcoverphoto', [CoverPhotoController::class, 'setcoverphoto'])->name('setcp');





});
