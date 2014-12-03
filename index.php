<?php
require_once( "database.php" );
require_once( "functions.php" );

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
		// Redirect user
		if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
			redirectToNewPage( $_POST["redirect"] );
		}
	}

if( isset( $_POST["editing-post-id"] ) )
	{
	// Editing a post.
	$editable = "0";
	if( isset( $_POST["editable"] )  &&  $_POST["editable"] != "" )
		$editable = 1;
	editPost( $db, $userID, $_POST["editing-post-id"], $_POST["compose-post"], $_POST["post-world"], $editable );
	/* $post_id = $_POST["editing-post-id"];
	$sql = "UPDATE posts SET content = ? WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $_POST["compose-post"], $post_id );
	$stmt->execute();
	$stmt->close();
	// If world is different,
	$current_world_name_basic = processWorldNameForBasic( get_db_value( $db, "SELECT worlds.basic_name FROM worlds JOIN world_posts ON (world_posts.world = worlds.id AND world_posts.post = ?)", "s", $post_id ) );
	$posted_world_name_basic  = processWorldNameForBasic( $_POST["post-world"] );
	// Update world.
	if( $current_world_name_basic != $posted_world_name_basic )
		{
		$result = update_db( $db, "DELETE FROM world_posts WHERE post = ?", "s", $post_id );
		$new_world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $posted_world_name_basic );
		if( $new_world_id == "" )
			{
			$posted_world_name_display = processWorldNameForDisplay( $_POST["post-world"] );
			$new_world_id = update_db( $db, "INSERT INTO worlds (id, basic_name, display_name, class) VALUES (UUID(), ?, ?, UUID())", "ss", $posted_world_name_basic, $posted_world_name_display );
			}
		$result = update_db( $db, "INSERT INTO world_posts (id, world, post) VALUES (UUID(), ?, ?)", "ss", $new_world_id, $post_id );
		$_POST["redirect"] = "world.php?i=$new_world_id";
		} */
	// Redirect user
	if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
		redirectToNewPage( $_POST["redirect"] );
	}
elseif( isset( $_POST["editing-comment-id"] ) )
	{
	// Editing a post.
	$post_content = $_POST["compose-post"];
	// Process die rolls
	$post_content = preg_replace_callback( "/\[ROLL\]([\S\s])+?\[\/ROLL\]/i", "processDieRoll", $post_content );
	$post_content = preg_replace_callback( "/\[ROLL ([\S\s])+?\]/i", "processDieRoll", $post_content );
	$comment_id = $_POST["editing-comment-id"];
	$sql = "UPDATE comments SET content = ? WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $post_content, $comment_id );
	$stmt->execute();
	$stmt->close();
	// Redirect user
	if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
		redirectToNewPage( $_POST["redirect"] );
	}
elseif( isset( $_POST["compose-post"] ) )
	{
	$sql = "";
	$post_type = "";
	$stmt = $db->stmt_init();
	if( isset( $_POST["post-id"] ) )
		{
		if( $_POST["compose-post"] != ""  &&
		    ! postingIdenticalToLastPost( $db, $_POST["compose-post"], "comments", $userID ) )
			{
			$post_content = $_POST["compose-post"];
			// Process die rolls
			$post_content = preg_replace_callback( "/\[ROLL\]([\S\s])+?\[\/ROLL\]/i", "processDieRoll", $post_content );
			$post_content = preg_replace_callback( "/\[ROLL ([\S\s])+?\]/i", "processDieRoll", $post_content );
			$post_type = "comment";
			$sql = "INSERT INTO comments (id, author, created, content, post) VALUES (UUID(), ?, ?, ?, ?)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "siss", $userID, time(), $post_content, $_POST["post-id"] );
			$stmt->execute();
			$stmt->close();
			$new_comment_id = get_db_value( $db, "SELECT id FROM comments WHERE author = ? ORDER BY created DESC LIMIT 1", "s", $userID );
			addPings( $db, $new_comment_id, "c", $userID );
			}
		}
	else
		{
		// If the post's content is non-blank and is not identical to the author's previous post,
		if( $_POST["compose-post"] != ""  &&
		    ! postingIdenticalToLastPost( $db, $_POST["compose-post"], "posts", $userID ) )
			{
			$parent = "";
			if( isset( $_POST["reply-to-post-id"] ) )
				$parent = $_POST["reply-to-post-id"];
			$public = "0";
			if( isset( $_POST["public"] )  &&  $_POST["public"] != "" )
				$public = 1;
			$editable = "0";
			if( isset( $_POST["editable"] )  &&  $_POST["editable"] != "" )
				$editable = 1;
			$world_name = "";
			if( isset( $_POST["post-world"] )  &&  $_POST["post-world"] != "" )
				$world_name = $_POST["post-world"];
			insertPost( $db, $userID, $_POST["compose-post"], $parent, $public, $editable, $world_name );
			}
		}
	// Redirect user
	if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
		redirectToNewPage( $_POST["redirect"] );
	}

$page_title = "Home";
require_once( "header.php" );

//requireLogin( $db, $db2 );

displayNavbar( $db, $userID );

if( $userID != "" )
	{
	displayComposePane( "post", $db, $userID );
	
	if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
		{
		print( "<h1>Everything</h1>\n" );
		$sql = getStandardSQL( "Everything" );
		}
	elseif( isset( $_GET["tab"] ) )
		{
		$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", "s", $_GET["tab"] );
		print( "<h1>$team_name</h1>\n" );
		$sql = getStandardSQL( "team" );
		}
	else
		{
		print( "<h1>All</h1>\n" );
		$sql = getStandardSQL( "all" );
		}
	
	//displayPosts( $db, $db2, $sql, $userID, 25, "ss", $userID, $_GET["tab"] );
	//displayPosts( $db, $db2, $sql, $userID, 25, "none" );
	/* print( "$sql<br>\n" );;
	print( "User ID $userID, tab " . $_GET["tab"] . ", $userID<br>\n" ); */
	//print( "$sql<br>\n" );
	
	if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
		displayPostsV2( $db, $db2, $sql, $userID, 25, "none" );
	elseif( isset( $_GET["tab"] ) )
		displayPostsV2( $db, $db2, $sql, $userID, 25, "ss", $_GET["tab"], $userID );
	else
		displayPostsV2( $db, $db2, $sql, $userID, 25, "ss", $userID, $userID );
	}
else
	{
	?>
	<p>Welcome to Hai, an experimental social platform.</p>
	<p>Hai is divided into Worlds and Rooms. Each <strong><a href="world.php">World</a></strong> focuses on one topic, while <strong><a href="room.php">Rooms</a></strong> are named areas of conversation. Worlds are like magazines (always on topic), while Rooms are like forums (themed but not always on topic).</p>
	<p>You can also search for <a href="hashtag.php"><strong>Hashtags</strong></a> across Hai.</p>
	<p>Until you log in, you can only see content that has been marked public.</p>
	<p>When you're logged in, you can assign other users to Teams based on their interests and see what they've posted.</p>
	<h2>Recent Posts</h2>
	<?php
	$sql = getStandardSQL( "Everything" );
	displayPostsV2( $db, $db2, $sql, "", 25, "none" );
	}

require_once( "footer.php" );
?>
