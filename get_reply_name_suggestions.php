<?php

// Returns suggested names

require_once( "database.php" );
require_once( "functions.php" );

$partial_name = "";
if( isset( $_GET["n"] ) )
	$partial_name = $_GET["n"];
$text_div = "";
if( isset( $_GET["t"] ) )
	$text_div = $_GET["t"];
$reply_suggestions_div = "";
if( isset( $_GET["d"] ) )
	$reply_suggestions_div = $_GET["d"];

if( $partial_name == "" )
	exit( 0 );

$stmt = $db->stmt_init();
$stmt->prepare( "SELECT id, visible_name, real_name, profile_public FROM users WHERE visible_name LIKE ? ORDER BY visible_name LIMIT 10" );
$n = "%" . $partial_name . "%";
$stmt->bind_param( "s", $n );
$stmt->execute();
$stmt->store_result();
$stmt->bind_result( $id, $visible_name, $real_name, $profile_public );
while( $stmt->fetch() )
	{
	print( "<a href=\"#\" style=\"text-decoration: none; color: black\" onclick=\"javascript:t=document.getElementById('$text_div').value;p=t.lastIndexOf('@');t=t.substr(0,p)+'@&quot;$visible_name&quot;';document.getElementById('$text_div').value=t;document.getElementById('$reply_suggestions_div').innerHTML='';document.getElementById('$text_div').focus();return false;\">" );
	if( file_exists( "assets/images/avatars/$id" ) )
		print( "<img src=\"assets/images/avatars/$id\" width=\"20\" /> " );
	//print( getAuthorLink( $id, $visible_name, $real_name, $profile_public ) );
	if( $visible_name == $real_name )
		print( "<strong>$visible_name</strong>" );
	else
		print( $visible_name );
	print( "</a><br />\n" );
	}
$stmt->close();
?>
