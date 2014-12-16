<?php
require_once( "functions.php" );

if( ! isset( $_FILES["account_xml_file"]["tmp_name"] ) )
	{
	header( "Location: account.php\n\n" );
	exit( 0 );
	}

$fp = fopen( $_FILES["account_xml_file"]["tmp_name"], "r" );
if( $fp )
	{
	importAccount( $db, $userID, $fp );
	}

// Note that we never call move_uploaded_file() on the file;
// we let it be deleted at the end of the request.

header( "Location: index.php?message=The%20file%20has%20been%20imported\n\n" );

?>
