<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>Home</h1>
    <p>{{ $message }}</p>
    <p>{{ $token }}</p>
    
    <script>
        // Retrieve the token from Blade template and store it in local storage
        let token = "{{ $token }}";
        localStorage.setItem('userToken', token);

    </script>

</body>
</html>