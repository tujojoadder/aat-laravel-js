<?php

namespace App\Http\Controllers;

use App\Models\CurrentStory;
use App\Models\Hadith;
use App\Models\QuestionAnswerSet;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuizGameController extends Controller
{

    //Get random hadith which have ques
    public function getRandomHadith(Request $request)
    {
        // Begin a database transaction
        return DB::transaction(function () use ($request) {
            // Fetch a random hadith where has_ques is 'yes'
            $hadithWithQuestions = Hadith::select('hadith_id', 'hadith', 'book') // Select relevant fields
                ->where('has_ques', 'yes')
                ->inRandomOrder() // Order by random
                ->first(); // Fetch a single random hadith

            if ($hadithWithQuestions) {
                // Get the user_id from the authenticated user
                $userId = auth()->user()->user_id;

                // Create a new record in the 'current_story' table
                CurrentStory::create([
                    'current_story_id' => Str::uuid(), // Generate a unique ID
                    'user_id'          => $userId,
                    'story_id'         => $hadithWithQuestions->hadith_id, // Use the hadith_id as story_id
                    'reading'          => false, // Default to false
                ]);

                // If all operations are successful, return the response
                return response()->json([
                    'hadith' => $hadithWithQuestions,
                    'message' => 'Current story record created',
                ]);
            }

            // If no hadith is found, return a 404 error
            return response()->json([
                'message' => 'No hadith found with questions'
            ], 404);
        });
    }


    // In your controller
    public function getRandomQuestionAnswerSet(Request $request, $hadithId)
    {
        // The $hadithId is automatically passed from the route
        $hadithId = cleanInput($hadithId);
        //update reading true 
        $currentStory = CurrentStory::where('story_id', $hadithId)->first();

        if ($currentStory) {
            // Update the 'reading' field to true in a simple and direct way
            $currentStory->update(['reading' => true]);
        }
        // Query the QuestionAnswerSet model to get a random record based on hadith_id
        $questionAnswerSet = QuestionAnswerSet::where('hadith_id', $hadithId)
            ->select('question_id', 'question', 'hadith_id', 'wrong_ans', 'correct_ans')
            ->inRandomOrder()
            ->first();

        if ($questionAnswerSet) {
            $answers = [
                'first_ans' => $questionAnswerSet->wrong_ans,
                'second_ans' => $questionAnswerSet->correct_ans
            ];

            // Randomize the order of wrong and correct answers
            if (rand(0, 1)) {
                $answers = [
                    'first_ans' => $questionAnswerSet->correct_ans,
                    'second_ans' => $questionAnswerSet->wrong_ans
                ];
            }

            // Response data
            $response = [
                'question_id' => $questionAnswerSet->question_id,
                'question' => $questionAnswerSet->question,
                'hadith_id' => $questionAnswerSet->hadith_id,
                'first_ans' => $answers['first_ans'],
                'second_ans' => $answers['second_ans']
            ];

            return response()->json($response);
        } else {
            return response()->json(['error' => 'No data found for the provided hadith ID'], 404);
        }
    }




    public function checkAnswer(Request $request)
    {
        $user = auth()->user();

        // Validate the incoming request
        $validatedData = $request->validate([
            'question_id' => 'required|string',
            'selected_answer' => 'required|string',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Retrieve the question from the database
            $question = QuestionAnswerSet::where('question_id', $validatedData['question_id'])->first();
            $hadithId = $question->hadith_id;
            // Check if the question exists
            if (!$question) {
                // Rollback the transaction if the question is not found
                DB::rollBack();
                return response()->json(['error' => 'Invalid question ID'], 404);
            }

            // Compare the selected answer with the correct answer
            $isCorrect = $question->correct_ans === $validatedData['selected_answer'];

            if ($isCorrect) {
                // Increment the user's quiz points if correct
                $user->increment('total_quiz_point', 5);
            } else {
                // Decrement the user's quiz points if incorrect, but ensure the value doesn't go below 0
                $user->total_quiz_point = max($user->total_quiz_point - 50, 0);
                $user->save(); // Save the updated total_quiz_point
            }

            // Prepare the response data
            $response = [
                'question_id' => $question->question_id,
                'is_correct' => $isCorrect,
                'correct_answer' => $question->correct_ans,
            ];
            //Delete record from current story table
            $currentStory = CurrentStory::where('story_id', $hadithId)->first();
           
           if ($currentStory) {
            $currentStory->delete();
           }
         
            // Commit the transaction
            DB::commit();

            // Return the response as JSON
            return response()->json($response);
        } catch (\Exception $e) {
            // Rollback the transaction if an error occurs
            DB::rollBack();
            return response()->json(['error' => 'An error occurred while processing your request.'], 500);
        }
    }


    public function getCurrentStory(Request $request)
    {
        // Get Auth user
        $user = auth()->user();

        // Fetch the current story for the user
        $currentStory = CurrentStory::where('user_id', $user->user_id)->first();

        if ($currentStory) {
            // If reading is true, return question and answer
            if ($currentStory->reading) {
                // Fetch a random question and answer set based on the current story's hadith_id
                $questionAnswerSet = QuestionAnswerSet::where('hadith_id', $currentStory->story_id)
                    ->select('question_id', 'question', 'hadith_id', 'wrong_ans', 'correct_ans')
                    ->inRandomOrder()
                    ->first();

                // If a question and answer set is found
                if ($questionAnswerSet) {
                    // Prepare answers and randomize their order
                    $answers = [
                        'first_ans' => $questionAnswerSet->wrong_ans,
                        'second_ans' => $questionAnswerSet->correct_ans
                    ];

                    if (rand(0, 1)) {
                        $answers = [
                            'first_ans' => $questionAnswerSet->correct_ans,
                            'second_ans' => $questionAnswerSet->wrong_ans
                        ];
                    }

                    // Prepare the response with the question and answers
                    $response = [
                        'question_id' => $questionAnswerSet->question_id,
                        'question' => $questionAnswerSet->question,
                        'hadith_id' => $questionAnswerSet->hadith_id,
                        'first_ans' => $answers['first_ans'],
                        'second_ans' => $answers['second_ans'],
                        'reading' => 'yes',


                    ];

                    return response()->json($response);
                } else {
                    // No question-answer set found for the current hadith_id
                    return response()->json(['error' => 'No question and answer found for the provided hadith ID'], 404);
                }
            } else {
                // If reading is false, return the hadith story
                $hadith = Hadith::where('hadith_id', $currentStory->story_id)->first();

                // Merge hadith fields into the currentStory object
                if ($hadith) {
                    $currentStory->hadith_text = $hadith->hadith;
                    $currentStory->hadith_id = $hadith->hadith_id;
                    $currentStory->reading = 'no';
                }

                // Return response with the currentStory containing hadith
                return response()->json($currentStory);
            }
        }else{
            $response=[
                'reading' => 'none',
            ];
            return response()->json($response);

        }
    }
}
