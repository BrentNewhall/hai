<?php

$page_title = "Delete Account";
require_once( "header.php" );
require_once( "database.php" );

if( $userID != "" )
	{
	if( isset( $_POST["delete"] )  &&
	    $_POST["delete"] == "yes" )
		{
	// Delete info from stuff.
	deleteAccount( $db, $userID );
	// Logout user
	unset( $_SESSION["logged_in"] );
	setcookie( "logged_in", $username, time() - 3600, "/",
	           $_SERVER["SERVER_NAME"] );
	print( "<h1>Account Deleted</h1>\n" );
	print( "<p>Your account has been completely deleted.</p>\n" );
	require_once( "footer.php" );
	exit( 0 );
	?>
	<?php
		}
	else
		{
	displayNavbar( $db, $userID );
	?>
	<h1>Delete Your Account</h1>
	<p>This will <strong>delete your entire account</strong> completely. You will not be able to get this data back.</p>
	<p>This will delete the following data:</p>
	<ul>
	<li> posts you created, including their entire histories</li>
	<li> comments you created</li>
	<li> images you uploaded</li>
	<li> videos you uploaded</li>
	<li> any information you entered on your account page, including your profile image</li>
	<li> your membership in any Rooms</li>
	</ul>
	<p>If you edited a post or comment that someone else created, your edits will not be changed. If you created any Rooms or Worlds, they will remain, but any posts you created within them will be deleted.</p>
	<p><strong>If you are the only moderator of a Room</strong>, the Room will be left with no moderator. Please assign someone else as moderator.</p>
	<center>
	<form action="delete_account.php" method="post">
	<input type="hidden" name="delete" value="yes" />
	<input type="submit" value="Yes, delete my account" />
	</form>
	<br />
	<br />
	<form action="index.php" method="get">
	<input type="submit" value="Nevermind" />
	</form>
	</center>
	<?php
		}
	}

require_once( "footer.php" );
?>
