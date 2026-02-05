<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\UniqeUser;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\Relation;

class ReportController extends Controller
{
    // ReportOnType
    /* Relation::morphMap([
    'user'=>'App\Models\User',
    'page'=>'App\Models\Pages',
    'group'=>'App\Models\Groups',
    'iaccount'=>'App\Models\IAccount',
    'post'=>'App\Models\Posts',
    'comment'=>'App\Models\Comments',
    'reply'=>'App\Models\Replies',
]); */
public function createReport(Request $request, $ReportOnType, $ReportOnId)
{
    // Get Auth user
    $user = auth()->user();
    $userId = $user->user_id;
    $request->merge(['ReportOnType' => $ReportOnType]);
    $request->merge(['ReportOnId' => $ReportOnId]);
    $reportType = $request->report_type;
    $reportCategory = $request->report_category;
    $request->validate([
        'ReportOnType' => 'required|string|max:15',
        'ReportOnId' => 'required|string|max:50',
    ]);
    $ReportOnType = cleanInput($ReportOnType);
    $ReportOnId = cleanInput($ReportOnId);
    $modelClass = Relation::getMorphedModel($ReportOnType);

    if (class_exists($modelClass)) {
        $record = $modelClass::find($ReportOnId);

        if (!$record) {
            return response()->json(['message' => 'Reported item not found']);
        }

        // Check if a similar report already exists
        $existingReport = Report::where('report_on_type', $ReportOnType)
            ->where('report_on_id', $ReportOnId)
            ->where('report_by_id', $userId)
            ->where('report_type', $reportType)
            ->where('report_category', $reportCategory)
            ->first();

        if ($existingReport) {
            return response()->json(['message' => "You have already reported this reason."]);
        }

        // Use database transaction to ensure atomicity
        DB::transaction(function () use ($reportType, $reportCategory, $ReportOnType, $ReportOnId, $userId, $record) {
            // Make Report
            $reportId = Str::uuid();
            $reportData = [
                'report_id' => $reportId,
                'report_on_type' => $ReportOnType,
                'report_on_id' => $ReportOnId,
                'report_by_id' => $userId,
                'report_type' => $reportType,
                'report_category' => $reportCategory,
            ];
            Report::create($reportData);

            // Increment reported count
            $record->increment('reported_count');
        });

        return response()->json(['message' => "Report successfully sent"]);
    } else {
        return response()->json(['message' => 'Invalid Report']);
    }
}











public function higetUsersWithReports()
{
    // Define the relationships to check
    $relationships = [
        'post' => 'author_id',
        'group' => 'group_creator',
        'page' => 'page_creator',
        'iaccount' => 'iaccount_creator',
        'comment' => 'commenter_id',
        'reply' => 'replied_by_id',
    ];

    // Initialize an empty collection to store users with reported counts
    $usersWithReports = collect();

    // Retrieve all users
    $allUsers = User::all();

    // Loop through each user
    foreach ($allUsers as $user) {
        // Initialize an empty array to store reported counts for each model
        $reportedCounts = [
            'post' => 0,
            'group' => 0,
            'page' => 0,
            'iaccount' => 0,
            'comment' => 0,
            'reply' => 0,
        ];

        // Flag to track if user has reported counts greater than 0
        $hasReportedCounts = false;

        // Loop through each relationship and retrieve reported counts for the current user
        foreach ($relationships as $relation => $foreignKey) {
            // Get the reported count for the current model
            $reportedCount = $user->{$relation}()->where('reported_count', '>', 0)->count();

            // Update the reported counts array
            $reportedCounts[$relation] = $reportedCount;

            // Check if reported count is greater than 0
            if ($reportedCount > 0) {
                $hasReportedCounts = true;
            }
        }

        // If user has reported counts, add user information with reported counts to the collection
        if ($hasReportedCounts) {
            $usersWithReports->push([
                'user_info' => $user->toArray(), // Convert user object to array
                'reported_counts' => $reportedCounts,
            ]);
        }
    }

    // Pass the data to the view and return the view
    return view('users_with_reports', compact('usersWithReports'));
}
}





/* 
public function getUsersWithReports()
{
    // Define the relationships to check
    $relationships = [
        'post' => 'author_id',
        'group' => 'group_creator',
        'page' => 'page_creator',
        'iaccount' => 'iaccount_creator',
        'comment' => 'commenter_id',
        'reply' => 'replied_by_id',
    ];

    // Initialize an empty collection to store users with reported counts
    $usersWithReports = collect();

    // Retrieve all users
    $allUsers = User::all();

    // Loop through each user
    foreach ($allUsers as $user) {
        // Initialize an empty array to store reported counts for each model
        $reportedCounts = [
            'post' => 0,
            'group' => 0,
            'page' => 0,
            'iaccount' => 0,
            'comment' => 0,
            'reply' => 0,
        ];

        // Loop through each relationship and retrieve reported counts for the current user
        foreach ($relationships as $relation => $foreignKey) {
            // Get the reported count for the current model
            $reportedCount = $user->{$relation}()->where('reported_count', '>', 0)->count();

            // Update the reported counts array
            $reportedCounts[$relation] = $reportedCount;
        }

        // Add user information with reported counts to the collection
        $usersWithReports->push([
            'user_info' => $user,
            'reported_counts' => $reportedCounts,
        ]);
    }

    // Check if any users were found
    if ($usersWithReports->isEmpty()) {
        return 'No users found with reported counts.';
    }

    // Return the collection of users with reported counts
    return $usersWithReports;
} */
