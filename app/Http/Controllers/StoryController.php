<?php

namespace App\Http\Controllers;

use App\Models\QuestionAnswerSet;
use App\Models\Story;
use App\Models\UniqeUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StoryController extends Controller
{
    public function createStoryAndQues(Request $request)
    {
        // Validate the form input
        $this->validate($request, [
            'story' => 'required|string',
            'questions.*' => 'required|string',
            'currectAnswers.*' => 'required|string',
            'wrongAnswers.*' => 'required|string',
        ]);

        // Clean input data
        $story = cleanInput($request->story);
        $questions = $request->questions;
        $currectAnswers = $request->currectAnswers;
        $wrongAnswers = $request->wrongAnswers;

        // Verify minimum 3 questions
        if (count($questions) < 3) {
            return redirect()->back()->withErrors(['message' => 'Please provide at least 3 questions.']);
        }

        // Create unique id for story
        $storyId = Str::uuid();

        // Use DB transaction
        DB::transaction(function () use ($story, $storyId, $questions, $currectAnswers, $wrongAnswers) {
            // Store the story
            $data = [
                'story_id' => $storyId,
                'story' => $story,
            ];
            Story::create($data);

            // Store the questions and answers
            foreach ($questions as $key => $question) {
                $quesId = Str::uuid();

                $questionData = [
                    'question_id' => $quesId,
                    'story_id' => $storyId,
                    'question' => $question,
                    'correct_ans' => $currectAnswers[$key],
                    'wrong_ans' => $wrongAnswers[$key],
                ];
                // Assuming you have a Question model
                QuestionAnswerSet::create($questionData);
            }
        });

        return redirect()->back()->with('message', 'Story inserted successfully');
    }

    public function showRandomStory()
    {
        $randomStory = Story::inRandomOrder()->first();

        // You may add error handling if no story is found
        return view('randomstory', compact('randomStory'));
    }

    public function viewQuestions($story_id)
    {
        // Fetch the questions for the story
        $questions = QuestionAnswerSet::where('story_id', $story_id)->get();

        // Shuffle the answers for each question
        $questions->each(function ($question) {
            $answers = collect([$question->correct_ans, $question->wrong_ans])->shuffle();
            $question->ans1 = $answers->shift(); // First answer
            $question->ans2 = $answers->shift(); // Second answer
        });

        return view('view-questions', compact('questions', 'story_id'));
    }

    
public function submitAnswers(Request $request, $story_id)
{
    // Retrieve submitted answers from the request
    $submittedAnswers = $request->input('answer');
    
    // Fetch the story along with its associated question-answer sets
    $story = Story::with('questionAnswerSets')->find($story_id);

    // Initialize a variable to count the correct answers
    $correctAnswers = 0;

    // Loop through each question-answer set in the story
    
    foreach ($story->questionAnswerSets as $questionAnswerSet) {
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
}
