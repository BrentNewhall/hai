<?php
// Connect to database.
DEFINE( "PROD_DEV", "TEST" );
require_once( '../hai_db.cfg' );
$db = new mysqli( DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME );
if( $db->connect_error )
	die( "<html><body>Could not connect to the database. Please <a href=\"mailto:brent@brentnewhall.com\">email brent@brentnewhall.com</a>.</body></html>\n" );
$db2 = new mysqli( DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME );

require_once( "functions.php" );

$start_time = 1412898245; // A convenient time for the beginning of time. All other times are calculated off this.
create_db( $db, $start_time );

// insertPost();
update_db( $db, "INSERT INTO users (id, username, visible_name, password, real_name, created, paid, profile_public, admin) VALUES (UUID(), 'Admin', 'Admin', 'blah', 'Admin', $start_time, 0, 1, 1)" );
$user_id = get_db_value( $db, "SELECT id FROM users WHERE real_name = 'Admin'" );
insertPost( $db, $user_id, "Adding basic content.", ' ', 1, '0' );
$result = get_db_value( $db, "SELECT content FROM posts WHERE author = ?", "s", $user_id );
$post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ?", "s", $user_id );
displayResult( "insertPost", "Expected content to match.", "Adding basic content.", $result );
insertPost( $db, $user_id, "Adding basic content.", ' ', 1, 1, "TestWorld" );
$basic_world_name = get_db_value( $db, "SELECT basic_name FROM worlds WHERE display_name = ?", "s", "TestWorld" );
displayResult( "insertPost", "Expected new world name to be added.", "testworld", $basic_world_name );
$world_id = get_db_value( $db, "SELECT id FROM worlds WHERE display_name = ?", "s", "TestWorld" );
$world_post_id = get_db_value( $db, "SELECT post FROM world_posts WHERE world = ?", "s", $world_id );
displayResult( "insertPost", "Expected world post ID and post ID to match.", $post_id, $world_post_id );
// ??? ADD TEST FOR PINGS ???

// editPost();
$old_content = "Edit test.";
update_db( $db, "INSERT INTO users (id, username, visible_name, password, real_name, created, paid, profile_public, admin) VALUES (UUID(), 'Ronny', 'Ronny', 'blah', 'Ronny', $start_time, 0, 1, 1)" );
$user_id = get_db_value( $db, "SELECT id FROM users WHERE real_name = 'Ronny'" );
insertPost( $db, $user_id, $old_content, ' ', 1, 1, "OldWorld" );
$post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ?", "s", $user_id );
$old_world_id = get_db_value( $db, "SELECT world FROM world_posts WHERE post = ?", "s", $post_id );
editPost( $db, $user_id, $post_id, "New content.", "NewWorld", 1 );
$new_content = get_db_value( $db, "SELECT content FROM posts WHERE id = ?", "s", $post_id );
$new_world_name = get_db_value( $db, "SELECT worlds.display_name FROM worlds JOIN world_posts ON (world_posts.world = worlds.id AND world_posts.post = ?)", "s", $post_id );
displayResult( "editPost", "Expected content to change.", "New content.", $new_content );
displayResult( "editPost", "Expected world to change.", "NewWorld", $new_world_name );


// getLogin()
$result = getLogin( $db, $db2 );
$expected = "";
displayResult( "getLogin", "Login code not as expected.", $expected, $result );

?>
<html>
<body style="background-color: green; color: white">
OK
</body>
</html>
<?php

function create_db( $db, $start_time )
	{
	$result = $db->query( "DROP TABLE users" );
	$result = $db->query( 'CREATE TABLE users (id CHAR(36) NOT NULL PRIMARY KEY, username TEXT NOT NULL, visible_name TEXT NOT NULL, password TEXT NOT NULL, real_name TEXT, created INTEGER NOT NULL, paid BOOLEAN NOT NULL, profile_public BOOLEAN NOT NULL, about TEXT, admin BOOLEAN NOT NULL)' );
	$result = $db->query( "DROP TABLE posts" );
	$result = $db->query( 'CREATE TABLE posts (id CHAR(36) NOT NULL PRIMARY KEY, created INT NOT NULL, author CHAR(36) NOT NULL, content TEXT NOT NULL, parent CHAR(36), public BOOLEAN, editable BOOLEAN)' );
	$result = $db->query( "DROP TABLE worlds" );
	$result = $db->query( 'CREATE TABLE worlds (id CHAR(36) NOT NULL PRIMARY KEY, basic_name VARCHAR(50) NOT NULL, display_name VARCHAR(50) NOT NULL, class CHAR(36))' );
	$result = $db->query( "DROP TABLE world_posts" );
	$result = $db->query( 'CREATE TABLE world_posts (id CHAR(36) NOT NULL PRIMARY KEY, world CHAR(36) NOT NULL, post CHAR(36) NOT NULL)' );
	$result = $db->query( "DROP TABLE post_history" );
	$result = $db->query( 'CREATE TABLE post_history (id CHAR(36) NOT NULL PRIMARY KEY, post CHAR(36) NOT NULL, author CHAR(36) NOT NULL, edited INT(10) UNSIGNED, original_content TEXT)' );
	}

function displayResult( $function_name, $message, $expected_result, $actual_result )
	{
	if( $expected_result != $actual_result )
		{
		print( "<html><body style=\"background-color: red; color: white\">\n" .
		       "<h1>$function_name</h1>\n" .
			   "<p>$message</p>\n" .
			   "<h2>Expected</h2>\n" .
			   "<pre>" . htmlentities( $expected_result ) . "</pre>\n" .
			   "<h2>Actual</h2>\n" .
			   "<pre>" . htmlentities( $actual_result ) . "</pre>\n" .
			   "</body></html>\n" );
		exit( 0 );
		}
	}
?>
