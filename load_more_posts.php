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

//print( "$start_index user $user_id<br>\n" );;

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
?>
