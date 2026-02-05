<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Story</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        /* Add custom styles here if needed */
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Random Story</h1>
        <hr>
        @if(isset($randomStory))
            <div class="card mt-4">
                <div class="card-body">
                    <p class="card-title">{{ $randomStory->story }}</p>
                    <!-- Button to redirect to view questions -->
                    <a href="{{ route('view-questions', ['story_id' => $randomStory->story_id ]) }}" class="btn btn-primary">Ready to Answer</a>
                </div>
            </div>
        @else
            <p class="mt-4">No story found.</p>
        @endif
    </div>
</body>
</html>
