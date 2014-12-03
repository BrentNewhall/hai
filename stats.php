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
       getStat( $db, "SELECT COUNT(*) FROM posts", "posts" ) .
	   " and " .
       getStat( $db, "SELECT COUNT(*) FROM comments", "comments" ) .
	   ".</p>\n" .
	   "<p>There are <a href=\"world.php?world=*\">" .
       getStat( $db, "SELECT COUNT(*) FROM worlds", "worlds" ) .
	   "</a> of posts, too.</p>\n" );

$seconds = time() - strtotime( "10 October 2014" ); // Seconds since site creation
$days = intval( $seconds / 86400 ); // Convert to days
print( "<p>Brent started coding Hai $days days ago.</p>\n" );

require_once( "footer.php" );
?>
