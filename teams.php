<?php
require_once( "database.php" );
if( isset( $_POST["rename-team"] )  &&  $userID != "" )
	{
	print( "Checking...<br>\n" );
	$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", "s", $_POST["t"] );
	if( $team_name != "" )
		{
		$new_name = strip_tags( $_POST["new-name"] );
		print( "New name $new_name<br>\n" );
		$stmt = $db->stmt_init();
		$sql = "UPDATE user_teams SET name = ? WHERE id = ?";
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $new_name, $_POST["t"] );
		$stmt->execute();
		$stmt->close();
		unset( $_POST["t"] );
		}
	}

$team_name = "";
if( isset( $_GET["t"] )  &&  $_GET["t"] != "" )
	$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", "s", $_GET["t"] );
if( $team_name != "" )
	$page_title = "$team_name Team";
else
	$page_title = "Teams";
require_once( "header.php" );


if( isset( $_POST["update-team"] )  &&  $userID != "" )
	{
	$members = $_POST;
	unset( $members["update-team"] );
	unset( $members["team-id"] );
	$stmt = $db->stmt_init();
	$sql = "DELETE FROM user_team_members WHERE team = ?";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $_POST["team-id"] );
	$stmt->execute();
	$stmt->close();
	foreach( array_keys( $members ) as $user_id )
		{
		$found = get_db_value( $db, "SELECT COUNT(*) FROM users WHERE id = ?", "s", $user_id );
		if( $found == 1 )
			{
			$stmt = $db->stmt_init();
			$sql = "INSERT INTO user_team_members (team, user) VALUES (?, ?)";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ss", $_POST["team-id"], $user_id );
			$stmt->execute();
			}
		}
	}

requireLogin( $db, $db2 );

displayNavbar( $db, $userID );

print( "<h1>$page_title</h1>\n" );
?>
<?php

if( isset( $_POST["new-team"] )  &&
   $_POST["new-team"] != "" )
   	{
	$invalid_team_names = array( "", "All", "Global", "Public" );
	$team_name = $_POST["new-team"];
	$stmt = $db->stmt_init();
	$sql = "SELECT name FROM user_teams WHERE user = ? AND name = ?";
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $userID, $team_name );
	$stmt->execute();
	$stmt->bind_result( $returned_team_name );
	$stmt->fetch();
	if( $returned_team_name == $team_name )
		print( "<p class=\"error\">You already have a team with that name. Please choose a different name.</p>\n" );
	elseif( in_array( $team_name, $invalid_team_names ) )
		print( "<p class=\"error\">You cannot create a team with that name.</p>\n" );
	else
		{
		$stmt = $db->stmt_init();
		$sql = "INSERT INTO user_teams (id, user, name) VALUES (UUID(), ?, ?)";
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $userID, $team_name );
		$stmt->execute();
		$stmt->close();
		}
	}

if( isset( $_GET["t"] )  &&  $_GET["t"] != "" )
	{
	$team_name = get_db_value( $db, "SELECT name FROM user_teams WHERE id = ?", "s", $_GET["t"] );
	if( $team_name != "" )
		{
		print( "<form action=\"teams.php\" method=\"post\">\n" );
		print( "<input type=\"hidden\" name=\"team-id\" value=\"" . $_GET["t"] . "\" />\n" );
		$stmt = $db->stmt_init();
		$sql = "SELECT users.id, users.username, users.visible_name, users.profile_public FROM user_team_members, users WHERE user_team_members.team = ? AND user_team_members.user = users.id ORDER BY users.visible_name LIMIT 500";
		$stmt->prepare( $sql );
		$stmt->bind_param( "s", $_GET["t"] );
		$stmt->execute();
		$stmt->bind_result( $user_id, $username, $visible_name, $profile_public );
		while( $stmt->fetch() )
			{
			print( "<input type=\"checkbox\" name=\"$user_id\" value=\"$user_id\" checked=\"checked\" /> " );
			if( $profile_public == 1 )
				print( "<a href=\"profile.php?i=$user_id\">$visible_name</a><br />\n" );
			else
				print( "$visible_name<br />\n" );
			}
		$stmt->close();
		print( "<input type=\"submit\" name=\"update-team\" value=\"Update membership\">\n" );
		print( "</form>\n" );
		//print( "<br />\n" );
		print( "<br />\n" );
		}
	}
else
	{
	$teams = array();
	$stmt = $db->stmt_init();
	$sql = "SELECT id, name FROM user_teams WHERE user = ? ORDER BY name";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $team_id, $team_name );
	while( $stmt->fetch() )
		{
		$teams[$team_id] = $team_name;
		}
	$stmt->close();
	foreach( array_keys( $teams ) as $team_id )
		{
		$stmt = $db->stmt_init();
		$sql = "SELECT users.id, users.username, users.visible_name, users.profile_public FROM user_team_members, users WHERE user_team_members.team = ? AND user_team_members.user = users.id ORDER BY users.visible_name LIMIT 5";
		$stmt->prepare( $sql );
		$stmt->bind_param( "s", $team_id );
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result( $user_id, $username, $user_visible_name, $profile_public );
		$membership_count = $stmt->num_rows;
		print( "<p><a href=\"teams.php?t=$team_id\">" . $teams[$team_id] . "</a> ($membership_count members" );
		if( $membership_count > 0 )
			{
			print( ": " );
			$members = array();
			while( $stmt->fetch() )
				{
				if( $profile_public == 1 )
					array_push( $members, "<a href=\"profile.php?i=$user_id\">$user_visible_name</a>" );
				else
					array_push( $members, $user_visible_name );
				}
			print( implode( ", ", $members ) );
			if( $membership_count > 5 )
				print( "..." );
			}
		$stmt->close();
		print( ") </p>\n" );
		//print( "<p><a href=\"teams.php?g=$team_id\">" . $teams[$team_id] . "</a> ($membership_count members)</p>\n" );
		}
	}

if( isset( $_GET["t"] )  ||  isset( $_POST["t"] ) )
	{
	print( "<form action='teams.php' method='post'>\n" .
		   "<input type=\"hidden\" name=\"t\" value=\"" . $_GET["t"] . "\" />\n" .
		   "<input type=\"submit\" name=\"rename-team\" value=\"Rename\" />\n" .
		   "<input type=\"text\" name=\"new-name\" value=\"$team_name\" />\n" .
	       "</form>\n" );
	}
else
	{
	?>
	<form action="teams.php" method="post">
	<input type="submit" value="Add team" />
	<input type="text" name="new-team" size="20" />
	</form>
	<?php
	}

require_once( "footer.php" );
?>
