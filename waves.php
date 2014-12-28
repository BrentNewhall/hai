<?php

require_once( "database.php" );

if( isset( $_POST['compose-post'] ) )
	{
	$editable = "0";
	if( isset( $_POST["editable"] )  &&  $_POST["editable"] != "" )
		$editable = 1;
	$comments = "0";
	if( isset( $_POST["comments"] )  &&  $_POST["comments"] != "" )
		$comments = 1;
	$sql = "INSERT INTO posts (id, author, created, content, " .
	                          "parent, public, editable, comments) " .
							  "VALUES (UUID(), ?, ?, ?, '', 0, ?, ?)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "sisii", $userID, time(), $_POST["compose-post"], $editable, $comments );
	$stmt->execute();
	$stmt->close();
	$new_post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ? ORDER BY created DESC LIMIT 1", array( "s", &$userID ) );
	$recipient_id = $_POST["recipient"];
	update_db( $db, "INSERT INTO waves (id, post, recipient) " .
	           "VALUES (UUID(), ?, ?)", "ss",
	           $new_post_id, $recipient_id );
	update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'w', ?, 0)", "sis", $recipient_id, time(), $new_post_id );
	}

$page_title = "Waves";
require_once( "header.php" );

print( "<h1>Waves</h1>" );

displayNavbar( $db, $userID );

if( isset( $_GET["i"] ) )
	{
	// Create an empty style element just to auto-expand the compose window.
	print( "<style onload=\"javascript:toggleComposePane('compose-tools','compose-pane','compose-post');\"></style>\n" );
	// Compose Wave
	displayComposePane( "post", $db, $userID, "", $_GET["i"] );
	}
else
	displayComposePane( "post", $db, $userID, "", " " );

// Display waves
$sql = getStandardSQLselect() .
       "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
       "JOIN waves ON (waves.post = posts.id AND (waves.recipient = ? OR posts.author = ?)) " .
       "ORDER BY posts.created DESC LIMIT $posts_per_page";
	
displayPosts( $db, $db2, $sql, $userID, $posts_per_page, array( "ss", &$userID, &$userID ) );

require_once( "footer.php" );
?>
