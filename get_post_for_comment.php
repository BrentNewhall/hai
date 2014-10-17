<?php

// Returns a post's data

require_once( "database.php" );
require_once( "functions.php" );
$post_id = "";
if( isset( $_GET["i"] ) )
	$post_id = $_GET["i"];
$userID = "";
if( isset( $_GET["u"] ) )
	$userID = $_GET["u"];

if( $post_id == "" )
	exit( 0 );

$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
	   "JOIN users ON (posts.author = users.id) " .
	   "WHERE posts.id = ? ";

displayPosts( $db, $db2, $sql, $userID, 25, "s", $post_id );
?>
