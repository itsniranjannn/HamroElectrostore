<?php
// logout.php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to landing page or login page
header("Location: land.php");
exit();
?>
