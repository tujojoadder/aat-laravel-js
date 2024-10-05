<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\BluetickUserController;
use App\Http\Controllers\ChatController;
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
use App\Http\Controllers\LoveController;
use App\Http\Controllers\MprofilePicturesController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\PostsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePictureController;
use App\Http\Controllers\QuizGameController;
use App\Http\Controllers\RepliesController;
use App\Http\Controllers\ReplyController;
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
       //cancel friend request
       Route::post('/cancelfriendrequest', [FriendRequestController::class, 'cancel_friend_request']);

       // view auth user friend requests
       Route::get('/users/friendrequestlist', [FriendRequestController::class, 'friend_requestlist'])->name('friendrequestlist');



       // Manage friend request(Accepted,rejected)
       Route::post('managefriendrequest', [FriendRequestController::class, 'manageFriendRequest'])->name('manageFriendRequest');
       // unfriend any friend
       Route::post('/users/unfrienduser/{useridtoremove}', [FriendRequestController::class, 'unfriendUser'])->name('unfriendUser');
       // Retrive all friends of auth user
       Route::get('/users/getfriendids', [FriendRequestController::class, 'getFriendIds'])->name('getFriendIds');

       /*    Friend --->>home */
       Route::get('/auth-friend-requests', [FriendRequestController::class, 'friend_request']);
       Route::get('/getsuggestionfriends', [FriendRequestController::class, 'getsuggestionfriend']);
       /*   Friend --->>sentRequest  */
       /* get all sented request auuth user did pendding*/
       Route::get('/sentfriendrequest', [FriendRequestController::class, 'getSentFriendRequest']);






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
       //(Both-->public/private) Set new admins group(the new admin have to member of the group)
       Route::post('groups/{groupId}/add-admin/{newAdmin}', [GroupsController::class, 'addAdmin'])
              ->name('addAdmin.group');
       //(Both-->public/private) remove user from group
       Route::delete('/groups/{groupId}/kick-out-member/{memberId}', [GroupsController::class, 'kickOutUser']);



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
       //Retrive joined groups(Not admin)
       Route::get('/groups/joined-not-admin', [GroupsController::class, 'getJoinedGroupsButNotAdmin']);
       //right -->Retrive joined groups(Not admin)
       Route::get('/groups/joined-not-admin-right', [GroupsController::class, 'getJoinedGroupsButNotAdminRight']);

       // Route to join a public group
       Route::post('groups/join/{groupId}', [GroupsController::class, 'joinPublicGroup'])->name('groups.join');

       // Route to leave a group
       Route::post('groups/leave/{groupId}', [GroupsController::class, 'leaveGroup'])->name('groups.leave');

       //get specific groupdetails 
       //Retrive joined groups(admin)
       Route::get('/groups/joined-admin', [GroupsController::class, 'getGroupsWhereAdmin']);

       // group suggestions
       Route::get('/groups/suggestions', [GroupsController::class, 'getGroupSuggestion']);

       //get specific groupdetails 
       Route::get('/groupdetails/{id}', [GroupsController::class, 'groupDetails']);
       /* get specific group posts */
       Route::get('/getspecificgroupposts', [GroupsController::class, 'getSpecificGroupPosts']);
       /*    get specific group posts approval requests*/
       Route::get('/getspecificgrouppostapprovalrequestes', [GroupsController::class, 'getSpecificGroupPostsApprovalRequestes']);

       //get specific group photo
       Route::get('/getspecificgroupphotos', [GroupsController::class, 'getSpecificGroupPhotos']);
       //get specific group member
       Route::get('/getspecificgroupmember', [GroupsController::class, 'getAllGroupMember']);
       //get specific group  for manage
       Route::get('/getspecificgroupmembermanage', [GroupsController::class, 'gettAllGroupMemberManage']);

       /* private group join request */
       Route::post('/groups/join-request-private/{groupId}', [GroupsController::class, 'joinRequestPrivateGroup']);
       /* private group le request */
       Route::delete('/groups/cancel-join-request/{groupId}', [GroupsController::class, 'cancelJoinRequest']);
       /*     get join request for specific group */
       Route::get('group/join-requests', [GroupsController::class, 'getUsersWithJoinRequests']);

       // Manage join group request(add,cancel)
       Route::post('managejoingrouprequest', [GroupsController::class, 'manageJoinGroupRequest']);
       // approvGroupPost
       Route::post('/groups/{groupId}/posts/{postId}/approve', [GroupsController::class, 'approvGroupPost']);
       // reject approvGroupPost
       Route::delete('/groups/{groupId}/posts/{postId}/reject', [GroupsController::class, 'RejectApprovGroupPost']);
       // Create group Post
       Route::post('group/post/create', [PostsController::class, 'createGroupPost']);




       //get random group Post
       Route::get('/group/randomposts', [GroupsController::class, 'getRandomGroupPosts']);
       //get combine group
       Route::get('/combined-groups', [GroupsController::class, 'getCombinedGroups']);












       /* <--- Page ---> */

       // Create Page
       Route::post('/create-page', [PagesController::class, 'createPage']);


       // FollowOrUnFollow Page
       Route::post('page/{pageId}/followorunfollow', [PagesController::class, 'FollowOrUnFollowPage'])
              ->name('followorunfollow.page');
       // add admins on Page
       Route::post('page/addadmin/{pageId}/{newAdminId}', [PagesController::class, 'addAdmin'])
              ->name('addadmin.page');
       // get pages where auth user is admin
       Route::get('/get-pages-where-admin', [PagesController::class, 'getPagesWhereAdmin']);
       // get liked pages
       Route::get('/get-pages-liked', [PagesController::class, 'getLikedPages']);
       // get page suggestions
       Route::get('/get-pages-suggestion', [PagesController::class, 'getPageSuggestion']);
       //get specific groupdetails 
       Route::get('/pagedetails/{id}', [PagesController::class, 'pageDetails']);


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
       Route::put('/page/{pageId}/update/name', [PagesController::class, 'updatePageName']);
       //upadete page details 
       Route::put('/page/{pageId}/update/details', [PagesController::class, 'updatePageDetails']);

       /* get specific page posts */
       Route::get('/getspecificpageposts', [PagesController::class, 'getSpecificPagePosts']);
       //get specific page photo
       Route::get('/getspecificpagephotos', [PagesController::class, 'getSpecificPagePhotos']);
       //get specific page members
       Route::get('/getspecificpagemember', [PagesController::class, 'getAllPageMember']);

       //get specific page members for manage
       Route::get('/getspecificpagememberformanage', [PagesController::class, 'gettAllPageMemberManage']);


       // Route to join a public group
       Route::post('page/join/{pageId}', [PagesController::class, 'joinPage']);

       // Route to leave a group
       Route::post('page/leave/{pageId}', [PagesController::class, 'leavePage']);
       //upadete pages name 
       Route::put('/pages/{pageId}/update/name', [PagesController::class, 'updatePageName']);
       //upadete pages details 
       Route::put('/pages/{pageId}/update/details', [PagesController::class, 'updatePageDetails']);
       // Create page Post
       Route::post('page/post/create', [PostsController::class, 'createPagePost']);






       //upadete pages location 
       Route::put('/pages/{pageId}/update/location', [PagesController::class, 'updatePageLocation']);
       //upadete pages phone 
       Route::put('/pages/{pageId}/update/phone', [PagesController::class, 'updatePagePhone']);
       //upadete pages email 
       Route::put('/pages/{pageId}/update/email', [PagesController::class, 'updatePageEmail']);
       //(Both-->public/private) Set new admins group(the new admin have to member of the group)
       Route::post('page/{pageId}/add-admin/{newAdmin}', [PagesController::class, 'addAdmin']);
       //(Both-->public/private) remove user from group
       Route::delete('/page/{pageId}/kick-out-member/{memberId}', [PagesController::class, 'kickOutUser']);




       /*  <<< --- IAccount --->>> */

       //Create IAccount
       Route::post('/iaccount/create', [IAccountController::class, 'createIAccount'])
              ->name('createIAccount.iaccount');
       //get random group Post
       Route::get('iaccount/randomposts', [IAccountController::class, 'getRandomIaccountPosts']);
    
       // get pages where auth user is admin
       Route::get('/get-your-iaccounts', [IAccountController::class, 'getYourIaccounts']);
       // get liked iaccounts
       Route::get('/get-iaccounts-liked', [IAccountController::class, 'getLikedIaccount']);
       //get specific IAccount 
       Route::get('/iaccountdetails/{id}', [IAccountController::class, 'iaccountDetails']);
       /* get specific group posts */
       Route::get('/getspecificiaccountposts', [IAccountController::class, 'getSpecificIaccountPosts']);
       //get specific group photo
       Route::get('/getspecificichannelphotos', [IAccountController::class, 'getSpecificIAccountPhotos']);
       // view specific user friend list
       Route::get('/getspecificiaccountfollowerids', [IAccountController::class, 'getIAccountFollowrDetails']);
       // Route to join iaccount
       Route::post('iaccount/join/{iChannelId}', [IAccountController::class, 'joinIAccount']);
       // Route to leave iaccount
       Route::post('iaccount/leave/{iChannelId}', [IAccountController::class, 'leaveIAccount']);
       //upadete iaccount name 
       Route::put('/iaccount/{iChannelId}/update/name', [IAccountController::class, 'updateIAccountName']);
       // Create page Post
       Route::post('iaccount/post/create', [PostsController::class, 'createIAccountPost']);



    
    
    
    
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
       Route::post('/posts/{postId}/comments', [CommentsController::class, 'createPostCommment']);
       

       //get specific post comments     
       Route::get('/posts/{postId}/comments', [CommentsController::class, 'getComments']);



       //delete specific comments     
       Route::delete('/post/{postId}/deletecomment', [CommentsController::class, 'deleteComment'])
              ->name('deleteComment.comment');




       /*  <<< --- Replies --->>> */

       //Reply any Comment  
              //create comment to any post      
              Route::post('/comments/{commentId}/replies', [ReplyController::class, 'createCommmentReply']);
           //create replies to any reply      
            Route::post('/reply/{commentId}/replies', [ReplyController::class, 'createRepliesToReply']);
           //get specific post comments     
           Route::get('/comments/{commentId}/replies', [ReplyController::class, 'getReplies']);




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


     /*  Quiz */
     Route::post('/random-hadith', [QuizGameController::class, 'getRandomHadith']);
     Route::post('get-random-question/{hadithId}', [QuizGameController::class, 'getRandomQuestionAnswerSet']);
     Route::post('check-answer', [QuizGameController::class, 'checkAnswer']);
     Route::get('get-current-story', [QuizGameController::class, 'getCurrentStory']);


   /*  Love post */
   Route::post('/toggle-love/{loveOnType}/{loveOnId}', [LoveController::class, 'toggleLove']);


 /*  Unlike */
   /*  Unlike post */
   Route::post('/toggle-unlike/{unlikeOnType}/{unlikeOnId}', [LoveController::class, 'toggleUnlike']);



    /* One to One Messgae */
    Route::post('/chatmessage', [ChatController::class, 'sendMessage']);
    Route::get('/loadchat', [ChatController::class, 'loadChat']);
// In routes/api.php
Route::delete('/message/delete', [ChatController::class, 'deleteMessage']);



 








});
       