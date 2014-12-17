<?php

// Block a user

require_once( "database.php" );
require_once( "functions.php" );
$user_id_to_block = "";
if( isset( $_GET["u"] ) )
	$user_id_to_block = $_GET["u"];

if( $userID != ""  &&  $user_id_to_block != ""  &&  isset( $_GET["unblock"] ) )
	{
	// Unblock
	update_db( $db, "DELETE FROM blocks WHERE blocker = ? AND troll = ?",
	                "ss", $userID, $user_id_to_block );
	header( "Location: account.php\n\n" );
	exit( 0 );
	}
if( $userID != ""  &&  $user_id_to_block != "" )
	{
	// Block
	update_db( $db, "INSERT INTO blocks (id, blocker, troll) " .
	                "VALUES (UUID(), ?, ?)", "ss", $userID, $user_id_to_block );
	header( "Location: index.php\n\n" );
	exit( 0 );
	}
?>
