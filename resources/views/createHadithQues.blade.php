<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Hadith Ques</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }

        .container {
            background-color: #fff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        h1,
        h2 {
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .question {
            border: 1px solid #ccc;
            border-radius: 5px;
            padding: 15px;
        }

        .form-check-label {
            margin-bottom: 5px;
        }

        #add-question {
            margin-top: 10px;
            margin-right: 10px;
        }

        #submit-btn {
            margin-top: 10px;
        }

        /* Color customization */
        label[for="hadith"],
        label[for^="question"],
        input[name^="questions"],
        input[name^="currectAnswers"],
        input[name^="wrongAnswers"] {
            color: #007bff;
            /* Blue color for hadith, question, and answer fields */
        }

    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        {{-- show success message --}}
        @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
        @endif

        <h1 class="mb-4">Make Hadith Questions</h1>

        <div class="form-group">
            <label for="hadith">Hadith:</label>
            <p id="hadith" name="hadith">{{ $randomHadith->hadith ?? 'No Hadith available' }}</p>
        </div>

        <form id="hadith-form" method="POST" action="{{ route('createhadithandques') }}">
            @csrf
            <input type="hidden" name="hadith_id" value="{{ $randomHadith->hadith_id ?? '' }}">
            <div class="d-flex justify-content-start mb-3">
                <button type="button" class="btn btn-primary mr-2" id="add-question">Add Question</button>
                <button type="submit" class="btn btn-success mr-2" id="submit-btn">Submit</button>
                <button type="button" class="btn btn-danger mr-2 ml-5" id="too-short-btn">Too Short</button>
            </div>
            <h2>Questions</h2>
            <div id="questions-container">
                <!-- Default Questions -->
                <div class="form-group question">
                    <label>Question 1:</label>
                    <input type="text" class="form-control" name="questions[]" required placeholder="Enter your question here...">
                    <div class="form-check mt-2">
                        <input type="text" class="form-control ml-2" name="currectAnswers[]" required placeholder="Correct answer...">
                    </div>
                    <div class="form-check mt-2">
                        <input type="text" class="form-control ml-2" name="wrongAnswers[]" required placeholder="Wrong answer...">
                    </div>
                </div>
            </div>
        </form>

        <form id="too-short-form" method="POST" action="{{ route('mark.hadith.short') }}" class="d-none">
            @csrf
            <input type="hidden" name="hadith_id" value="{{ $randomHadith->hadith_id ?? '' }}">
        </form>
    </div>

    <script>
        $(document).ready(function() {
            let questionCount = 1;

            $('#add-question').click(function() {
                questionCount++;
                let questionHtml = `
                <div class="form-group question">
                    <label>Question ${questionCount}:</label>
                    <input type="text" class="form-control" name="questions[]" required placeholder="Enter your question here...">
                    <div class="form-check mt-2">
                        <input type="text" class="form-control ml-2" name="currectAnswers[]" required placeholder="Correct answer...">
                    </div>
                    <div class="form-check mt-2">
                        <input type="text" class="form-control ml-2" name="wrongAnswers[]" required placeholder="Wrong answer...">
                    </div>
                </div>`;
                $('#questions-container').prepend(questionHtml); // Prepend instead of append

                // Enable submit button if at least three questions are added
                if (questionCount >= 3) {
                    $('#submit-btn').prop('disabled', false);
                }
            });

            // Validation to prevent form submission if less than three questions are added
            $('#hadith-form').submit(function(event) {
                if (questionCount < 1) {
                    event.preventDefault();
                    alert('Please add at least three questions.');
                }
            });

            $('#too-short-btn').click(function() {
                $('#too-short-form').submit();
            });
        });

    </script>

</body>
</html>
