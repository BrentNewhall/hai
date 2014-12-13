<?php

// Exports user data and downloads it to user's computer as "hai-user.xml".

require_once( "database.php" );
require_once( "functions.php" );
if( $userID != "" )
	{
	//if( $xml != "" )
		{
		header( "Content-Description: File Transfer" );
		header( "Content-Type: application/octet-stream" );
		header( "Content-Disposition: attachment; filename=hai-user.xml" );
		header( "Content-Transfer-Encoding: binary" );
		header( "Expires: 0" );
		header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
		header( "Pragma: public" );
		//header( "Content-Length: " . strlen($xml ) );
		ob_clean();
		flush();
		exportAccount( $db, $userID );
		exit( 0 );
		}
	}
?>
