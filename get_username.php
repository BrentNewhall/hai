<?php

// Returns statement if username exists or not.

require_once( "database.php" );
require_once( "functions.php" );

$username = "";
if( isset( $_GET["u"] ) )
	$username = $_GET["u"];

if( $username == "" )
	exit( 0 );

$stmt = $db->stmt_init();
$exists = intval( get_db_value( $db, "SELECT COUNT(*) FROM users WHERE username = ?", array( "s", &$username ) ) );
if( $exists > 0 )
	print( "<span class=\"username-exists\">That username already exists.</span>" );
else
	print( "<span class=\"username-available\">That username is available.</span>" );
?>
