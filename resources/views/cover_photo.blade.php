@extends('layouts.app')

@section('content')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .img-container {
            margin-bottom: 20px;
            /* Add bottom margin for spacing between images */
            position: relative;
            /* Enable positioning for the button */
        }

        .img-container img {
            width: 100%;
            /* Set the width of the image to fill its container */
            max-height: 300px;
            /* Set a maximum height for the images */
            object-fit: cover;
            /* Ensure the image covers the container without stretching */
            display: block;
            /* Ensure the image aligns properly within its container */
            border-radius: 8px;
            /* Add border radius for a rounded look */
            transition: transform 0.3s ease;
            /* Add transition for smooth hover effect */
        }

        .img-container:hover img {
            transform: scale(1.1);
            /* Scale the image slightly on hover for a zoom effect */
        }

        .btn-set-profile {
            position: absolute;
            /* Position the button absolutely within the container */
            bottom: 10px;
            /* Add bottom spacing for the button */
            left: 50%;
            /* Center the button horizontally */
            transform: translateX(-50%);
            /* Center the button horizontally */
        }

    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            @if(isset($CoverPhotos) && count($CoverPhotos) > 0)
            @foreach ($CoverPhotos as $CoverPhoto)
            <div class="col-md-4">
                <div class="img-container text-center">
                    <img src="{{ $CoverPhoto->image_url }}" class="img-fluid" alt="CoverPhoto">
                    <button class="btn btn-danger btn-set-profile" onclick="setAsCover('{{ $CoverPhoto->image_url }}')">Set as Cover</button>
                </div>
            </div>
            @endforeach
            @else
            <div class="col-md-12 text-center">
                <p>No Cover Photo found.</p>
            </div>
            @endif
        </div>

        <!-- Display the message if it exists -->
        @if (isset($message))
        <div class="alert alert-success">{{ $message }}</div>
        @endif

        <!-- Form for deleting all pictures -->
        <form action="{{ route('cp.delete') }}" method="post" class="mb-3">
            @csrf

            @method('delete')

            <button type="submit" class="btn btn-danger btn-delete-all">Delete all Pictures</button>
        </form>

        <!-- Form for setting a profile picture -->
        <form id="cover_form" action="{{ route('setcp') }}" method="POST">
            @csrf
            <input type="hidden" name="image" id="cover_photo_url">
            <input type="hidden" name="user_id" value="b8a7ffe1-624e-41e1-bfc0-46bf9700ba94">
        </form>
    </div>

    <script>
        function setAsCover(imageUrl) {
            // Set the image URL in the hidden input field
            document.getElementById('cover_photo_url').value = imageUrl;
            // Submit the form
            document.getElementById('cover_form').submit();
        }

    </script>
</body>
</html>
@endsection
