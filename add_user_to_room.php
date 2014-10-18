<?php

require_once( "database.php" );

$page_title = "Add User to Room";
$room_id = "";
$room_name = "";
if( ( isset( $_GET["i"] )  &&  $_GET["i"] != "" )  ||
    ( isset( $_POST["i"] )  &&  $_POST["i"] != "" ) )
	{
	if( isset( $_GET["i"] ) )
		$room_id = $_GET["i"];
	else
		$room_id = $_POST["i"];
	$room_name   = get_db_value( $db, "SELECT name FROM rooms WHERE id = ?", "s", $room_id );
	$room_hidden = get_db_value( $db, "SELECT hidden FROM rooms WHERE id = ?", "s", $room_id );
	if( $room_hidden == 1  ||  $room_name == "" )
		{
		// This room is hidden or doesn't exist, so pretend it doesn't exist.
		if( $room_name != "" )
			header( "Location: room.php?i=$room_id\n\n" );
		else
			header( "Location: room.php\n\n" );
		exit( 0 );
		}
	$page_title = "$room_name - Add User";
	}

// Join a room
if( isset( $_POST["users"] )  &&  $userID != ""  &&  $room_id != "" )
	{
	$invite_only = get_db_value( $db, "SELECT invite_only FROM rooms WHERE id = ?", "s", $room_id );
	if( $invite_only == 0 )
		{
		$users = $_POST["users"];
		// Add all users in list, if they're not already members
		foreach( $users as $user_id )
			{
			$in_room = get_db_value( $db, "SELECT id FROM room_members WHERE room = ? AND user = ?", "ss", $room_id, $user_id );
			//print( "Room id $room_id, user id $user_id, in room $in_room<br>\n" );
			if( $in_room == "" )
				{
				update_db( $db, "INSERT INTO room_members (id, room, user, op) VALUES (UUID(), ?, ?, 0)", "ss", $room_id, $user_id );
				update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'ra', ?, 0)", "sis", $user_id, time(), $room_id );
				}
			}
		/* $sql = "INSERT INTO room_members (id, room, user, op) VALUES (UUID(), ?, ?, 0)";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $room_id, $userID );
		$stmt->execute();
		$stmt->close(); */
		header( "Location: room.php?i=$room_id\n\n" );
		exit( 0 );
		}
	}

require_once( "header.php" );

requireLogin( $db, $db2 );

if( $userID != "" )
	displayNavbar( $db, $userID );

print( "<h1>Add user to room</h1>\n" );

$name_query = "";

if( isset( $_GET["name-query"] ) )
	{
	$name_query = $_GET["name-query"];
	print( "<form action=\"add_user_to_room.php\" method=\"post\">\n" );
	print( "<input type=\"hidden\" name=\"i\" value=\"$room_id\" />\n" );
	$sql = "SELECT users.id, username, visible_name, real_name, profile_public FROM users WHERE visible_name LIKE ? AND users.id NOT IN (SELECT user FROM room_members WHERE room = ?)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$q = "%$name_query%";
	$stmt->bind_param( "ss", $q, $room_id );
	$stmt->execute();
	$stmt->bind_result( $user_id, $username, $user_visible_name, $user_real_name, $user_profile_public );
	while( $stmt->fetch() )
		{
		print( "<input type=\"checkbox\" id=\"user-checkbox-$user_id\" name=\"users[]\" value=\"$user_id\" /> " );
		print( "<label for=\"user-checkbox-$user_id\">" );
		print( getAuthorLink( $user_id, $user_visible_name, $user_real_name, $user_profile_public ) );
		print( "</label><br />\n" );
		}
	if( $stmt->num_rows > 0 )
		print( "<input type=\"submit\" value=\"Add selected users\" />\n" );
	$stmt->close();
	print( "</form>\n" );
	}

?>
<form action="add_user_to_room.php" method="get">
<input type="hidden" name="i" value="<?php echo $room_id; ?>" />
Search for users to add to this room: <input type="text" name="name-query" value="<?php echo $name_query; ?>" />
<input type="submit" value="Search" />
</form>
<?php

require_once( "footer.php" );
?>
