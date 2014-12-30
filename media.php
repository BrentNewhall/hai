<?php
$media = "images";
if( isset( $_GET["type"] ) )
	$media = $_GET["type"];

$page_title = "Your " . ucwords( $media );
require_once( "database.php" );
require_once( "header.php" );

displayNavbar( $db, $userID );

function getWhereUsed( $db, $filename )
	{
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT id, content FROM posts WHERE content LIKE ?" );
	$search_for = "%" . $filename . "%";
	$stmt->bind_param( "s", $search_for );
	$stmt->execute();
	$stmt->bind_result( $post_id, $post_content );
	$stmt->store_result();
	if( $stmt->num_rows > 0 )
		print( "<br />Used in " );
	while( $stmt->fetch() )
		{
		print( "<a href=\"post.php?i=$post_id\">" . 
		       stripslashes( getPostSnippet( $post_content ) ) .
			   "</a><br />\n" );
		}
	}

print( "<h1>$page_title</h1>\n" );

$sql = "SELECT filename, created FROM user_media WHERE user = ? AND type = ? ORDER BY created DESC";
$stmt = $db->stmt_init();
if( $stmt->prepare( $sql ) )
	{
	$media_single = substr( $media, 0, 5 );
	$stmt->bind_param( "ss", $userID, $media_single );
	$stmt->execute();
	$stmt->bind_result( $filename, $timestamp );
	$stmt->store_result();
	$old_date = "";
	while( $stmt->fetch() )
		{
		$date = date( "d F Y", $timestamp );
		if( $date != $old_date  &&  $timestamp != 0 )
			{
			print( "<h2>$date</h2>\n" );
			$old_date = $date;
			}
		if( $media == "images" )
			{
			print( "<a href=\"assets/images/uploads/$filename\">" .
			       "<img src=\"assets/images/uploads/$filename\" " .
				        "style=\"max-width: 500px; margin-top: 15px\" " .
						"border=\"0\" /></a>\n" );
			print getWhereUsed( $db, $filename );
			}
		else
			{
			$uuid = getGUID();
			print( "<div id=\"$uuid\">Loading the player...</div>" .
			          "<script type=\"text/javascript\">" .
			          "jwplayer(\"$uuid\").setup({" .
			          "file: \"assets/video/uploads/$filename\"," .
			          "image: \"assets/images/video-background.jpg\"," .
			          "width: 500," .
			          "height: 280" .
			          "});" .
			          "</script>" );
			print getWhereUsed( $db, $filename );
			}
		}
	$stmt->close();
	}
require_once( "footer.php" );
?>
