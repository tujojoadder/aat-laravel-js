<?php

namespace App\Http\Controllers;

use App\Models\DayHadith;
use App\Models\DayLike;
use App\Models\QuestionAnswerSet;
use App\Models\Hadith;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HadithController extends Controller
{

    /* Insert hadith */
    public function hadithInsert(Request $request)
    {
        // Validate the form input
        $this->validate($request, [
            'hadith' => 'required|string',
        ]);

        // Clean input data
        $hadith = cleanInput($request->hadith);

        // Create unique id for hadith
        $hadithId = Str::uuid();

        // Use DB transaction
        DB::transaction(function () use ($hadith, $hadithId) {
            // Store the hadith
            $data = [
                'hadith_id' => $hadithId,
                'hadith' => $hadith,
            ];
            Hadith::create($data);
        });

        // Redirect back with success message
        return redirect()->back()->with('message', 'Hadith submitted successfully!');
    }





    /* make hadith too sort for make ques */
    public function markHadithAsShort(Request $request)
    {
        $hadithId = $request->input('hadith_id');
        $hadith = Hadith::find($hadithId);
        if ($hadith) {
            $hadith->has_ques = 'too_sort';
            $hadith->save();
        }

        // Redirect back with success message
        return redirect()->back()->with('message', 'Hadith is seted as too_sort');
    }

    public function createHadithQues(Request $request)
    {
        // Validate the form input
        $this->validate($request, [
            'hadith_id' => 'required|string',
            'questions.*' => 'required|string',
            'currectAnswers.*' => 'required|string',
            'wrongAnswers.*' => 'required|string',
        ]);

        // Clean input data
        $hadithId = cleanInput($request->hadith_id);
        $questions = $request->questions;
        $currectAnswers = $request->currectAnswers;
        $wrongAnswers = $request->wrongAnswers;


        // Use DB transaction
        DB::transaction(function () use ($hadithId, $questions, $currectAnswers, $wrongAnswers) {

            // Store the questions and answers
            foreach ($questions as $key => $question) {
                $quesId = Str::uuid();

                $questionData = [
                    'question_id' => $quesId,
                    'hadith_id' => $hadithId,
                    'question' => $question,
                    'correct_ans' => $currectAnswers[$key],
                    'wrong_ans' => $wrongAnswers[$key],
                ];
                // Assuming you have a Question model
                QuestionAnswerSet::create($questionData);
            }
        });

        return redirect()->back()->with('message', 'Hadith question inserted successfully');
    }




    public function viewQuestions($hadith_id)
    {
        // Fetch the questions for the hadith
        $questions = QuestionAnswerSet::where('hadith_id', $hadith_id)->get();

        // Shuffle the answers for each question
        $questions->each(function ($question) {
            $answers = collect([$question->correct_ans, $question->wrong_ans])->shuffle();
            $question->ans1 = $answers->shift(); // First answer
            $question->ans2 = $answers->shift(); // Second answer
        });

        return view('view-questions', compact('questions', 'hadith_id'));
    }


    public function submitAnswers(Request $request, $hadith_id)
    {
        // Retrieve submitted answers from the request
        $submittedAnswers = $request->input('answer');

        // Fetch the hadith along with its associated question-answer sets
        $hadith = Hadith::with('questionAnswerSets')->find($hadith_id);

        // Initialize a variable to count the correct answers
        $correctAnswers = 0;

        // Loop through each question-answer set in the hadith

        foreach ($hadith->questionAnswerSets as $questionAnswerSet) {
            // Check if the user has submitted an answer for this question
            if (isset($submittedAnswers[$questionAnswerSet->question_id])) {
                // Compare the submitted answer with the correct answer for this question
                if ($submittedAnswers[$questionAnswerSet->question_id] == $questionAnswerSet->correct_ans) {
                    // If the answers match, increment the count of correct answers
                    $correctAnswers++;
                }
            }
        }

        // Now you have the count of correct answers, you can do whatever you want with it
        // For example, you can return a view with the result
        return response()->json(['message' => $correctAnswers]);
    }




    /*<---- Random hadith ----> */
    /*  for laravel js  ->web.php*/
    public function showRandomHadith()
    {
        $randomHadith = Hadith::where('has_ques', 'no')->inRandomOrder()->first();
        return view('createHadithQues', compact('randomHadith'));
    }

    /*  for react js  ->app.php*/
    public function getRandomHadith()
    {
        $randomHadith = Hadith::inRandomOrder()->first();
        return response()->json(['data' => $randomHadith]);
    }


    /* set day hadith */

    public function setDayHadith(Request $request)
    {
        // Get Authenticated user
        $user = auth()->user();
    
        // Validate request data
        $validatedData = $request->validate([
            'hadith_id' => 'required|exists:hadith,hadith_id',
        ]);
    
        // Clean the input
        $hadith_id = cleanInput($request->hadith_id);
    
        // Use a transaction for database operations
        DB::transaction(function () use ($validatedData, $user, $hadith_id) {
            // Delete any existing record for this user
            DayHadith::where('user_id', $user->user_id)->delete();
    
            // Insert data into the 'day_hadiths' table
            DayHadith::create([
                'day_hadith_id' => (string) Str::uuid(), // Generate a UUID for the primary key
                'hadith_id' => $hadith_id,
                'user_id' => $user->user_id, // Use the authenticated user's ID
            ]);
        });
    
        // Return a response (e.g., a success message)
        return response()->json(['message' => 'Day Hadith added successfully'], 201);
    }
    

/* Get all user day hadith */
public function getDayHadiths()
{
    $user = auth()->user();

    // Retrieve all users except the currently authenticated user in random order
    $otherUsers = User::where('user_id', '!=', $user->user_id)
        ->with('dayHadith.hadith') // Eager load the dayHadith and related Hadith
        ->inRandomOrder() // Randomize the order of the results
        ->get();

    return response()->json([
        'data' => $otherUsers
    ]);
}




/* like day hadith */
public function likeDayHadith(Request $request)
{
    // Get Authenticated user
    $user = auth()->user();

    // Validate request data
    $validatedData = $request->validate([
        'day_hadith_id' => 'required|exists:day_hadiths,day_hadith_id',
        // Add more validations here
    ]);

    // Clean all input variables
    $cleanedData = [
        'day_hadith_id' => cleanInput($request->day_hadith_id),
        // Add other variables here if needed
    ];

    // Use a transaction for database operations
    DB::transaction(function () use ($cleanedData, $user) {
        // Check if a like already exists for the same user_id and day_hadith_id
        $existingLike = DayLike::where('day_hadith_id', $cleanedData['day_hadith_id'])
                               ->where('user_id', $user->user_id)
                               ->first();

        // If an existing like is found, delete it
        if ($existingLike) {
            $existingLike->delete();
        }

        // Insert a new like
        DayLike::create([
            'day_likes_id' => Str::uuid(), // Generate a UUID for the primary key
            'day_hadith_id' => $cleanedData['day_hadith_id'],
            'user_id' => $user->user_id, // Use the authenticated user's ID
        ]);
    });

    // Return a response (e.g., a success message)
    return response()->json(['message' => 'Like successful'], 201);
}

}
