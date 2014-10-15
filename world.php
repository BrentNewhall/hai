<?php

require_once( "database.php" );

$page_title = "Worlds";
$world_id = "";
$world = "";
if( isset( $_GET["t"] )  &&  $_GET["t"] != "" )
	{
	$world_id = $_GET["t"];
	$world = get_db_value( $db, "SELECT display_name FROM worlds WHERE id = ?", "s", $world_id );
	$page_title = "$world - World";
	}
else
	{
	$_GET["t"] = "*";
	}

if( isset( $_GET["subscribe"] )  &&  $userID != ""  &&  $world_id != "" )
	{
	$sql = "INSERT INTO user_worlds (id, user, world) VALUES (UUID(), ?, ?)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $userID, $world_id );
	$stmt->execute();
	$stmt->close();
	}

require_once( "header.php" );

// Display popular worlds
function displayPopularWorlds( $db )
	{
	print( "<div title=\"These are the 25 worlds with the most posts.\">" .
	       "<p><strong>Popular</strong>: \n" );
	$sql = "SELECT worlds.id, worlds.display_name, (SELECT COUNT(*) FROM world_posts tp WHERE tp.world = worlds.id) AS WorldCount FROM worlds " .
		   //"JOIN world_posts ON (world_posts.world = worlds.id) " .
	       "ORDER BY WorldCount LIMIT 25";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->execute();
	$stmt->bind_result( $world_id, $world_name, $world_count );
	while( $stmt->fetch() )
		{
		print( "<a href=\"world.php?t=$world_id\" title=\"$world_count results\">$world_name</a> \n" );
		}
	$stmt->close();
	print( "</div>\n" );
	}

displayPopularWorlds( $db );

if( $world != ""  &&  $userID != "" )
	{
	$subscribed = get_db_value( $db, "SELECT COUNT(*) FROM user_worlds WHERE user = ? AND world = ?", "ss", $userID, $world_id );
	if( $subscribed == 1 )
		print( "<div style=\"float: right\"><input type=\"submit\" name=\"subscribe\" value=\"Subscribed\" disabled /></div>\n" );
	else
		print( "<div style=\"float: right\"><form action=\"world.php\" method=\"get\"><input type=\"hidden\" name=\"t\" value=\"$world_id\" /><input type=\"submit\" name=\"subscribe\" value=\"Subscribe\" /></form></div>\n" );
	print( "<h1>$world</h1>\n" );
	}
else
	print( "<h1>Worlds</h1>\n" );

if( $userID != "" )
	displayNavbar( $db, $userID );

if( $world != "" )
	{
	// Display posts that match that hashtag
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "JOIN world_posts ON (world_posts.post = posts.id AND world_posts.world = ?) " .
	       "ORDER BY posts.created DESC";
	
	displayPosts( $db, $db2, $sql, $userID, 25, "s", $world_id );
	}

require_once( "footer.php" );
?>
