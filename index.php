<?php
require_once( "database.php" );
require_once( "functions.php" );

if( isset( $_POST["action"] )  &&
    $_POST["action"] == "update-group-membership" )
	{
	if( isset( $_POST["user"] )  &&
	    get_db_value( $db, "SELECT id FROM users WHERE id = ?", array( "s", & $_POST["user"] ) ) == $_POST["user"] )
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
			if( get_db_value( $db, "SELECT id FROM user_teams WHERE id = ?", array( "s", &$team_id ) ) == $team_id )
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
	$comments = "0";
	if( isset( $_POST["comments"] )  &&  $_POST["comments"] != "" )
		$comments = 1;
	editPost( $db, $userID, $_POST["editing-post-id"], $_POST["compose-post"], $_POST["post-world"], $editable, $comments );
	// Redirect user
	if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
		redirectToNewPage( $_POST["redirect"] );
	}
elseif( isset( $_POST["editing-comment-id"] ) )
	{
	// Editing a comment.
	$comment_content = $_POST["compose-post"];
	// Process die rolls
	$comment_content = preg_replace_callback( "/\[ROLL\]([\S\s])+?\[\/ROLL\]/i", "processDieRoll", $comment_content );
	$comment_content = preg_replace_callback( "/\[ROLL ([\S\s])+?\]/i", "processDieRoll", $comment_content );
	$comment_id = $_POST["editing-comment-id"];
	editComment( $db, $userID, $comment_id, $comment_content );
	/* $sql = "UPDATE comments SET content = ? WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $post_content, $comment_id );
	$stmt->execute();
	$stmt->close(); */
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
			$new_comment_id = get_db_value( $db, "SELECT id FROM comments WHERE author = ? ORDER BY created DESC LIMIT 1", array( "s", &$userID ) );
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
			$comments = "0";
			if( isset( $_POST["comments"] )  &&  $_POST["comments"] != "" )
				$comments = 1;
			$world_name = "";
			if( isset( $_POST["post-world"] )  &&  $_POST["post-world"] != "" )
				$world_name = $_POST["post-world"];
			insertPost( $db, $userID, $_POST["compose-post"], $parent, $public, $editable, $comments, $world_name );
			}
		}
	// Redirect user
	if( isset( $_POST["redirect"] )  &&  $_POST["redirect"] != "" )
		redirectToNewPage( $_POST["redirect"] );
	}

$page_title = "Home";
require_once( "header.php" );

displayNavbar( $db, $userID );

if( $userID != "" )
	{
	
	if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
		{
		print( "<h1>Everything</h1>\n" );
		displayComposePane( "post", $db, $userID );
		$sql = getStandardSQL( "Everything User" );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "ss", &$userID, &$userID ) );
		}
	elseif( isset( $_GET["tab"] ) )
		{
		$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", array( "s", &$_GET["tab"] ) );
		print( "<h1>$team_name</h1>\n" );
		displayComposePane( "post", $db, $userID );
		$sql = getStandardSQL( "team" );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "ss", &$_GET["tab"], &$userID ) );
		}
	else
		{
		print( "<h1>All</h1>\n" );
		$sql = getStandardSQL( "all" );
		displayComposePane( "post", $db, $userID );
		displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "sssss", &$userID, &$userID, &$userID, &$userID, &$userID ) );
		}
	}
else
	{
	?>
	<p>Welcome to Hai, an experimental social platform.</p>
	<p>Hai is divided into Worlds and Rooms. Each <strong><a href="world.php">World</a></strong> focuses on one topic, while <strong><a href="room.php">Rooms</a></strong> are named areas of conversation. Worlds are like magazines (every article is related to one topic), while Rooms are like web forums or chat rooms (themed but not always on topic).</p>
	<p>You can also browse <a href="hashtag.php"><strong>Hashtags</strong></a> across Hai.</p>
	<p>Until you log in, you can only see content that has been marked public.</p>
	<p>When you're logged in, you can assign other users to Teams based on their interests and see what they've posted.</p>
	<p>Check out <a href="formatting.php">all the ways you can format your posts</a>, too.</p>
	<h2>Recent Posts</h2>
	<?php
	$sql = getStandardSQL( "Everything" );
	displayPosts( $db, $db2, $sql, "", $posts_per_page );
	}

require_once( "footer.php" );
?>
