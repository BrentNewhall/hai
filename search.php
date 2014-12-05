<?php

$page_title = "Search";
$query = "";
if( isset( $_GET["q"] )  &&  $_GET["q"] != "" )
	{
	$query = $_GET["q"];
	$page_title = "$query - $page_title";
	}
require_once( "header.php" );
require_once( "database.php" );

displayNavbar( $db, $userID );

if( $query != "" )
	print( "<h1>Search for \"$query\"</h1>\n" );

print( "<p>Searching posts and comments for \"$query\".</p>\n" );

// Display posts that match that hashtag
$sql = getStandardSQLselect() .
       "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
	   "LEFT JOIN comments ON (comments.post = posts.id) " .
	   "WHERE (posts.content LIKE ? OR comments.content LIKE ?) " .
	   "AND posts.public = 1 " .
       "ORDER BY posts.created DESC LIMIT 25";

displayPostsV2( $db, $db2, $sql, $userID, 25, "ss", "%$query%", "%$query%" );

require_once( "footer.php" );
?>
