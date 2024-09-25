@extends('layouts.app')
@vite('resources/js/app')

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <div style="text-align: center;background-color:aquamarine; padding:5px;">
        <h3 class="text-light">Insert picture on Fprofile Picture table</h3>
        <form method="POST" enctype="multipart/form-data" action="{{ route('fpstore') }}">
            @csrf
            <input type="file" name="image" id="image">
            <button type="submit" class="btn-primary btn">Submit</button>
        </form>
    </div>
    <div class="text-center bg-secondary my-2 p-3">
        <a href="{{ route('viewfp') }}" class="btn btn-primary text-center">Change Fprofile</a>
    </div>
    <div class="text-center bg-secondary my-2 p-3">
        <a href="{{ route('viewmp') }}" class="btn btn-primary text-center">Change Mprofile</a>
    </div>
    <div class="text-center bg-secondary my-2 p-3">
        <a href="{{ route('viewcp') }}" class="btn btn-primary text-center">Change Coverphoto for user</a>
    </div>




    <script defer>
        document.addEventListener('DOMContentLoaded', function() {
            Echo.channel('trade').listen('NewTrade', (e) => {
                console.log(e);
            });
        });
    
        document.addEventListener('DOMContentLoaded', function() {
            Echo.private('privateName').listen('PrivateTrade', (e) => {
                console.log(e);
            });
        });
    
        document.addEventListener('DOMContentLoaded', function() {
            Echo.join('track-user')
                .here((users) => {
                    console.log('Currently present users:', users);
                })
                .joining((user) => {
                    console.log('User joined:', user);
                })
                .leaving((user) => {
                    console.log('User left:', user);
                })      
                .listen('.custom-name', (e) => {
                    console.log(e);
                });
        });
    
        document.addEventListener('DOMContentLoaded', function() {
            Echo.join('track-channel').here((users) => {
                    console.log('Currently present users:', users);
                })
                .joining((user) => {
                    console.log('User joined:', user);
                })
                .leaving((user) => {
                    console.log('User left:', user);
                }).listen('Track', (e) => {
                console.log(e);
            });
        });



/* Hello event */
document.addEventListener('DOMContentLoaded', function() {
            Echo.channel('ab').listen('Hello', (e) => {
                console.log(e);
                console.log('oooo')
            });
        });

    </script>
    
</body>
</html>
