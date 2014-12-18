<?php

// Returns the security question for a user

require_once( "database.php" );
require_once( "functions.php" );

if( isset( $_GET["u"] ) )
	{
	$username = $_GET["u"];
	$question = get_db_value( $db, "SELECT question FROM security_questions JOIN users ON security_questions.user = users.id WHERE users.username = ?", array( "s", & $username ) );
	print $question;
	}
?>
