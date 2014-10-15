<?php
require_once( "database.php" );
if( isset( $_POST["action"] )  &&
    $_POST["action"] == "update-group-membership" )
	{
	if( isset( $_POST["user"] )  &&
	    get_db_value( $db, "SELECT id FROM users WHERE id = ?", "s", $_POST["user"] ) == $_POST["user"] )
		{
		$user = $_POST["user"];
		// Delete existing group memberships for this user.
		$stmt = $db->stmt_init();
		$sql = "SELECT id FROM user_teams WHERE user = ?";
		$stmt->prepare( $sql );
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $team_id );
		while( $stmt->fetch() )
			{
			$stmt2 = $db2->stmt_init();
			$sql = "DELETE FROM user_team_members WHERE team = ? AND user = ?";
			$stmt2->prepare( $sql );
			$stmt2->bind_param( "ss", $team_id, $user );
			$stmt2->execute();
			$stmt2->close();
			}
		$team_ids = $_POST;
		unset( $team_ids["action"] );
		unset( $team_ids["user"] );
		foreach( array_keys( $team_ids ) as $team_id )
			{
			if( get_db_value( $db, "SELECT id FROM user_teams WHERE id = ?", "s", $team_id ) == $team_id )
				{
				$stmt = $db->stmt_init();
				$sql = "INSERT INTO user_team_members (team, user) VALUES (?, ?)";
				$stmt->prepare( $sql );
				$stmt->bind_param( "ss", $team_id, $user );
				$stmt->execute();
				$stmt->close();
				}
			}
		header( "Location: index.php\n\n" );
		exit( 0 );
		}
	}

$page_title = "Home";
require_once( "header.php" );

requireLogin( $db, $db2 );

displayNavbar( $db, $userID );

if( isset( $_POST["editing-post-id"] ) )
	{
	// Editing a post.
	$post_id = $_POST["editing-post-id"];
	$sql = "UPDATE posts SET content = ? WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $_POST["compose-post"], $post_id );
	$stmt->execute();
	$stmt->close();
	}
elseif( isset( $_POST["compose-post"] ) )
	{
	$stmt = $db->stmt_init();
	if( isset( $_POST["post-id"] ) )
		{
		$sql = "INSERT INTO comments (id, author, created, content, post) VALUES (UUID(), ?, ?, ?, ?)";
		$stmt->prepare( $sql );
		$stmt->bind_param( "siss", $userID, time(), $_POST["compose-post"], $_POST["post-id"] );
		}
	else
		{
		$parent = "";
		$public = 0;
		if( isset( $_POST["set-post-public"] )  &&  $_POST["set-post-public"] != "" )
			$public = 1;
		if( isset( $_POST["reply-to-post-id"] ) )
			$parent = $_POST["reply-to-post-id"];
		$sql = "INSERT INTO posts (id, author, created, content, " .
		                          "parent, public) " .
								  "VALUES (UUID(), ?, ?, ?, ?, ?)";
		$stmt->prepare( $sql );
		$stmt->bind_param( "sissi", $userID, time(), $_POST["compose-post"], $parent, $public );
		}
	$stmt->execute();
	$stmt->close();
	$new_post_id = "";
	if( isset( $_POST["post-world"] )  &&  $_POST["post-world"] != "" )
		{
		$new_post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ? ORDER BY created DESC LIMIT 1", "s", $userID );
		$full_world_name = processWorldNameForDisplay( $_POST["post-world"] );
		$basic_world_name = processWorldNameForBasic( $_POST["post-world"] );
		$world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $basic_world_name );
		if( $world_id == "" )
			{ // It doesn't exist, so create it
			$stmt = $db->stmt_init();
			$sql = "INSERT INTO worlds (id, basic_name, display_name) VALUES (UUID(), ?, ?)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ss", $basic_world_name, $full_world_name );
			$stmt->execute();
			$stmt->close();
			$world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $basic_world_name );
			}
		$stmt = $db->stmt_init();
		$sql = "INSERT INTO world_posts (world, post) VALUES (?, ?)";
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $world_id, $new_post_id );
		$stmt->execute();
		$stmt->close();
		}
	if( ! isset( $_POST["post-id"] ) )
		{
		// Add groups
		if( $new_post_id == "" )
			$new_post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ? ORDER BY created DESC LIMIT 1", "s", $userID );
		$post_groups = $_POST["group-ids"];
		if( $post_groups != "" )
			{
			foreach( $post_groups as $group_id )
				{
				$stmt = $db->stmt_init();
				$sql = "INSERT INTO post_groups (post, usergroup) VALUES (?, ?)";
				$stmt->prepare( $sql );
				$stmt->bind_param( "ss", $new_post_id, $group_id );
				$stmt->execute();
				$stmt->close();
				}
			}
		}
	}
if( isset( $_GET["edit-id"] ) )
	{
	// Get post information
	// Fill compose pane
	// Show compose pane
	}

displayComposePane( "post", $db, $userID );

if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
	{
	print( "<h1>Everything</h1>\n" );
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "WHERE posts.public = 1 " .
	       "ORDER BY posts.created DESC";
	}
elseif( isset( $_GET["tab"] ) )
	{
	$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", "s", $_GET["tab"] );
	print( "<h1>$team_name</h1>\n" );
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, parent_posts.id FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   // Where the author is a member of this group
		   "JOIN user_teams ug ON (ug.id = ? AND ug.user = ?) " . // ? = team ID
		   "JOIN user_team_members ugm ON (ug.id = ugm.team AND ugm.user = posts.author) " . // ? = userID
		   "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
	       "ORDER BY posts.created DESC";
	}
else
	{
	print( "<h1>All</h1>\n" );
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, parent_posts.id FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
		   "LEFT JOIN user_teams ON (user_teams.user = ?) " .
		   "LEFT JOIN user_team_members ON (user_team_members.team = user_teams.id )" . //AND user_group_members.user = ?) " . // ? = userID
		   "WHERE posts.author = ? OR user_team_members.user = posts.author " .
	       "ORDER BY posts.created DESC";
	}

//displayPosts( $db, $db2, $sql, $userID, 25, "ss", $userID, $_GET["tab"] );
//displayPosts( $db, $db2, $sql, $userID, 25, "none" );
/* print( "$sql<br>\n" );;
print( "User ID $userID, tab " . $_GET["tab"] . ", $userID<br>\n" ); */
//print( "$sql<br>\n" );

$param2 = $userID;
if( isset( $_GET["tab"] ) )
	$param2 = $_GET["tab"];

if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
	displayPosts( $db, $db2, $sql, $userID, 25, "none" );
elseif( isset( $_GET["tab"] ) )
	displayPosts( $db, $db2, $sql, $userID, 25, "ss", $param2, $userID );
else
	displayPosts( $db, $db2, $sql, $userID, 25, "ss", $userID, $userID );

require_once( "footer.php" );
?>
