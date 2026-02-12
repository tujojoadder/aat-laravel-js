<?php

use App\Http\Controllers\DemoRegisterController;
use App\Http\Controllers\HadithController;
use App\Http\Controllers\IAccountController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;



/* <--- Hadith ---> */

/* insert hadith */

Route::get('/hadith/insert', function () {
    return view('insertHadith');
});

/* Create hadith ques */
Route::get('/createhadithques', [HadithController::class, 'showRandomHadith'])
    ->name('createhadithques');

/* Make hadith sort */
Route::post('/mark-hadith-short', [HadithController::class, 'markHadithAsShort'])->name('mark.hadith.short');




//submit answer
Route::post('/submitanswers/{story_id}', [HadithController::class, 'submitAnswers'])
    ->name('submit-answers');
//get random Story
Route::get('/randomstory', [HadithController::class, 'showRandomStory'])
    ->name('showRandomStory');
//get ques for story   
Route::get('/view-questions/{story_id}', [HadithController::class, 'viewQuestions'])
    ->name('view-questions');



//language change
Route::get('lang/home', [IAccountController::class, 'index']);
Route::get('lang/change', [IAccountController::class, 'change'])->name('changeLang');
//Get Reports that whitch user ger roported for how many model
Route::get('/report', [ReportController::class, 'higetUsersWithReports']);




Route::get('/', function () {
    return view('welcome');
});


Route::get('/login', function () {
    return view('login');
});
Route::get('/googleLogin', [LoginController::class, 'googleLogin']);
Route::get('/auth/google/callback', [LoginController::class, 'googleHandle']);



/* Route::get('storage:link', function () {
    $storageLink = public_path('storage');

    // Delete if it exists
    if (File::exists($storageLink)) {
        File::deleteDirectory($storageLink); // safely removes symlink or folder
    }

    // Create storage link
    Artisan::call('storage:link');

    return "Storage link recreated successfully.";
});
 */

Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:cache');

    return "Cache cleared";
});

Route::get('/generate-docs', function () {

    Artisan::call('scribe:generate');

    return "Docs Generated";
});
