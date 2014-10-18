<?php

// Returns suggested worlds for a given world name snippet

require_once( "database.php" );
require_once( "functions.php" );

$partial_world_name = "";
if( isset( $_GET["w"] ) )
	$partial_world_name = $_GET["w"];
$world_name_field = "";
if( isset( $_GET["f"] ) )
	$world_name_field = $_GET["f"];

if( $partial_world_name == "" )
	exit( 0 );

$stmt = $db->stmt_init();
$stmt->prepare( "SELECT display_name FROM worlds WHERE display_name LIKE ? ORDER BY display_name LIMIT 10" );
$n = "%" . $partial_world_name . "%";
$stmt->bind_param( "s", $n );
$stmt->execute();
$stmt->store_result();
$stmt->bind_result( $world_name );
while( $stmt->fetch() )
	{
	print( "<a href=\"#\" style=\"text-decoration: none; color: black\" onclick=\"javascript:document.getElementById('$world_name_field').value='$world_name';document.getElementById('world-hints').innerHTML='';return false;\">$world_name</a><br />\n" );
	}
$stmt->close();
?>
