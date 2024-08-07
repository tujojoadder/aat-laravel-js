<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Hadith</title>
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

      
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#hadith-form').on('submit', function(event) {
                $('#submit-btn').attr('disabled', true);
            });
        });
    </script>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Insert Hadith</h1>
        @if (session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
        @endif
       
        <form id="hadith-form" method="POST" action="{{ route('hadithInsert') }}">
            @csrf
            <div class="form-group">
                <label for="hadith">Hadith:</label>
                <textarea class="form-control" id="hadith" name="hadith" rows="5" required placeholder="Write your hadith here..."></textarea>
            </div>
            <div class="d-flex justify-content-start mb-3">
                <button type="submit" class="btn btn-success" id="submit-btn">Submit</button>
            </div>
        </form>
    </div>
</body>
</html>
