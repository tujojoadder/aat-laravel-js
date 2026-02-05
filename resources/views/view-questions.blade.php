<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions</title>
    <!-- Bootstrap CSS -->
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background-color: #f8f9fa;">
    <div class="container mt-2">
        <h1 class="text-center mb-4 bg-dark p-3" style="color: #00ffb3;">Questions</h1>
        <hr>
        <h1>{{ $story_id }}</h1>
        <form method="POST" action="{{ route('submit-answers', ['story_id' => $story_id]) }}">
            @csrf
            <div class="card mt-1" style="background-color: #ffffff;">
                <div class="card-body">
                  
                    @foreach($questions as $index => $question)
                        <div class="mb-1">
                            <h4 class="text-secondary">Question {{ $index + 1 }}</h4>
                            <p><strong>Question:</strong> {{ $question->question }}</p>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answer[{{ $question->question_id }}]" id="option1_{{ $index }}" value="{{ $question->ans1 }}">
                                <label class="form-check-label" for="option1_{{ $index }}">
                                    {{ $question->ans1 }}
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="answer[{{ $question->question_id }}]" id="option2_{{ $index }}" value="{{ $question->ans2 }}">
                                <label class="form-check-label" for="option2_{{ $index }}">
                                    {{ $question->ans2 }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn-lg btn btn-outline-secondary mt-4 " >Submit Answers</button>
            </div>
        </form>
    </div>
</body>
</html>
