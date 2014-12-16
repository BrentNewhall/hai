<?php

// Exports user data and downloads it to user's computer as "hai-user.xml".

require_once( "database.php" );
require_once( "functions.php" );
if( $userID != "" )
	{
	$username = get_db_value( $db, "SELECT username FROM users WHERE id = ?", "s", $userID );
	$username = preg_replace( "/[^A-Za-z0-9]/", "", $username );
	header( "Content-Description: File Transfer" );
	header( "Content-Type: application/octet-stream" );
	header( "Content-Disposition: attachment; filename=hai-user-$username.xml" );
	header( "Content-Transfer-Encoding: binary" );
	header( "Expires: 0" );
	header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
	header( "Pragma: public" );
	ob_clean();
	flush();
	exportAccount( $db, $userID );
	exit( 0 );
	}
?>
