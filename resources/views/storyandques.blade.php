<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Story</title>
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

        h1, h2 {
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
        label[for="story"],
        label[for^="question"],
        input[name^="questions"],
        input[name^="currectAnswers"],
        input[name^="wrongAnswers"] {
            color: #007bff; /* Blue color for story, question, and answer fields */
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Submit Story</h1>
        @if (isset($message))
        <div class="alert alert-success">{{ $message }}</div>
        @endif
       
        <form id="story-form" method="POST"  action="{{ route('createStoryAndQues.story') }}">
            @csrf
            <div class="form-group">
                <label for="story">Story:</label>
                <textarea class="form-control" id="story" name="story" rows="5" required placeholder="Write your story here..."></textarea>
            </div>
            <div class="d-flex justify-content-start mb-3">
                <button type="button" class="btn btn-primary mr-2" id="add-question">Add Question</button>
                <button type="submit" class="btn btn-success" id="submit-btn" disabled>Submit</button>
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
    </div>
    

    <script>
        $(document).ready(function() {
            let questionCount = 1;
    
            $('#add-question').click(function(){
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
            $('#story-form').submit(function(event) {
                if (questionCount < 3) {
                    event.preventDefault();
                    alert('Please add at least three questions.');
                }
            });
        });
    </script>
    
</body>
</html>
