<?php

require_once( "database.php" );

$page_title = "Worlds";
$world_id = "";
$world = "";
if( isset( $_GET["i"] )  &&  $_GET["i"] != "" )
	{
	$world_id = $_GET["i"];
	$world = get_db_value( $db, "SELECT display_name FROM worlds WHERE id = ?", "s", $world_id );
	$page_title = "$world - World";
	}
else
	{
	$_GET["i"] = "*";
	}

if( isset( $_GET["subscribe"] )  &&  $userID != ""  &&  $world_id != "" )
	{
	// If the user has not already subscribed to this world,
	$id = get_db_value( $db, "SELECT id FROM user_worlds WHERE user = ? AND world = ?", "ss", $userID, $world_id );
	if( $id == "" )
		{
		$sql = "INSERT INTO user_worlds (id, user, world) VALUES (UUID(), ?, ?)";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $userID, $world_id );
		$stmt->execute();
		$stmt->close();
		}
	}

require_once( "header.php" );

// Display popular worlds
function displayPopularWorlds( $db )
	{
	print( "<div title=\"These are the 25 worlds with the most posts.\">" .
	       "<p><strong>Popular</strong>: \n" );
	$sql = "SELECT worlds.id, worlds.display_name, (SELECT COUNT(*) FROM world_posts tp WHERE tp.world = worlds.id) AS WorldCount FROM worlds " .
		   //"JOIN world_posts ON (world_posts.world = worlds.id) " .
	       "ORDER BY WorldCount DESC LIMIT 25";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->execute();
	$stmt->bind_result( $world_id, $world_name, $world_count );
	while( $stmt->fetch() )
		{
		print( "<a href=\"world.php?i=$world_id\" title=\"$world_count post" );
		if( $world_count > 1 )
			print( "s" );
		print( "\">$world_name</a> \n" );
		}
	$stmt->close();
	print( "</div>\n" );
	}

if( $world == "" )
	displayWorldOrRoomList( $db, "world", "popular" );

if( $world != ""  &&  $userID != "" )
	{
	$subscribed = get_db_value( $db, "SELECT COUNT(*) FROM user_worlds WHERE user = ? AND world = ?", "ss", $userID, $world_id );
	if( $subscribed == 1 )
		print( "<div style=\"float: right\"><input type=\"submit\" name=\"subscribe\" value=\"Subscribed\" disabled /></div>\n" );
	else
		print( "<div style=\"float: right\"><form action=\"world.php\" method=\"get\"><input type=\"hidden\" name=\"i\" value=\"$world_id\" /><input type=\"submit\" name=\"subscribe\" value=\"Subscribe\" /></form></div>\n" );
	print( "<h1>$world</h1>\n" );
	}
else
	print( "<h1>All Worlds</h1>\n" );

displayNavbar( $db, $userID );

if( $world != "" )
	{
	displayComposePane( "post", $db, $userID );
	// Display posts that match that hashtag
	$sql = getStandardSQLselect() .
	       "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
		   "JOIN world_posts ON (world_posts.post = posts.id AND world_posts.world = ?) " .
	       "ORDER BY posts.created DESC";
	
	displayPostsV2( $db, $db2, $sql, $userID, 25, "s", $world_id );
	}
else
	{
	displayWorldOrRoomList( $db, "world", "list" );
	}

require_once( "footer.php" );
?>
