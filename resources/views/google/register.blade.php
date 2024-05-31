<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Google Registration Form</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        form {
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 5px;
            width: 300px;
        }

        label {
            display: block;
            margin-bottom: 8px;
        }

        select,
        input {
            width: calc(100% - 8px); /* Adjust width for three fields in one row */
            padding: 8px;
            margin-bottom: 16px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background-color: #4caf50;
            color: #fff;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #45a049;
        }

        /* Override width for day, month, and year fields */
        #birthdate_year,
        #birthdate_month,
        #birthdate_day {
            width: calc(33% - 10px); /* Adjust width for three fields in one row */
        }
    </style>
</head>
<body>
    <form method="POST" id="registrationForm" action="{{ route('google.register.additional') }}">
        @csrf
        <label for="fname"></label>
        <input name="fname" type="text" placeholder="First Name" required>

        <label for="lname"></label>
        <input name="lname" type="text" placeholder="Last Name" required>

        <label for="password"></label>
        <input name="password" type="password" placeholder="Password" required minlength="8">


        <label for="gender">Gender:</label>
        <select name="gender" id="gender" required>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="others">Others</option>
        </select>

        {{-- <label for="birthdate">Birthdate:</label>
        <input type="date" name="birthdate" id="birthdate" required> --}}
        <label for="birthdate">Birthdate:</label>


        <select name="birthdate_year" id="birthdate_year" required>
            <option value="" disabled selected>Year</option>
            <?php
    $currentYear = date('Y');
    $startYear = $currentYear - 100; // Adjust the start year as needed
    for ($year = $currentYear; $year >= $startYear; $year--):
        ?>
            <option value="<?= $year ?>"><?= $year ?></option>
            <?php endfor; ?>
        </select>

        <select name="birthdate_month" id="birthdate_month"  required>
            <option value="" disabled selected>Month</option>
            <?php
    $months = [
        'January', 'February', 'March', 'April', 'May', 'June', 'July',
        'August', 'September', 'October', 'November', 'December'
    ];
    ?>
            <?php foreach ($months as $index => $month): ?>
            <option value="<?= $index + 1 ?>"><?= $month ?></option>
            <?php endforeach; ?>
        </select>

        <select name="birthdate_day" id="birthdate_day" required>
            <option value="" disabled selected>Day</option>
        </select>



        <input type="hidden" name="email" value="{{ $user->email }}">
        <div id="birthdateError" style="color: red;"></div> <!-- Add a div to display the error message -->
        <br>
        <button type="submit">Register</button>

    </form>

    <script>
        // Function to dynamically populate the days based on the selected month and year
        function populateDays() {
            var selectedMonth = parseInt(document.getElementById("birthdate_month").value);
            var selectedYear = parseInt(document.getElementById("birthdate_year").value);
            var daysInMonth = new Date(selectedYear, selectedMonth, 0).getDate();

            // Adjust for February in leap years
            if (selectedMonth === 2 && isLeapYear(selectedYear)) {
                daysInMonth = 29;
            }

            var daySelect = document.getElementById("birthdate_day");
            daySelect.innerHTML = ""; // Clear previous options

            // Add "Day" option as the default
            var defaultOption = document.createElement("option");
            defaultOption.value = "";
            defaultOption.disabled = true;
            defaultOption.selected = true;
            defaultOption.textContent = "Day";
            daySelect.appendChild(defaultOption);

            // Add options for each day in the month
            for (var i = 1; i <= daysInMonth; i++) {
                var option = document.createElement("option");
                option.value = i;
                option.text = i;
                daySelect.appendChild(option);
            }
        }

        // Function to check if a year is a leap year
        function isLeapYear(year) {
            // A leap year is divisible by 4, but not by 100, unless it is also divisible by 400
            return (year % 4 === 0 && year % 100 !== 0) || (year % 400 === 0);
        }

        // Event listeners to update days when month or year changes
        document.getElementById("birthdate_month").addEventListener("change", populateDays);
        document.getElementById("birthdate_year").addEventListener("change", populateDays);

        // Function to show or hide the month field based on the selected year
        function showMonthField() {
            var selectedYear = document.getElementById("birthdate_year").value;
            var monthField = document.getElementById("birthdate_month");
            if (selectedYear !== "") {
                monthField.style.display = "block";
                populateDays(); // Populate days when the month field is shown
            } else {
                monthField.style.display = "none";
            }
        }


    </script>
</body>
</html>
