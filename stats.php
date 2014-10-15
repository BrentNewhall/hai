<?php

$page_title = "Stats";
require_once( "header.php" );
require_once( "database.php" );

print( "<h1>Stats</h1>\n" );

if( $userID != "" )
	displayNavbar( $db, $userID );

// Display posts that match that hashtag
function getStat( $db, $sql, $name )
	{
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->execute();
	$stmt->bind_result( $count );
	$stmt->fetch();
	return( "$count $name" );
	}
print( "<p>" .  getStat( $db, "SELECT COUNT(*) FROM users", "users" ) .
       " have written " .
       getStat( $db, "SELECT COUNT(*) FROM comments", "comments" ) .
	   " among " .
       getStat( $db, "SELECT COUNT(*) FROM posts", "posts" ) .
	   ".</p>\n" .
	   "<p>There are <a href=\"world.php?world=*\">" .
       getStat( $db, "SELECT COUNT(*) FROM worlds", "worlds" ) .
	   "</a> of posts, too.</p>\n" );

require_once( "footer.php" );
?>
