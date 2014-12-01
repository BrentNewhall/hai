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
//print( "Hello ($last_post_time)... " );

if( isset( $_GET["r"] ) )
	{
	$room_id = $_GET["r"];
	// Loading recent posts into room
	$sql = getStandardSQLselect() .
		   "LEFT JOIN broadcasts ON (broadcasts.id = posts.id) " . // Ignore broadcasts
		   "JOIN room_posts ON (room_posts.post = posts.id AND room_posts.room = ?) " .
	       "WHERE posts.created > $last_post_time " .
	       "ORDER BY posts.created DESC";
	//displayPostsV2( $db, $db2, $sql, 0, $posts_per_page, "s", $room_id );
	//print $sql;
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		//print( "Prepared. " );
		$stmt->bind_param( "s", $room_id );
		$stmt->execute();
		print $db->error;
		print $stmt->error;
		$stmt->store_result();
		$stmt->bind_result( $post_id, $created_date );
		//print( $stmt->num_rows . " results. " );
		$post_index = 0;
		$post_ids = array();
		while( $stmt->fetch() )
			{
			//print( "FOUND $post_id<br>\n" );
			array_push( $post_ids, $post_id );
			}
		$stmt->close();
		foreach( $post_ids as $post_id )
			{
			displayPost( $db, $post_id );
			$post_index++;
			}
		}
	print $db->error;
	}
else
	{
	// Loading more posts
	if( $tab == "Everything" )
		{
		$sql = fixSQL( getStandardSQL( "Everything" ), $start_index );
		displayPostsV2( $db, $db2, $sql, $userID, $posts_per_page, "none" );
		}
	elseif( $tab != "" )
		{
		$sql = fixSQL( getStandardSQL( "team" ), $start_index );
		displayPostsV2( $db, $db2, $sql, $userID, $posts_per_page, "ss", $tab, $userID );
		}
	else
		{
		$sql = fixSQL( getStandardSQL( "all" ), $start_index );
		displayPostsV2( $db, $db2, $sql, $userID, $posts_per_page, "ss", $userID, $userID );
		}
	}
?>
