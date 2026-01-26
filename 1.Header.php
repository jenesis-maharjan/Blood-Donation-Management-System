<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sath</title>
    <link rel="icon" href="Pics/Logo.jpg" type="image/x-icon">

    <link rel="stylesheet" href="css/Styles.css">
    <link rel="stylesheet" href="css/Admin.css">
    <script src="js/Header.js" defer></script>
    <script src="js/Slider.js" defer></script>
    <script src="js/Admin.js" defer></script>

</head>
<body>
    <header>
        <!-- Top Section of the Header -->
        <div class="header-top">
            <div class="center-content">
                <a href="2.MainPage.php">
                    <img src="Pics/Logo.jpg" alt="Logo" class="logo">
                </a>
                <h1 class="company-name">Sath</h1>
            </div>
        </div>

        <!-- Lower Section of the Header (Navigation) -->
        <nav class="header-bottom">
            <div class="nav-left-hidden">
                <a href="2.MainPage.php">
                    <img src="Pics/Logo.jpg" alt="Logo" class="logo">
                </a>
            </div>
            <ul class="nav-menu">
                <li>
                    <a href="#" class="dropdown-toggle">About Us ▼</a>
                    <ul class="dropdown">
                        <li><a href="6.Information.php">Blood Information</a></li>
                        <li><a href="2.MainPage.php#what-we-do">What We Do</a></li>
                        <li><a href="2.MainPage.php#our-team">Our Team</a></li>
                        <li><a href="5.Events.php">Events</a></li>
                    </ul>
                </li>
                <li><a href="3.Request.php">Request Blood</a></li>
                <li><a href="4.Donate.php">Donate Blood</a></li>
                <li><a href="14.Checking.php">Tracking</a></li>
            </ul>
            <div class="nav-right-hidden">
                <div class="dropdown_login">
                    <button id="loginButton">
                        <img src="Pics/profile.png" alt="Login Icon" class="icon">
                    </button>
                    <div id="loginForm" class="dropdown-content-login">
                        <form id="loginArea">
                            <h3>Admin Login</h3>
                            <input type="text" id="username" name="username" placeholder="Username" required><br>
                            <input type="password" id="password" name="password" placeholder="Password" required><br>
                            <p id="errorMessage" style="color: red; display: none;">Incorrect Username or Password</p>
                            <button type="button" id="submitButton" class="login_submit">Submit</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>
    </header>
</body>
</html>
