<?php

require_once( "database.php" );
require_once( "functions.php" );

$page_title = "Pings";
require_once( "header.php" );

requireLogin( $db, $db2 );

displayNavbar( $db, $userID );

print( "<div id=\"display-pinged-post\"></div>\n" );

print( "<h1>Pings</h1>\n" );

function printPingForPost( $comment_id, $userID, $ping_time, $is_read, $db )
	{
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT DISTINCT comments.content, posts.id, posts.content, users.id, users.visible_name, users.real_name, users.profile_public FROM comments JOIN posts ON (comments.post = posts.id) JOIN users ON (comments.author = users.id) WHERE comments.id = ?" );
	$stmt->bind_param( "s", $comment_id );
	$stmt->execute();
	$stmt->bind_result( $comment_content, $post_id, $post_content, $author_id, $author_visible_name, $author_real_name, $author_profile_public );
	$stmt->fetch();
	$stmt->close();
	$comment_snippet = stripslashes( getPostSnippet( $comment_content ) );
	$post_snippet = stripslashes( getPostSnippet( $post_content ) );
	print( " onmouseover=\"javascript:getPostForComment('$post_id','$userID','display-pinged-post');\"" );
	print( " onmouseleave=\"javascript:document.getElementById('display-pinged-post').innerHTML='';\"" );
	print( "><div class=\"timestamp\">$ping_time</div>" );
	print( "In <em><a " );
	if( $is_read == 1 )
		print( "class=\"ping-read\" " );
	print( "href=\"post.php?i=$post_id#main-post\">$post_snippet</a></em><br />\n" );
	print( getAuthorLink( $author_id, $author_visible_name, $author_real_name, $author_profile_public ) );
	print( " wrote, $comment_snippet\n" );
	}

function printPingForRoom( $room_id, $op_user_id, $ping_time, $is_read, $db, $content_type )
	{
	$room_name = get_db_value( $db, "SELECT name FROM rooms WHERE id = ?", array( "s", &$room_id ) );
	print( "><div class=\"timestamp\">$ping_time</div>" );
	print( "You were " );
	if( $content_type == "ra" )
		print( " added to " );
	elseif( $content_type == "rk" )
		print( " removed from " );
	elseif( $content_type == "ro" )
		print( " made a moderator of " );
	else
		print( " removed as a moderator from " );
	print( "the room <a href=\"room.php?i=$room_id\">$room_name</a>.\n" );
	}

function printPingForMention( $post_id, $user_id, $ping_time, $is_read, $db, $content_type )
	{
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT DISTINCT posts.id, posts.content, users.id, users.visible_name, users.real_name, users.profile_public FROM posts JOIN users ON (posts.author = users.id) WHERE posts.id = ?" );
	print $stmt->error;
	$stmt->bind_param( "s", $post_id );
	$stmt->execute();
	$stmt->bind_result( $post_id, $post_content, $author_id, $author_visible_name, $author_real_name, $author_profile_public );
	$stmt->fetch();
	$stmt->close();
	print( " onmouseover=\"javascript:getPostForComment('$post_id','$userID','display-pinged-post');\"" );
	print( " onmouseleave=\"javascript:document.getElementById('display-pinged-post').innerHTML='';\"" );
	print( "><div class=\"timestamp\">$ping_time</div>" );
	print( "You were mentioned in " );
	print( getAuthorLink( $author_id, $author_visible_name, $author_real_name, $author_profile_public ) );
	print( "'s post <em><a href=\"post.php?i=$post_id\">" . stripslashes( getPostSnippet( $post_content ) ) . "</a></em>.\n" );
	}


//$sql = "SELECT DISTINCT comments.content, posts.content, posts.id, pings.is_read, pings.created, pings.content_type, users.id, users.visible_name, users.real_name, users.profile_public FROM pings JOIN comments ON (comments.id = pings.content_id) JOIN posts ON (comments.post = posts.id) JOIN users ON (comments.author = users.id) WHERE pings.user = ? ORDER BY pings.created DESC LIMIT 50";
$sql = "SELECT DISTINCT id, content_id, content_type, created, is_read FROM pings WHERE pings.user = ? ORDER BY pings.created DESC LIMIT 50";
$stmt = $db->stmt_init();
if( $stmt->prepare( $sql ) )
	{
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $comment_id, $post_id, $content_type, $ping_time, $is_read );
	while( $stmt->fetch() )
		{
		$comment_snippet = stripslashes( getPostSnippet( $comment_content ) );
		$post_snippet = stripslashes( getPostSnippet( $post_content ) );
		$ping_time = getAge( $ping_time );
		if( $is_read == 1 )
			print( "<div class=\"ping ping-read\"" );
		else
			print( "<div class=\"ping ping\"" );
		if( $content_type == "c" )
			{
			printPingForPost( $post_id, $userID, $ping_time, $is_read, $db );
			}
		elseif( substr( $content_type, 0, 1 ) == "r" )
			{
			printPingForRoom( $post_id, $userID, $ping_time, $is_read, $db, $content_type );
			}
		elseif( $content_type == "m" )
			{
			printPingForMention( $post_id, $userID, $ping_time, $is_read, $db, $content_type );
			}
		print( "</div>\n" );
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
