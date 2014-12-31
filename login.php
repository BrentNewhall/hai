<?php
require_once( "database.php" );

if( ! isset( $_POST["username"] ) )
	{
	header( "Location: index.php?error=101\n\n" );  exit(1);
	}
$username = $_POST["username"];
if( $username == "" )
	{
	header( "Location: index.php?error=101\n\n" );  exit(1);
	}

if( ! isset( $_POST["password"] ) )
	{
	header( "Location: index.php?error=102&username=$username\n\n" );  exit(1);
	}
$password = $_POST["password"];
if( $password == "" )
	{
	header( "Location: index.php?error=102&username=$username\n\n" );  exit(1);
	}

if( isset( $_POST["submit"] )  &&  $_POST["submit"] == "Create account" )
	{
	require_once( "functions.php" );
	createAccount( $db, $username, $password );
	exit( 0 );
	}


$stmt = $db->stmt_init();
if( $stmt->prepare( "SELECT username FROM users WHERE username = ? AND password = ?" ) )
	{
	$stmt->bind_param( "ss", $username, crypt( $password, $crypt_salt ) );
	$stmt->execute();
	$stmt->bind_result( $returned_username );
	$stmt->fetch();
	if( $returned_username != $username )
		{
		header( "Location: index.php?error=103&username=$username&returned=$returned_username\n\n" );  exit(1);
		}
	else
		{
		$_SESSION["logged_in"] = $username;
		// If user wants to stay logged in, set a cookie that expires
		// after 30 days.
		if( isset( $_POST["stay-logged-in"] )  &&
		    $_POST["stay-logged-in"] != "" )
			setcookie( "logged_in", $username, time() + 60*60*24*30, "/" );
		header( "Location: index.php\n\n" );  exit(0);
		}
	}
else
	{
	header( "Location: index.php?error=104&username=$username\n\n" );  exit(1);
	}
?>
