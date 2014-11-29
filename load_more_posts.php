<?php

// Returns more paged posts

function fixSQL( $sql, $start_index )
	{
	return str_replace( "LIMIT 25", "LIMIT $start_index, 25", $sql );
	}

require_once( "database.php" );
require_once( "functions.php" );
$tab = 0;
if( isset( $_GET["tab"] ) )
	$tab = $_GET["tab"];
$start_index = 0;
if( isset( $_GET["index"] ) )
	$start_index = $_GET["index"];
$userID = "";
if( isset( $_GET["u"] ) )
	$userID = $_GET["u"];
$last_post_time = "";
if( isset( $_GET["t"] ) )
	$last_post_time = $_GET["t"];

//print( "$start_index user $user_id<br>\n" );;

if( $last_post_time == "" )
	{
	// Loading more posts
	if( $tab == "Everything" )
		{
		$sql = fixSQL( getStandardSQL( "Everything" ), $start_index );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, "none" );
		}
	elseif( $tab != "" )
		{
		$sql = fixSQL( getStandardSQL( "team" ), $start_index );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, "ss", $tab, $userID );
		}
	else
		{
		$sql = fixSQL( getStandardSQL( "all" ), $start_index );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, "ss", $userID, $userID );
		}
	}
else
	{
	$room_id = $_GET["r"];
	// Loading recent posts into room
	$sql = getStandardSQLselect() .
		   "JOIN room_posts ON (room_posts.post = posts.id AND room_posts.room = ?) " .
		   "LEFT JOIN broadcasts ON (broadcasts.id = posts.id) " . // Ignore broadcasts
	       "WHERE bothcreated < $last_post_time " .
	       "ORDER BY posts.created DESC";
	displayPosts( $db, $db2, $sql, 0, $posts_per_page, "s", $room_id );
	}
?>
