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
		exit( 0 );
		}
	$page_title = "$room_name - Room";
	}
else
	{
	$_GET["i"] = "*";
	}

// Join a room
if( ( isset( $_GET["join"] )  ||  isset( $_POST["join"] ) )  &&
    $userID != ""  &&  $room_id != "" )
	{
	$invite_only = get_db_value( $db, "SELECT invite_only FROM rooms WHERE id = ?", "s", $room_id );
	if( $invite_only == 0 )
		{
		$password = get_db_value( $db, "SELECT password FROM rooms WHERE id = ?", "s", $room_id );
		if( $password == ""  ||
		    ( isset( $_POST["password"] )  &&
		      crypt( $_POST["password"], $crypt_salt ) == $password ) )
			{
			update_db( $db, "INSERT INTO room_members (id, room, user, op) VALUES (UUID(), ?, ?, 0)", "ss", $room_id, $userID );
			}
		else
			{
			// Print password field
			require_once( "header.php" );
			requireLogin( $db, $db2 );
			displayNavbar( $db, $userID );
			print( "<h1>Enter password</h1>\n" .
			       "<form action=\"room.php\" method=\"post\">\n" .
				   "<input type=\"hidden\" name=\"room-id\" value=\"$room_id\" />\n" .
				   "<input type=\"password\" name=\"password\" />\n" .
				   "<input type=\"submit\" name=\"join\" value=\"Join\" />\n" .
				   "</form>\n" );
			require_once( "footer.php" );
			exit( 0 );
			}
		}
	}

// Leave a room
if( isset( $_GET["leave"] )  &&  $userID != ""  &&  $room_id != "" )
	{
	$in_room = get_db_value( $db, "SELECT id FROM room_members WHERE room = ? and user = ?", "ss", $room_id, $userID );
	if( $in_room != "" )
		{
		update_db( $db, "DELETE FROM room_members WHERE id = ?", "s", $in_room );
		}
	}

// Add post
if( isset( $_POST['compose-post'] ) )
	{
	if( isset( $_POST["editing-post-id"] ) )
		{
		require_once( "functions.php" );
		editPost( $db, $userID, $_POST["editing-post-id"], $_POST["compose-post"], "", 1 );
		}
	else
		{
		// The post's public flag inherits its value from the room.
		$room_is_public = get_db_value( $db, "SELECT public FROM rooms WHERE id = ?", "s", $room_id );
		$sql = "INSERT INTO posts (id, author, created, content, " .
		                          "parent, public) " .
								  "VALUES (UUID(), ?, ?, ?, '', ?)";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "sisi", $userID, time(), $_POST["compose-post"], $room_is_public );
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
	}

// Kick user
if( isset( $_GET["action"] )  &&  $_GET["action"] == "kick" )
	{
	// If user is a member of a room
	$user_id = $_GET["user"];
	$member = get_db_value( $db, "SELECT id FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $user_id );
	if( $member != "" )
		{
		// Remove that record
		update_db( $db, "DELETE FROM room_members WHERE id = ?", "s", $member );
		update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'rk', ?, 0)", "sis", $user_id, time(), $room_id );
		}
	}

// Make user op
if( isset( $_GET["action"] )  &&  $_GET["action"] == "op" )
	{
	// If user is a member of a room
	$user_id = $_GET["user"];
	$member = get_db_value( $db, "SELECT id FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $user_id );
	if( $member != "" )
		{
		// Update room record
		update_db( $db, "UPDATE room_members SET op = 1 WHERE id = ?", "s", $member );
		// Ping the user that the user was opped
		update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'ro', ?, 0)", "sis", $user_id, time(), $room_id );
		}
	}

// Deop user
if( isset( $_GET["action"] )  &&  $_GET["action"] == "deop" )
	{
	// If user is a member of a room
	$user_id = $_GET["user"];
	$member = get_db_value( $db, "SELECT id FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $user_id );
	if( $member != "" )
		{
		// Update room record
		update_db( $db, "UPDATE room_members SET op = 0 WHERE id = ?", "s", $member );
		// Ping the user that the user was opped
		update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'rd', ?, 0)", "sis", $user_id, time(), $room_id );
		}
	}

