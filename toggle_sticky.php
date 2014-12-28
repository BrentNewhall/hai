<?php

// Toggles a post's sticky status.

require_once( "database.php" );
require_once( "functions.php" );
$post_id = "";
if( isset( $_GET["i"] ) )
	$post_id = $_GET["i"];
$new_value = "";
if( isset( $_GET["v"] ) )
	$new_value = $_GET["v"];

if( $post_id == ""  ||  $new_value == ""  ||  $userID == "" )
	exit( 0 );

update_db( $db, "UPDATE room_posts SET sticky = ? WHERE post = ?", "ss", $new_value, $post_id );
$room_id = get_db_value( $db, "SELECT room FROM room_posts WHERE post = ?", array( "s", &$post_id ) );
header( "Location: room.php?i=$room_id\n\n" );
?>
