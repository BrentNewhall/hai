<?php

// Logs out the user by clearing both session variable and cookie.

session_start();
unset( $_SESSION["logged_in"] );
setcookie( "logged_in", $username, time() - 3600, "/" );
header( "Location: index.php\n\n" );
exit( 0 );

?>
