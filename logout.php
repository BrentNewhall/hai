<?php

// Logs out the user by clearing both session variable and cookie.

session_start();
unset( $_SESSION["logged_in"] );
setcookie( "logged_in", $username, time() - 3600, "/",
           $_SERVER["SERVER_NAME"] );
header( "Location: index.php\n\n" );
exit( 0 );

?>
