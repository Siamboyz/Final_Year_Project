<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Welcome to OASS</title>
    <meta http-equiv="refresh" content="5;url=homepage.php">
    <script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            height: 100vh;
            background: linear-gradient(135deg, #e0f7fa, #e8f5e9);
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .splash-container {
            text-align: center;
            animation: zoomIn 1.5s ease-in-out;
        }

        h1 {
            color: #2e3d49;
            margin-top: 30px;
            font-size: 26px;
        }

        p {
            margin-top: 10px;
            color: #666;
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
<div class="splash-container">
    <!-- Lottie Player -->
    <lottie-player
        src="assets/eye_welcoming.json"
        background="transparent"
        speed="1"
        style="width: 200px; height: 200px; margin: auto;"
        loop
        autoplay>
    </lottie-player>

    <h1>Welcome to Ophthalmology Appointment Scheduling System</h1>
    <p>Redirecting to login...</p>
</div>
</body>
</html>
