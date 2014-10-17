<?php

require_once( "database.php" );
require_once( "functions.php" );

$page_title = "Pings";
require_once( "header.php" );

requireLogin( $db, $db2 );

displayNavbar( $db, $userID );

print( "<div id=\"display-pinged-post\"></div>\n" );

print( "<h1>Pings</h1>\n" );

$sql = "SELECT DISTINCT comments.content, posts.content, posts.id, pings.is_read, pings.created, users.id, users.visible_name, users.real_name, users.profile_public FROM pings JOIN comments ON (comments.id = pings.content_Id) JOIN posts ON (comments.post = posts.id) JOIN users ON (comments.author = users.id) WHERE pings.user = ? ORDER BY pings.created DESC LIMIT 50";
$stmt = $db->stmt_init();
if( $stmt->prepare( $sql ) )
	{
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $comment_content, $post_content, $post_id, $is_read, $ping_time, $author_id, $author_visible_name, $author_real_name, $author_profile_public );
	while( $stmt->fetch() )
		{
		$comment_snippet = stripslashes( getPostSnippet( $comment_content ) );
		$post_snippet = stripslashes( getPostSnippet( $post_content ) );
		$ping_time = getAge( $ping_time );
		if( $is_read == 1 )
			print( "<div class=\"ping ping-read\"\n" );
		else
			print( "<div class=\"ping ping\"" );
		print( " onmouseover=\"javascript:getPostForComment('$post_id','$userID','display-pinged-post');\"" );
		print( " onmouseleave=\"javascript:document.getElementById('display-pinged-post').innerHTML='';\"" );
		print( "><div class=\"timestamp\">$ping_time</div>" );
		print( "In <em><a " );
		if( $is_read == 1 )
			print( "class=\"ping-read\" " );
		print( "href=\"post.php?i=$post_id#main-post\">$post_snippet</a></em><br />\n" );
		print( getAuthorLink( $author_id, $author_visible_name, $author_real_name, $author_profile_public ) );
		print( " wrote, $comment_snippet</div>\n" );
		}
	$stmt->close();
	// Mark all of this user's pings as read
	$stmt = $db->stmt_init();
	$stmt->prepare( "UPDATE pings SET is_read = 1 WHERE user = ?" );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->close();
	}

require_once( "footer.php" );
?>
