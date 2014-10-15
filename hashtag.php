<?php

$page_title = "Hashtag";
$tag = "";
if( isset( $_GET["tag"] )  &&  $_GET["tag"] != "" )
	{
	$tag = $_GET["tag"];
	$page_title = "#" . "$tag - $page_title";
	}
require_once( "header.php" );
require_once( "database.php" );

// Display popular hashtags
function displayPopularHashtags( $db )
	{
	$sql = "SELECT DISTINCT posts.content FROM posts " .
		   "WHERE content LIKE '%#%' AND public = 1 " .
	       "ORDER BY created DESC LIMIT 100";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	#$stmt->bind_param( "s", "%#" . $_GET["tag"] . "%" );
	$stmt->execute();
	$stmt->bind_result( $content );
	$hashtag_counts = array();
	while( $stmt->fetch() )
		{
		// Skip ordered lists
		$matches = array();
		preg_match_all( "/\#([\S]+?)([\s]|$)/", $content, $matches );
		$all_matches = $matches[0];
		$hashtags = $matches[1];
		foreach( $hashtags as $match )
			{
			#print( "<p>" . $match . "</p>\n" );
			if( array_key_exists( $match, $hashtag_counts ) )
				$hashtag_counts[$match]++;
			else
				$hashtag_counts[$match] = 1;
			#print_r( $match );
			}
		}
	$stmt->close();
	// Sort from biggest to smallest, keeping keys in order
	arsort( $hashtag_counts );
	// Pull out just the hashtags, which should be in order
	$hashtags = array_keys( $hashtag_counts );
	// Print out top 10 results
	print( "<div title=\"Among the latest 100 posts, these hashtags show up most often.\">" .
	       "<p><strong>Popular</strong>: \n" );
	for( $i = 0; $i < 10  &&  $i < count($hashtags); $i++ )
		{
		print( "<a href=\"hashtag.php?tag=" . $hashtags[$i] . "\">#" . $hashtags[$i] . "</a> \n" );
		}
	print( "</div>\n" );
	}

displayPopularHashtags( $db );

if( $tag != "" )
	print( "<h1>#" . "$tag</h1>\n" );

if( $userID != "" )
	displayNavbar( $db, $userID );

// Display posts that match that hashtag
$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
	   "JOIN users ON (posts.author = users.id) " .
	   "WHERE posts.content LIKE ? AND posts.public = 1 " .
       "ORDER BY posts.created DESC LIMIT 25";

displayPosts( $db, $db2, $sql, $userID, 25, "s", "%#" . $_GET["tag"] . "%" );

require_once( "footer.php" );
?>
