<?php

require_once( "database.php" );

$page_title = "Rooms";
$room_id = "";
$room_name = "";
if( ( isset( $_GET["i"] )  &&  $_GET["i"] != "" )  ||
    ( isset( $_POST["room-id"] )  &&  $_POST["room-id"] != "" ) )
	{
	if( isset( $_GET["i"] ) )
		$room_id = $_GET["i"];
	else
		$room_id = $_POST["room-id"];
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT name, topic, hidden FROM rooms WHERE id = ?" );
	$stmt->bind_param( "s", $room_id );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $room_name, $room_topic, $room_hidden );
	$stmt->fetch();
	$stmt->close();
	if( $room_hidden == 1 )
		{
		// This room is hidden, so pretend it doesn't exist.
		header( "Location: room.php\n\n" );
		}
	$page_title = "$room_name - World";
	}
else
	{
	$_GET["i"] = "*";
	}

// Join a room
if( isset( $_GET["join"] )  &&  $userID != ""  &&  $room_id != "" )
	{
	$invite_only = get_db_value( $db, "SELECT invite_only FROM rooms WHERE id = ?", "s", $room_id );
	if( $invite_only == 0 )
		{
		$sql = "INSERT INTO room_members (id, room, user, op) VALUES (UUID(), ?, ?, 0)";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $room_id, $userID );
		$stmt->execute();
		$stmt->close();
		}
	}

// Add post
if( isset( $_POST['compose-post'] ) )
	{
	$sql = "INSERT INTO posts (id, author, created, content, " .
	                          "parent, public) " .
							  "VALUES (UUID(), ?, ?, ?, '', 1)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "sis", $userID, time(), $_POST["compose-post"] );
	$stmt->execute();
	$stmt->close();
	$new_post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ? ORDER BY created DESC LIMIT 1", "s", $userID );
	$sql = "INSERT INTO room_posts (id, room, post) " .
							  "VALUES (UUID(), ?, ?)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $_POST["room-id"], $new_post_id );
	$stmt->execute();
	$stmt->close();
	}

// Create a new room
if( isset( $_POST["new-room"] )  &&
   $_POST["new-room"] != "" )
   	{
	$invalid_room_names = array( "", "all", "global", "public" );
	$room_name = $_POST["new-room"];
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT id, name FROM rooms" );
	$stmt->execute();
	$stmt->bind_result( $returned_room_id, $returned_room_name );
	$stmt->fetch();
	$stmt->close();
	if( strtolower($returned_room_name) == strtolower($room_name) )
		{
		$_GET["error"] = 301;
		$_GET["room_id"]   = $returned_room_id;
		$_GET["room_name"] = $returned_room_name;
		}
	elseif( in_array( strtolower($room_name), $invalid_room_names ) )
		$_GET["error"] = 302;
	else
		{
		$public = 0;
		if( isset( $_POST["public"] )  &&  $_POST["public"] != "" )
			$public = 1;
		$hidden = 0;
		if( isset( $_POST["hidden"] )  &&  $_POST["hidden"] != "" )
			$hidden = 1;
		$invite_only = 0;
		if( isset( $_POST["invite-only"] )  &&  $_POST["invite-only"] != "" )
			$invite_only = 1;
		$topic = "";
		if( isset( $_POST["topic"] )  &&  $_POST["topic"] != "" )
			$topic = $_POST["topic"];
		$password = "";
		/* if( isset( $_POST["password"] )  &&  $_POST["password"] != "" )
			$password = $_POST["password"]; */
		if( $password != ""  &&  ! testPassword( $password ) )
			$_GET["error"] = 152;
		else
			{
			$stmt = $db->stmt_init();
			$sql = "INSERT INTO rooms (id, name, topic, public, hidden, invite_only, password) VALUES (UUID(), ?, ?, ?, ?, ?, ?)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ssiis", $room_name, $topic, $public, $hidden, $invite_only, $password );
			$stmt->execute();
			$stmt->close();
			$room_id = get_db_value( $db, "SELECT id FROM rooms WHERE name = ?", "s", $room_name );
			$stmt = $db->stmt_init();
			$sql = "INSERT INTO room_members (id, room, user, op) VALUES (UUID(), ?, ?, 1)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ss", $room_id, $userID );
			$stmt->execute();
			$stmt->close();
			}
		}
	}

require_once( "header.php" );

// Display popular rooms
if( $room_name == "" )
	displayWorldOrRoomList( $db, "room", "popular" );

