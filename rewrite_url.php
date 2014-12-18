<?php
// Called by .htaccess to rewrite URLs.

$path = strtolower( ltrim( $_SERVER["REQUEST_URI"], "/" ) );
$elements = explode( "/", $path );
// If $path = "/world/hai" then $elements[0] = "world" and $elements[1] = "hai"

// Process world
if( $elements[0] == "world"  ||
    $elements[0] == "worlds" )
	{
	require_once "functions.php";
	$world_name = urldecode( $elements[1] );
	$world_name = processWorldNameForBasic( $world_name );
	$world_id = get_db_value( $db, "SELECT id FROM worlds " .
	                               "WHERE basic_name = ?", array( "s", &$world_name ) );
	if( $world_id != "" )
		{
		//$_GET["i"] = $world_id;
		//require_once "world.php";
		header( "Location: /world.php?i=$world_id\n\n" );
		exit( 0 );
		}
	}
// Process room
elseif( $elements[0] == "room"  ||
        $elements[0] == "rooms" )
	{
	require_once "functions.php";
	$room_name = urldecode( strtolower( $elements[1] ) );
	$room_id = get_db_value( $db, "SELECT id FROM rooms " .
	                              "WHERE LOWER(name) = ?", array( "s", &$room_name ) );
	if( $room_id != "" )
		{
		//$_GET["i"] = $room_id;
		//require_once "room.php";
		header( "Location: /room.php?i=$room_id\n\n" );
		exit( 0 );
		}
	}
// Process user profile
elseif( $elements[0] == "user" )
	{
	require_once "functions.php";
	$username = urldecode( $elements[1] );
	$user_id = get_db_value( $db, "SELECT id FROM users " .
	                              "WHERE username = ?", array( "s", &$username ) );
	if( $user_id != "" )
		{
		//$_GET["i"] = $user_id;
		//require_once "profile.php";
		header( "Location: /profile.php?i=$user_id\n\n" );
		exit( 0 );
		}
	}

// If nothing found, just redirect to the main page.
header( "Location: /index.php\n\n" );
?>
