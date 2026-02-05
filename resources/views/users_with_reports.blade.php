<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users with Reports</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card-header {
            background-color: #f8f9fa;
            padding: 10px;
            display: flex;
            align-items: center;
        }
        .profile-picture {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .btn-report {
            margin-right: 10px;
        }
        .btn-report:last-child {
            margin-right: 0;
        }
        .btn-custom {
            border-color: #ccc;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-success {
            background-color: #28a745;
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4 mb-4">Users with Reports</h1>
        @foreach ($usersWithReports as $userData)
        <div class="card mb-4">
            <div class="card-header">
                <img src="{{ $userData['user_info']['profile_picture'] }}" alt="Profile Picture" class="profile-picture">
                <h5 class="mb-0">{{ $userData['user_info']['user_fname'] }} {{ $userData['user_info']['user_lname'] }}</h5>
                <div class="ml-auto">
                    @foreach ($userData['reported_counts'] as $modelName => $count)
                    <button class="btn btn-sm btn-custom btn-report {{ $count > 0 ? 'btn-danger' : 'btn-info' }}">
                        {{ ucfirst($modelName) }}: {{ $count }}
                    </button>
                    @endforeach
                </div>
            </div>
            <!-- Add card body content if necessary -->
        </div>
        @endforeach
    </div>
</body>
</html>
