<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>OASS Registration Account</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url("img/doctor-testing-patient-eyesight.jpg"); /* Adjusted path */
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-container {
            background: #FFFFFF;
            padding: 25px 35px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 420px;
        }

        h1 {
            font-size: 24px;
            margin-bottom: 15px;
            text-align: center;
            color: #005A9C; /* Deep Blue - Professional */
        }

        h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #005A9C; /* Deep Blue */
            border-bottom: 2px solid #008080; /* Teal - Accent Border */
            padding-bottom: 8px;
            text-align: center;
        }

        label {
            display: block;
            font-size: 14px;
            margin-bottom: 5px;
            color: #005A9C; /* Deep Blue */
        }

        input[type="text"], input[type="email"], input[type="password"], select {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #008080; /* Teal - Input Border */
            border-radius: 4px;
            font-size: 14px;
        }

        select {
            cursor: pointer;
        }

        select:focus, input:focus {
            border-color: #005A9C; /* Deep Blue */
            outline: none;
        }

        .login-link {
            font-size: 15px;
            color: #008080; /* Teal */
            text-decoration: none;
            text-align: center;
            display: block;
            padding: 10px;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        button {
            background-color: #005A9C; /* Deep Blue */
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        button:hover {
            background-color: #004C4C; /* Dark Teal */
        }

        footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #F5F5F5; /* Light Gray - Neutral */
            font-size: 12px;
            color: #008080; /* Teal - Accent */
            text-align: center;
            padding: 10px 0;
            box-shadow: 0px -2px 6px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>
<body>

<div class="form-container">
    <h1>OASS | Registration Account</h1>
    <form action="process_registration.php" method="POST" onsubmit="return validateForm()">
        <h2>Sign Up</h2>

        <!-- Full Name -->
        <label for="full_name">Full Name</label>
        <input type="text" id="full_name" name="name" placeholder="Enter Full Name" required>

        <!-- Choose Role -->
        <label for="role">Select Role</label>
        <select id="role" name="role" required>
            <option value="" disabled selected>-- Select Role --</option>
            <option value="doctor">Doctor</option>
            <option value="counter_staff">Counter Staff</option>
            <option value="admin">Admin</option>
        </select>

        <!-- Email -->
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter Email" required>

        <!-- Password -->
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter Password" required>

        <!-- Confirm Password -->
        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Enter Password Again" required>

        <!-- Login Link -->
        <a href="homepage.php" class="login-link">Already have an account? Log in</a>

        <!-- Submit Button -->
        <button type="submit" name="register-button">Register</button>
    </form>
</div>

<!-- Footer -->
<footer>
    &copy; 2025 OASS System | All Rights Reserved
</footer>

<script>
    function validateForm() {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirm_password').value;

        if (password !== confirmPassword) {
            alert("Passwords do not match.");
            return false;
        }

        return true;
    }
</script>

</body>
</html>
