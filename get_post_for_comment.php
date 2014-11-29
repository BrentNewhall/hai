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

$sql = getStandardSQLselect() .
	   "LEFT JOIN broadcasts ON (broadcasts.id = posts.id) " .
       "WHERE posts.id = ? ORDER BY posts.created ";

displayPosts( $db, $db2, $sql, $userID, 25, "s", $post_id );
?>
