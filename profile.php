<?php

require_once( "database.php" );

function getStuff( $db, $thing, $userID )
	{
	$output = "";
	$sql = "SELECT $thing, public FROM user_$thing" . "s WHERE user = ?";
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result( $result, $public );
		while( $stmt->fetch() )
			{
			if( $thing == "email"  &&  $public == 1 )
				$output .= "<a href=\"mailto:$result\">$result</a><br />\n";
			elseif( $public == 1 )
				$output .= "$result<br />\n";
			}
		$stmt->close();
		if( $output != "" )
			{
			print( "<div style=\"float: left; margin: 0px 10px 25px 0px;\">\n" .
			       "<h2>" . ucfirst( $thing ) . "s</h2>\n" .
			       "$output" .
			       "</div>\n" );
			}
		}
	}

function getInterest( $db, $name, $sql, $userID )
	{
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result( $id, $item_name );
		if( $stmt->num_rows > 0 )
			{
			print( "<div style=\"float: left; margin: 0px 10px 25px 0px;\">\n" .
				   "<h2>" . ucfirst( $name ) . "s</h2>\n" );
			while( $stmt->fetch() )
				{
				print( "<a href=\"$name.php?i=$id\">$item_name</a><br />\n" );
				}
			print( "</div>\n" );
			}
		}
	}

$page_title = "Profile";
if( isset( $_GET["i"] )  &&  $_GET["i"] != "" )
	{
	$user_id = $_GET["i"];
	$sql = "SELECT visible_name, real_name, profile_public, about FROM users WHERE id = ?";
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $user_id );
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result( $visible_name, $real_name, $profile_public, $about_user );
		$stmt->fetch();
		if( $stmt->num_rows > 0  &&  $profile_public == 1 )
			{
			$stmt->close();
			$page_title = "$visible_name - $page_title";
			require_once( "header.php" );
			print( "<h1>$visible_name</h1>\n" );
			if( $userID != "" )
				displayNavbar( $db, $userID );
			// Get any public information and display that
			if( $about_user != "" )
				print( "<div style=\"margin: 0px 10px 25px 0px;\"><h2>About</h2>\n$about_user</div>\n" );
			print getInterest( $db, "world", "SELECT worlds.id, worlds.display_name FROM worlds JOIN user_worlds ON user_worlds.user = ? AND user_worlds.world = worlds.id WHERE user_worlds.public = 1", $userID );
			print getInterest( $db, "room", "SELECT rooms.id, rooms.name FROM rooms JOIN room_members ON room_members.user = ? AND room_members.room = rooms.id WHERE room_members.public = 1", $userID );
			print( "<br style=\"clear: both\">\n" );
			getStuff( $db, "email", $user_id );
			getStuff( $db, "phone", $user_id );
			$sql = getStandardSQLselect() . 
			       "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
				   "WHERE posts.author = ? " .
			       "ORDER BY posts.created DESC LIMIT 25";
			displayPosts( $db, $db2, $sql, $userID, 25, array( "s", &$_GET["i"] ) );
			}
		}
	}

require_once( "footer.php" );
?>
