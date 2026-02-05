<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            height: 100vh;
        }

        .users-list {
            flex: 1;
            background-color: #f0f0f0;
            overflow-y: auto;
            padding: 20px;
        }

        .users-list h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .user-item {
            list-style: none;
            margin: 0;
            padding: 0;
            cursor: pointer;
            padding: 10px 0;
            border-bottom: 1px solid #ccc;
        }

        .user-item:last-child {
            border-bottom: none;
        }

        .user-item:hover {
            background-color: #e0e0e0;
        }

        .message-box {
            flex: 2;
            background-color: #fff;
            overflow-y: auto;
            padding: 20px;
        }

        .message-input {
            width: calc(100% - 100px);
            margin-right: 10px;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .send-button {
            width: 100px;
            padding: 10px;
            font-size: 16px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .send-button:hover {
            background-color: #0056b3;
        }

        .message {
            margin-bottom: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }

        .message p {
            margin: 0;
        }

        .chat-box{
            width:90%;
            height: 60vh;
            background-color: #97aec7b3
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="users-list">
            <h2>Users</h2>
            <ul>
                <li class="user-item">User 1</li>
                <li class="user-item">User 2</li>
                <li class="user-item">User 3</li>
                <!-- Add more users as needed -->
            </ul>
        </div>
        <div class="message-box">
            
<div class="chat-box">


</div>

<p></p>
            <div class="chat-form">
                <input type="text" class="message-input" placeholder="Type your message...">
                <p></p>
                <button class="send-button">Send</button>
            </div>
        </div>
    </div>
</body>
</html>