if( $room_name != ""  &&  $userID != "" )
	{
	$subscribed = get_db_value( $db, "SELECT COUNT(*) FROM room_members WHERE user = ? AND room = ?", "ss", $userID, $room_id );
	$invite_only = get_db_value( $db, "SELECT invite_only FROM rooms WHERE id = ?", "s", $room_id );
	if( $subscribed == 1 )
		print( "<div style=\"float: right\"><input type=\"submit\" name=\"subscribe\" value=\"Member\" disabled /></div>\n" );
	elseif( ! $invite_only )
		print( "<div style=\"float: right\"><form action=\"room.php\" method=\"get\"><input type=\"hidden\" name=\"i\" value=\"$room_id\" /><input type=\"submit\" name=\"join\" value=\"Join\" /></form></div>\n" );
	print( "<h1>$room_name</h1>\n" );
	print( "<div id=\"room-topic\">$room_topic</div>\n" );
	}
else
	print( "<h1>All Rooms</h1>\n" );

if( $userID != "" )
	displayNavbar( $db, $userID );

if( $room_name != "" )
	{
	// Display members
	print( "<div class=\"room-members\" onload=\"javascript:alert('Hello');\">\n" .
	       "Members<br />\n" );
	$sql = "SELECT users.id, users.visible_name, users.real_name, users.profile_public, room_members.op FROM room_members JOIN users ON (users.id = room_members.user) WHERE room_members.room = ? ORDER BY op DESC, users.visible_name";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $room_id );
	$stmt->execute();
	$stmt->bind_result( $user_id, $user_visible_name, $user_real_name, $user_profile_public, $user_op );
	while( $stmt->fetch() )
		{
		if( $user_op == 1 )
			print( "@<span title=\"The @ means this user is a moderator.\">" );
		if( $user_profile_public )
			print( getAuthorLink( $user_id, $user_visible_name, $user_real_name, $user_profile_public ) . "<br />\n" );
		else
			print( getAuthorLink( $user_id, $user_visible_name, $user_real_name, $user_profile_public ) . "<br />\n" );
		if( $user_op == 1 )
			print( "</span>" );
		}
	print( "</div>\n" );
	// Display compose pane
	$is_member = get_db_value( $db, "SELECT COUNT(*) FROM room_members WHERE user = ? AND room = ?", "ss", $userID, $room_id );
	if( $is_member )
		{
		displayComposePane( "room", $db, $userID, $room_id );
		}
	// Display posts that match that hashtag
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "JOIN room_posts ON (room_posts.post = posts.id AND room_posts.room = ?) " .
	       "ORDER BY posts.created DESC";
	displayPosts( $db, $db2, $sql, $userID, 25, "s", $room_id );
	// Create an empty style element just to auto-expand the compose window.
	print( "<style onload=\"javascript:toggleComposePane('compose-tools','compose-pane','compose-post');\"></style>\n" );
	}
else
	{
	// All rooms
	displayWorldOrRoomList( $db, "room", "all" );
	?>
	<h2>Create a new room</h2>
	<table border="0">
		<form action="room.php" method="post">
		<tr>
			<td class="label">Name</td>
			<td><input type="text" name="new-room" size="20" /></td>
			<td>Required. Must be unique across Hai.</td>
		</tr>
		<tr>
			<td class="label">Topic</td>
			<td><input type="text" name="topic" size="20" /></td>
			<td>Optional. What do you want to talk about first?</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="public" checked="checked" id="room-public" value="yes" />
				<label for="room-public">Public</label>
			</td>
			<td>If checked, this room will be publicly visible on the web, even to people not logged into Hai.</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="hidden" id="room-hidden" value="yes" />
				<label for="room-hidden">Hidden</label>
			</td>
			<td>If checked, this room will not appear in room lists or searches.</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="invite-only" id="invite-only" value="yes" />
				<label for="invite-only">Invite-only</label>
			</td>
			<td>If checked, nobody can join this room on their own. Only moderators of this room will be able to add members.</td>
		</tr>
		<!-- <tr>
			<td class="label">Password</td>
			<td><input type="text" name="password" size="20" /></td>
			<td>Optional. If filled in, users must enter this password to join the room.</td>
		</tr> -->
		<tr>
			<td></td>
			<td><input type="submit" value="Create room" /></td>
		</tr>
		</form>
	</table>
	<p>You will be able to change any of these parameters later.</p>
	<?php
	}

require_once( "footer.php" );
?>
