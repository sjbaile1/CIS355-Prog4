<?php // Simply destroys the session and logs the user out.
session_start();
session_destroy(); //resets our session and returns to login page
header("Location: login.php");