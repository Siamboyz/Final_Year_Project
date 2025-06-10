<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/index_style.css" type="text/css">
    <title>OASS - Home</title>
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar">
    <a href="#" class="logo">Ophthalmology Department</a>
    <ul class="menu">
        <li><a href="#">Home</a></li>
        <li><a href="#">About Us</a></li>
        <li><a href="#">Gallery</a></li>
        <li><a href="#">Contact Us</a></li>
    </ul>
</nav>

<!-- Login Section -->
<section id="logins" class="our-blog container-fluid">
    <div class="login-container">
        <div class="login-box">
            <h1>Welcome to OASS</h1>
            <h2>Sign in to your account</h2>
            <p>Please enter your email and password to log in.</p>

            <form action="process_login.php" method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                <button type="submit" class="login-button" name="login">Login <span>&#8594;</span></button>
            </form>

            <p class="register-link">Don't have an account yet? <a href="registration.php">Create an account</a></p>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    &copy; 2025 OASS System | All Rights Reserved
</footer>

</body>
</html>
