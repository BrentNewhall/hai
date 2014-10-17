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

$page_title = "Profile";
if( isset( $_GET["i"] )  &&  $_GET["i"] != "" )
	{
	$user_id = $_GET["i"];
	$sql = "SELECT visible_name, real_name, profile_public FROM users WHERE id = ?";
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $user_id );
		$stmt->execute();
		$stmt->store_result();
		$stmt->bind_result( $visible_name, $real_name, $profile_public );
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
			getStuff( $db, "email", $user_id );
			getStuff( $db, "phone", $user_id );
			$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
				   "JOIN users ON (posts.author = users.id) " .
				   "WHERE posts.author = ? " .
			       "ORDER BY posts.created DESC LIMIT 25";
			displayPosts( $db, $db2, $sql, $userID, 25, "s", $_GET["i"] );
			}
		}
	}

require_once( "footer.php" );
?>
