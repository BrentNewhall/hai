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

// So, the following is a hack. Due to the structure of the displayPosts()
// function, I can't easily add more parameters. So, I set this "global"
// variable so it knows to display comment history.
$display_comment_history = "yes";

function printEdits( $db, $post_id )
	{
	$num_edits = get_db_value( $db, "SELECT COUNT(*) FROM post_history WHERE post = ?", array( "s", &$post_id ) );
	if( $num_edits > 0 )
		{
		print( "<div id=\"post-history-container\">\n" );
		print( "<div id=\"post-history\">\n" );
		$sql = "SELECT original_content, edited, author, real_name, visible_name, profile_public FROM post_history JOIN users ON (post_history.author = users.id) WHERE post_history.post = ? ORDER BY edited DESC";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		print $stmt->error;
		$stmt->bind_param( "s", $post_id );
		$stmt->execute();
		$stmt->bind_result( $content, $timestamp, $author, $real_name, $visible_name, $profile_public );
		while( $stmt->fetch() )
			{
			print( "<div class=\"history-info\"><span title=\"" . date( "d M Y @ H:i", $timestamp ) . "\">" . getAge( $timestamp ) . " ago</a>, " .
			       getAuthorLink( $author, $visible_name, $real_name, $profile_public ) .
			       " changed this post from:</div>\n" .
			       formatPost( $content ) );
			}
		print( "</div>\n" );
		print( "</div>\n" );
		}
	}

function displayPostWithHistory( $db, $db2, $userID, $post_id, $sql )
	{
	global $this_page_post_id;
	$parent_post_id = get_db_value( $db, "SELECT parent FROM posts WHERE id = ?", array( "s", &$post_id ) );
	if( $parent_post_id != "" )
		displayPostWithHistory( $db, $db2, $userID, $parent_post_id, $sql );
	if( $post_id == $this_page_post_id )
		{
		print( "<a name=\"main-post\"></a>\n" );
		printEdits( $db, $post_id );
		print( "<div style=\"border-left: 5px solid black\">\n" );
		}
	displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "s", &$post_id ) );
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
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "s", &$post_id ) );
	}

if( $userID != "" )
	displayNavbar( $db, $userID );

$public = get_db_value( $db, "SELECT public FROM posts WHERE id = ?", array( "s", &$post_id ) );
$author = get_db_value( $db, "SELECT author FROM posts WHERE id = ?", array( "s", &$post_id ) );

if( $public  ||  $author == $userID )
	{
	$sql = getStandardSQLselect() . " LEFT JOIN broadcasts ON (broadcasts.id = posts.id) WHERE posts.id = ? ORDER BY bothcreated DESC";
	displayPostWithHistory( $db, $db2, $userID, $post_id, $sql );
	displayPostChildren( $db, $db2, $userID, $post_id, $sql );
	}

require_once( "footer.php" );
?>