// Create a new room
if( isset( $_POST["new-room"] )  &&
   $_POST["new-room"] != "" )
   	{
	$invalid_room_names = array( "", "all", "global", "public" );
	$room_name = $_POST["room-name"];
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
		require_once( "functions.php" );
		$public = "0";
		if( isset( $_POST["public"] )  &&  $_POST["public"] != "" )
			$public = 1;
		$hidden = "0";
		if( isset( $_POST["hidden"] )  &&  $_POST["hidden"] != "" )
			$hidden = 1;
		$invite_only = "0";
		if( isset( $_POST["invite-only"] )  &&  $_POST["invite-only"] != "" )
			$invite_only = 1;
		$topic = "";
		if( isset( $_POST["topic"] )  &&  $_POST["topic"] != "" )
			$topic = $_POST["topic"];
		$password = "";
		if( isset( $_POST["password"] )  &&  $_POST["password"] != "" )
			$password = $_POST["password"];
		if( $password != ""  &&  ! testPassword( $password ) )
			$_GET["error"] = 152;
		else
			{
			$stmt = $db->stmt_init();
			$sql = "INSERT INTO rooms (id, name, topic, public, hidden, invite_only, password) VALUES (UUID(), ?, ?, ?, ?, ?, ?)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ssiiis", $room_name, $topic, $public, $hidden, $invite_only, $password );
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

if( isset( $_POST["edit-room"] )  &&  $_POST["edit-room"] != "" )
   	{
	require_once( "functions.php" );
	// Edit room.
	$new_room_name = $_POST["room-name"];
	$new_public = 0;
	if( isset( $_POST["public"] )  &&  $_POST["public"] != "" )
		$new_public = 1;
	$new_hidden = 0;
	if( isset( $_POST["hidden"] )  &&  $_POST["hidden"] != "" )
		$new_hidden = 1;
	$new_invite_only = 0;
	if( isset( $_POST["invite-only"] )  &&  $_POST["invite-only"] != "" )
		$new_invite_only = 1;
	$new_topic = "";
	if( isset( $_POST["topic"] )  &&  $_POST["topic"] != "" )
		$new_topic = $_POST["topic"];
	$new_password = "";
	if( isset( $_POST["password"] )  &&  $_POST["password"] != "" )
		$new_password = $_POST["password"];
	// New name must not exist.
	$new_name_exists = get_db_value( $db, "SELECT id FROM rooms WHERE name = ? AND id <> ?", "ss", $new_room_name, $room_id );
	// New password must be valid.
	if( $new_name_exists != "" )
		print( "<p class=\"error\">That name already exists.</p>\n" );
	elseif( $new_password != ""  &&  ! testPassword( $new_password ) )
		print( "<p class=\"error\">Passwords must have at least 8 characters and must contain at least one upper-case letter, at least one number, and at least one symbol.</p>\n" );
	else
		{
		if( $new_password != "" )
			{
			$encrypted_password = crypt( $new_password, $crypt_salt );
			update_db( $db, "UPDATE rooms SET name = ?, topic = ?, public = ?, hidden = ?, invite_only = ?, password = ? WHERE id = ?", "ssiiiss", $new_room_name, $new_topic, $new_public, $new_hidden, $new_invite_only, $encrypted_password, $room_id );
			}
		else
			update_db( $db, "UPDATE rooms SET name = ?, topic = ?, public = ?, hidden = ?, invite_only = ? WHERE id = ?", "ssiiis", $new_room_name, $new_topic, $new_public, $new_hidden, $new_invite_only, $room_id );
		$room_topic = $new_topic;
		}
	}

function displayOptionsTable( $room_id = "", $name = "", $topic = "", $public = "", $hidden = "", $invite_only = "", $password = "" )
	{
	print( "\t<hr />\n" );
	if( $room_id == "" )
		print( "\t<h2>Create a new room</h2>\n" );
	else
		print( "\t<h2>Edit this room</h2>\n" );
	?>
	<table border="0">
		<form action="room.php" method="post">
		<?php
		if( $room_id != "" )
			print( "\t\t<input type=\"hidden\" name=\"room-id\" value=\"$room_id\" />" );
		?>
		<tr>
			<td class="label">Name</td>
			<td><input type="text" name="room-name" size="20" value="<?php echo $name; ?>"/></td>
			<td>Required. Must be unique across Hai.</td>
		</tr>
		<tr>
			<td class="label">Topic</td>
			<!-- <td><input type="text" name="topic" size="20" value="<?php echo $topic; ?>"/></td> -->
			<td><textarea name="topic" id="topic" style="width: 250px; height: 100px" onkeyup="javascript:updatePreview('topic','topic-preview');"><?php echo $topic; ?></textarea></td>
			<td>Optional. What do you want to talk about first?
			    <div class="reply-suggestions" id="topic-preview-reply-suggestions"></div><div style="width: 250px; height: 75px; border: 1px solid black" id="topic-preview"></div></td>
		</tr>
		<tr>
			<td></td>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="public" <?php
				if( $public == 1  ||  $public == "" )
					print( "checked=\"checked\" " );
				?>id="room-public" value="yes" />
				<label for="room-public">Public</label>
			</td>
			<td>If checked, this room will be publicly visible on the web, even to people not logged into Hai.</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="hidden" id="room-hidden" <?php
				if( $hidden )
					print( "checked=\"checked\" " );
				?>value="yes" />
				<label for="room-hidden">Hidden</label>
			</td>
			<td>If checked, this room will not appear in room lists or searches.</td>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="invite-only" id="invite-only" <?php
				if( $invite_only )
					print( "checked=\"checked\" " );
				?>value="yes" />
				<label for="invite-only">Invite-only</label>
			</td>
			<td>If checked, nobody can join this room on their own. Only moderators of this room will be able to add members.</td>
		</tr>
		<tr>
			<td class="label">Password<?php
			if( $password != "" )
				print( " <strong>(set)</strong>" );
			?></td>
			<td><input type="text" name="password" size="20" value="" /></td>
			<td>Optional. If filled in, users must enter this password to join the room.</td>
		</tr>
		<tr>
			<td></td>
			<?php
			if( $room_id == "" )
				print( "\t\t\t<td><input type=\"submit\" name=\"new-room\" value=\"Create room\" /></td>\n" );
			else
				print( "\t\t\t<td><input type=\"submit\" name=\"edit-room\" value=\"Edit room\" /></td>\n" );
			?>
		</tr>
		</form>
	</table>
	<?php
	if( $room_id != "" )
		print( "<a href=\"add_user_to_room.php?i=$room_id\">Add users to this room</a>\n" );
	}

function displayOpInterface( $db, $userID, $room_id )
	{
	// If not an op, return nothing.
	$is_op = get_db_value( $db, "SELECT op FROM room_members WHERE user = ? AND room = ?", "ss", $userID, $room_id );
	if( ! $is_op )
		return;
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT name, topic, public, hidden, invite_only, password FROM rooms WHERE id = ?" );
	$stmt->bind_param( "s", $room_id );
	$stmt->execute();
	$stmt->bind_result( $name, $topic, $public, $hidden, $invite_only, $password );
	$stmt->fetch();
	$stmt->close();
	displayOptionsTable( $room_id, $name, $topic, $public, $hidden, $invite_only, $password );
	}

require_once( "header.php" );

// Display popular rooms
if( $room_name == "" )
	displayWorldOrRoomList( $db, "room", "popular" );

if( $room_name != ""  &&  $userID != "" )
	{
	$subscribed = get_db_value( $db, "SELECT COUNT(*) FROM room_members WHERE user = ? AND room = ?", "ss", $userID, $room_id );
	$invite_only = get_db_value( $db, "SELECT invite_only FROM rooms WHERE id = ?", "s", $room_id );
	$password = get_db_value( $db, "SELECT password FROM rooms WHERE id = ?", "s", $room_id );
	if( $subscribed == 1 )
		print( "<div style=\"float: right\"><input type=\"submit\" name=\"subscribe\" value=\"Member\" disabled /></div>\n" );
	elseif( $invite_only == 0  &&  $password == "" )
		print( "<div style=\"float: right\"><form action=\"room.php\" method=\"get\"><input type=\"hidden\" name=\"i\" value=\"$room_id\" /><input type=\"submit\" name=\"join\" value=\"Join\" /></form></div>\n" );
	}
if( $room_name != "" )
	print( "<h1>$room_name</h1>\n" );
else
	print( "<h1>All Rooms</h1>\n" );

displayNavbar( $db, $userID );

if( $room_name != "" )
	{
	// Display members
	if( $userID == "" )
		$login_user_is_op = 0;
	else
		$login_user_is_op = get_db_value( $db, "SELECT op FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $userID );
	print( "<div class=\"room-members\" onload=\"javascript:alert('Hello');\">\n" .
	       "Moderators:<br />\n" );
	$sql = "SELECT users.id, users.visible_name, users.real_name, users.profile_public, room_members.op FROM room_members JOIN users ON (users.id = room_members.user) WHERE room_members.room = ? ORDER BY op DESC, users.visible_name";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $room_id );
	$stmt->execute();
	$stmt->bind_result( $user_id, $user_visible_name, $user_real_name, $user_profile_public, $user_op );
	$last_op = 0;
	while( $stmt->fetch() )
		{
		if( $last_op == 1  &&  $user_op == 0 )
			print( "<div style=\"margin-top: 10px\">Members:</div>\n" );
		if( $user_op == 1 )
			print( "<span title=\"This user is a moderator.\"" );
		else
			print( "<span title=\"This is a regular user.\"" );
		if( $user_id == $userID )
			print( ">" );
		else
			print( " onmouseover=\"javascript:document.getElementById('member-control-$user_id').style.display='block';\" onmouseleave=\"javascript:document.getElementById('member-control-$user_id').style.display='none';\">" );
		if( $user_profile_public )
			print( getAuthorLink( $user_id, $user_visible_name, $user_real_name, $user_profile_public ) . "<br />\n" );
		else
			print( getAuthorLink( $user_id, $user_visible_name, $user_real_name, $user_profile_public ) . "<br />\n" );
		if( $login_user_is_op == 1 )
			{
			print( "<div id=\"member-control-$user_id\" class=\"member-control\" title=\"Remove from room\" style=\"display: none\"><a href=\"room.php?action=kick&i=$room_id&user=$user_id\">Kick</a>" );
			print( "&nbsp; " );
			if( $user_op )
				print( "<a title=\"Remove moderator privileges for this user\" href=\"room.php?action=deop&i=$room_id&user=$user_id\">Unmod</a>\n" );
			else
				print( "<a title=\"Make this user a moderator of this room\" href=\"room.php?action=op&i=$room_id&user=$user_id\">Make mod</a>\n" );
			print( "</div>" );
			}
		print( "</span>" );
		$last_op = $user_op;
		}
	print( "</div>\n" );
	$is_member = 0;
	if( $userID != ""  &&  get_db_value( $db, "SELECT id FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $userID ) != "" )
		$is_member = 1;
	// Display compose pane
	if( $is_member  &&  $userID != "" )
		{
		displayComposePane( "room", $db, $userID, $room_id );
		}
	// Display topic
	print( "<div id=\"room-topic\">" . formatPost( $room_topic ) . "</div>\n" );
	// Display posts for this room
	$sql = getStandardSQLselect() .
		   "JOIN room_posts ON (room_posts.post = posts.id AND room_posts.room = ?) " .
		   "LEFT JOIN broadcasts ON (broadcasts.id = posts.id) " .
	       "ORDER BY posts.created DESC";
	displayPostsV2( $db, $db2, $sql, $userID, 25, "s", $room_id );
	displayOpInterface( $db, $userID, $room_id );
	// Create an empty style element just to auto-expand the compose window.
	print( "<style onload=\"javascript:toggleComposePane('compose-tools','compose-pane','compose-post');\"></style>\n" );
	// Display "Leave" button
	if( $is_member )
		print( "<div style=\"text-align: right\"><form action=\"room.php\" method=\"get\"><input type=\"hidden\" name=\"i\" value=\"$room_id\" /><input type=\"submit\" name=\"leave\" value=\"Leave room\"></form></div>\n" );
	// Start auto-update of room
	$latest_post_time = get_db_value( $db, "SELECT MAX(created) FROM posts JOIN room_posts ON room_posts.post = posts.id AND room_posts.room = ?", "s", $room_id );
	// Load latest posts every 5 seconds.
	print( "<script type='text/javascript'>\n" .
	       "function loadLatestPostsLoop() {\n" .
		   "  setTimeout(function() {\n" .
		   //"    console.log( 'Loading latest posts.' );\n" .
		   "    loadLatestPosts('$room_id',Math.round(new Date().getTime() / 1000) - 5);\n" .
		   "    loadLatestPostsLoop();\n" .
		   "    }, 5000 );\n" .
		   "  }\n" .
		   "loadLatestPostsLoop();\n" .
		   "</script>\n" );
	}
else
	{
	// All rooms
	displayWorldOrRoomList( $db, "room", "all" );

	if( $userID != "" )
		{
		displayOptionsTable();
		?>
		<p>You will be able to change any of these parameters later.</p>
		<?php
		}
	}

require_once( "footer.php" );
?>
