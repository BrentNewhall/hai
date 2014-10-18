<?php

$page_title = "View Post";
require_once( "header.php" );
require_once( "database.php" );

$post_id = "";
if( isset( $_GET["i"] )  &&  $_GET["i"] != "" )
	$post_id = $_GET["i"];

if( $post_id == ""  &&  strlen($post_id) != 36 )
	{
	print( "<p>Sorry.</p>\n" );
	require_once( "footer.php" );
	exit( 0 );
	}

$this_page_post_id = $post_id;

function displayPostWithHistory( $db, $db2, $userID, $post_id, $sql )
	{
	global $this_page_post_id;
	$parent_post_id = get_db_value( $db, "SELECT parent FROM posts WHERE id = ?", "s", $post_id );
	if( $parent_post_id != "" )
		displayPostWithHistory( $db, $db2, $userID, $parent_post_id, $sql );
	if( $post_id == $this_page_post_id )
		{
		print( "<a name=\"main-post\"></a>\n" );
		print( "<div style=\"border-left: 5px solid black\">\n" );
		}
	displayPosts( $db, $db2, $sql, $userID, 25, "s", $post_id );
	if( $post_id == $this_page_post_id )
		print( "</div>\n" );
	}

function displayPostChildren( $db, $db2, $userID, $parent_post_id, $sql )
	{
	$child_post_ids = array();
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT id FROM posts WHERE parent = ? ORDER BY created" );
	$stmt->bind_param( "s", $parent_post_id );
	$stmt->execute();
	$stmt->bind_result( $id );
	while( $stmt->fetch() )
		{
		array_push( $child_post_ids, $id );
		}
	$stmt->close();
	foreach( $child_post_ids as $post_id )
		displayPosts( $db, $db2, $sql, $userID, 25, "s", $post_id );
	}

if( $userID != "" )
	displayNavbar( $db, $userID );

$public = get_db_value( $db, "SELECT public FROM posts WHERE id = ?", "s", $post_id );

if( $public )
	{
	$sql = getStandardSQLselect() . "WHERE posts.id = ?";
	displayPostWithHistory( $db, $db2, $userID, $post_id, $sql );
	displayPostChildren( $db, $db2, $userID, $post_id, $sql );
	}

require_once( "footer.php" );
?>
