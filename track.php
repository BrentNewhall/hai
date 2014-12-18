<?php

// Track a post

require_once( "database.php" );

// Get post ID. If obviously invalid, kick back to main page.
if( ! isset( $_GET["i"] ) )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }
$post_id = $_GET["i"];
if( $post_id == ""  || strlen($post_id) != 36 )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }
if( $userID == "" )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }

// As long as monitor doesn't already exist...
$track_exists = get_db_value( $db, "SELECT id FROM tracking WHERE post = ? AND user = ?", array( "ss", &$post_id, &$userID ) );
if( $track_exists != "" )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }

// Add monitor.
update_db( $db, "INSERT INTO tracking (id, post, user) VALUES (UUID(), ?, ?)", "ss", $post_id, $userID );

if( isset( $_GET["redirect"] )  &&  $_GET["redirect"] != "" )
	header( "Location: " . $_GET["redirect"] . "\n\n" );
else
	header( "Location: index.php\n\n" );
?>
