<?php

require_once( "database.php" );

$posts_per_page = 25;

function displayComposePane( $flavor, $db, $userID, $post_id = "" )
	{
	// Display form and table for composing a message.
	// $flavor = "post" or "comment"
	// $post_id = ID of parent post, if composing a comment
	$button_area_width = 175;
	$width = 500;
	$height = 350;
	$collapsed_height = 75;
	$write_button_label = "Write";
	$compose_id = "compose-post";
	$compose_class = "compose-post";
	$preview_id = "post-preview";
	$preview_class = "post-preview";
	$tools_id = "compose-tools";
	$compose_pane_id = "compose-pane";
	$in_reply_to_id = "reply-to";
	if( $flavor == "comment" )
		{
		$button_area_width = 100;
		$width = 400;
		$height = 100;
		$collapsed_height = 25;
		$write_button_label = "Reply";
		$compose_id = "compose-comment-$post_id";
		$compose_class = "compose-comment";
		$preview_id = "comment-preview-$post_id";
		$preview_class = "comment-preview";
		$tools_id = "compose-tools-$post_id";
		$compose_pane_id = "compose-pane-$post_id";
		$in_reply_to_id = "reply-to-$post_id";
		}
	elseif( $flavor == "room" )
		{
		$button_area_width = 100;
		$width = 400;
		$height = 150;
		$compose_class = "compose-room";
		$preview_class = "preview-room";
		}
	if( $flavor == "room" )
		print( "<form action=\"room.php\" method=\"post\">\n" );
	else
		print( "<form action=\"index.php\" method=\"post\">\n" );
	
	if( isset( $_GET["tab"] ) )
		print( "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?tab=" . $_GET["tab"] . "\" />\n" );
	elseif( isset( $_GET["i"] ) )
		print( "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?i=" . $_GET["i"] . "\" />\n" );
	else
		print( "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "\" />\n" );
	if( $flavor == "comment" )
		print( "<input type='hidden' name='post-id' value='$post_id' />\n" );
	if( $flavor == "room" )
		print( "<input type='hidden' name='room-id' value='$post_id' />\n" );
	?>
	<div style="display: table">
		<div style="display: table-row">
			<div style="display: table-cell; width: <?php echo $button_area_width; ?>px; height: <?php echo $collapsed_height; ?>px; text-align: center; vertical-align: middle;" id="<?php echo $tools_id; ?>">
				<button onclick="javascript:toggleComposePane('<?php echo $tools_id; ?>','<?php echo $compose_pane_id; ?>','<?php echo $compose_id; ?>'); return false;"><?php echo $write_button_label; ?></button>
			</div>
			<div style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px; background-color: white; display: none" id="<?php echo $compose_pane_id; ?>">
				<textarea class="<?php echo $compose_class; ?>" name="compose-post" id="<?php echo $compose_id; ?>" onkeyup="javascript:updatePreview('<?php echo $compose_id; ?>','<?php echo $preview_id; ?>');" /></textarea><br />
				<?php
				if( $flavor == "post" )
					{
					// Get world name, if applicable
					$world_value = "";
					if( isset( $_GET["i"] )  && $_GET["i"] != "" )
						{
						$world_value = get_db_value( $db, "SELECT display_name FROM worlds WHERE id = ?", "s", $_GET["i"] );
						}
					?>
					World: <input type="text" name="post-world" id="post-world" value="<?php echo $world_value; ?>" size="30" title="A topic of conversation" onchange="javascript:displayWorldSuggestions('post-world','post-restrictions','set-post-public');" onkeyup="javascript:displayWorldSuggestions('post-world','post-restrictions', 'set-post-public' );" />
					<input type="checkbox" name="public" id="set-post-public" checked="yes" value="yes" onchange="javascript:displayWorldSuggestions('post-world', 'post-restrictions', 'set-post-public' );" /> <label for="set-post-public" title="Public posts show up in both the world and your general feed. If unchecked, the post only shows up in the world.">Public</label><br />
					<?php
					} // end if flavor == "post"
				?>
				<div id="<?php echo $in_reply_to_id; ?>" style="display: none"></div>
				<div class="<?php echo $preview_class; ?>" id="<?php echo $preview_id; ?>"></div>
				<div id="post-restrictions" class="post-restrictions"><?php
				if( $world_value == "" )
					print( "This post will appear in the \"Everything\" stream, and in the streams of anyone who's added you to a Team." );
				else
					print( "This post will appear in the \"$world_value\" World." );
				?>
				</div>
			</div>
		</div>
	</div>
	</form>
	<?php
	}

function getAge( $timestamp )
	{
	$age = "";
	$minutes = intval( (time() - $timestamp) / 60 ); // Minutes
	if( $minutes < 1 )
		$age = "&lt;1m";
	elseif( $minutes < 60 )
		$age = $minutes . "m";
	else
		{
		$hours = intval( $minutes / 60 ); // Hours
		if( $hours < 24 )
			$age = $hours . "h " . intval($minutes - ($hours*60)) . "m";
			//$age = $hours . "h";
		else
			{
			$days = intval( $minutes / 60 / 24 ); // Days
			if( $days < 7 )
				$age = $days . "d " . intval($hours - ($days*24)) . "h";
			else
				{
				$weeks = intval( $minutes / 60 / 24 / 7 ); // Weeks
				if( $weeks < 4 )
					$age = $weeks . "w";
				else
					{
					$months = intval( $minutes / 60 / 24 / 30 ); // Months
					$age = $months . "M";
					}
				}
			}
		}
	return $age;
	}





function printError( $code )
	{
	$code = intval( $code );
	$errors = array();
	$errors[101] = "You must enter a username.";
	$errors[102] = "You must enter a password.";
	$errors[103] = "That username or password were not found in the database.";
	$errors[104] = "Error connecting to the database. Please try again later.";
	$errors[150] = "Usernames can only contain letters, numbers, underscores (_) and dashes (-). Please try another username.";
	$errors[151] = "That username already exists on Hai. Please choose another username.";
	$errors[152] = "Passwords must be at least 8 characters and must contain at least one upper-case letter, at least one number, and at least one symbol.";
	$errors[201] = "That post does not exist.";
	$errors[202] = "You can't delete that post.";
	$errors[301] = "A room named <a href=\"room.php?r=" . $_GET["room_id"] . "\">" . $_GET["room_name"] . "</a> already exists. Please choose another name.";
	$errors[302] = "You cannot create a room with that name. Please choose another name.";
	if( array_key_exists( $code, $errors ) )
		print( "<p class=\"error\">" . $errors[$code] . "</p>\n" );
	}



function displayNavbar( $db, $userID )
	{
	global $page_title;
	?>
	<div id="navbar">
	<?php
	$unread_pings = get_db_value( $db, "SELECT COUNT(*) FROM pings WHERE user = ? AND is_read = 0", "s", $userID );
	print( "<p><a" );
	if( $_SERVER["PHP_SELF"] == "/pings.php" )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"Notifications of new comments on posts you wrote or commented on.\" href=\"pings.php\">Pings" );
	if( $unread_pings > 0 )
		print( " <strong>($unread_pings)</strong>" );
	print( "</a></p>\n" );
	print( "<p" );
	if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"All posts marked public, from everyone. Kinda like Twitter!\"><a href=\"index.php?tab=Everything\">Everything</a></p>\n" );
	// Teams
	print( "<p><a title=\"Modify membership of your teams and create new teams.\" href=\"teams.php\">Teams</a></p>\n" );
	print( "<p" );
	if( $page_title == "Home"  &&  ! isset( $_GET["tab"] ) )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"Posts from anyone in any of your teams.\" class=\"view-content\"><a href=\"index.php\">All</a></p>\n" );
	$stmt = $db->stmt_init();
	$sql = "SELECT id, name FROM user_teams WHERE user = ? ORDER BY name";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $team_id, $team_name );
	while( $stmt->fetch() )
		{
		print( "<p class=\"view-content\"" );
		if( $page_title == "Home"  &&
		    isset( $_GET["tab"] )  &&  $_GET["tab"] == $team_id )
			print( " style=\"font-weight: bold\"" );
		print( "><a title=\"Posts from anyone in your '$team_name' team.\" href=\"index.php?tab=$team_id\">$team_name</a>" );
		print( "</p>\n" );
		}
	// Worlds
	print( "<p" );
	if( $_SERVER["PHP_SELF"] == "/world.php"  &&
	    ( isset( $_GET["i"] )  &&  $_GET["i"] == "*" ) )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"View topics of conversation.\"><a href=\"world.php?world=*\">Worlds</a></p>\n" );
	$stmt = $db->stmt_init();
	$sql = "SELECT worlds.id, worlds.display_name FROM user_worlds, worlds WHERE user_worlds.world = worlds.id AND user_worlds.user = ? ORDER BY worlds.display_name";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $world_id, $world_name );
	while( $stmt->fetch() )
		{
		print( "<p class=\"view-content\"" );
		if( $_SERVER["PHP_SELF"] == "/world.php"  &&
		    ( isset( $_GET["i"] )  &&  $_GET["i"] == $world_id ) )
			print( " style=\"font-weight: bold\"" );
		print( "><a title=\"Posts in the '$world_name' world.\" href=\"world.php?i=$world_id\">$world_name</a>" );
		print( "</p>\n" );
		}
	// Rooms
	print( "<p" );
	if( $_SERVER["PHP_SELF"] == "/room.php"  &&
	    ( isset( $_GET["i"] )  &&  $_GET["i"] == "*" ) )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"Browse private areas of conversation.\"><a href=\"room.php\">Rooms</a></p>\n" );
	$stmt = $db->stmt_init();
	$sql = "SELECT rooms.id, rooms.name FROM room_members JOIN rooms ON (room_members.room = rooms.id) WHERE room_members.user = ? ORDER BY rooms.name";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $room_id, $room_name );
	while( $stmt->fetch() )
		{
		$room_short_name = $room_name;
		if( strlen($room_short_name) > 15 )
			{
			$room_short_name = substr( $room_short_name, 0, 15 ) . "...";
			}
		print( "<p class=\"view-content\"" );
		if( $_SERVER["PHP_SELF"] == "/room.php"  &&
		    ( ( isset( $_GET["i"] )  &&  $_GET["i"] == $room_id )  ||
		      ( isset( $_POST["room-id"] )  &&  $_POST["room-id"] == $room_id )  ) )
			print( " style=\"font-weight: bold\"" );
		print( "><a title=\"Posts in the '$room_name' room.\" href=\"room.php?i=$room_id\">$room_short_name</a>" );
		print( "</p>\n" );
		}
	// Hashtags
	print( "<p><a " );
	if( $_SERVER["PHP_SELF"] == "/hashtag.php" )
		print( " style=\"font-weight: bold\"" );
	print( "href=\"hashtag.php\">Hashtags</a></p>\n" );
	?>
	<p><a href="account.php">Account</a></p>
	<p><a href="logout.php">Logout</a></p>
	</div>
	<div id="formatting-hints">
	<p>Post Formatting:</p>
	<p><em>''italics''</em>, <em>*italics*</em>, <em>_italics_</em>, <em>[i]italics[/i]</em>, <strong>'''bold'''</strong>, <strong>**bold**</strong>, <strong>__bold__</strong>, <strong>[b]bold[/b]</strong>, <span style="text-decoration: underline">[u]underline[/u]</span>, <span style="color: blue">[color=blue]color[/color]</span>, <span style="font-size: 8pt">[size=8]size[/size]</span>, <a href="mailto:me@me.com">[email]me@me.com<br />[/email]</a>, <a href="http://test.com">http://test.com</a>.</p>
	<p>Start a line with<br />"* " for a bulleted list; "# " for a numbered list.</p>
	<p>Web, image, and YouTube addresses are automatically embedded.</p>
	<p><a href="formatting.php">More info</a></p>
	</div>
	<?php
	}




function getAuthorLink( $id, $visible_name, $real_name, $profile_public )
	{
	$result = "";
	if( $profile_public )
		$result .= "<a href=\"profile.php?i=$id\" ";
	else
		$result .= "<span ";
	if( $visible_name == $real_name )
		$result .= "class=\"uses-real-name\" ";
	$result .= ">" . $visible_name;
	if( $profile_public )
		$result .= "</a>";
	else
		$result .= "</span>";
	return $result;
	}




function printAuthorInfo( $db, $userID, $author_id, $author_username, $author_visible_name, $author_real_name, $author_public, $post_id, $type = "full" )
	{
	if( $type == "full" )
		{
		$avatar_size = 50;
		$author_class = "author";
		}
	else
		{
		$author_class = "comment-author";
		$avatar_size = 30;
		}
	$stmt = $db->stmt_init();
	// Build list of groups to which this person applies
	$group_names_array = array();
	$stmt->prepare( "SELECT name FROM user_teams JOIN user_team_members ON (user_teams.id = user_team_members.team AND user_team_members.user = ?) WHERE user_teams.user = ?" );
	print $db->error;
	$stmt->bind_param( "ss", $author_id, $userID);
	$stmt->execute();
	$stmt->bind_result( $group_name );
	while( $stmt->fetch() )
		{
		array_push( $group_names_array, $group_name );
		}
	$group_names = implode( ", ", $group_names_array );
	// Build list of all groups
	$all_groups = "";
	$all_groups = "<form action=\"index.php\" method=\"post\">\n" .
	              "<input type=\"hidden\" name=\"action\" value=\"update-group-membership\" />\n" .
	              "<input type=\"hidden\" name=\"user\" value=\"$author_id\" />\n";
	if( isset( $_GET["tab"] ) )
		$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?tab=" . $_GET["tab"] . "\" />\n";
	elseif( isset( $_GET["i"] ) )
		$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?i=" . $_GET["i"] . "\" />\n";
	else
		$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "\" />\n";
	$stmt->prepare( "SELECT id, name FROM user_teams WHERE user = ?" );
	$stmt->bind_param( "s", $userID);
	$stmt->execute();
	$stmt->bind_result( $group_id, $group_name );
	while( $stmt->fetch() )
		{
		$all_groups .= "<input type=\"checkbox\" id=\"$group_id\" name=\"$group_id\" ";
		if( in_array( $group_name, $group_names_array ) )
			$all_groups .= "checked=\"yes\" ";
		$all_groups .= "/> <label for=\"$group_id\"> $group_name</label><br />\n";
		}
	$all_groups .= "<input type=\"submit\" value=\"Update\">\n</form>\n";
	print(  "<div class=\"$author_class\" " );
	if( $author_username != $_SESSION["logged_in"] )
		print( "onmouseover=\"javascript:document.getElementById('author-details-$post_id').style.display='block';\" onmouseleave=\"javascript:document.getElementById('author-details-$post_id').style.display='none';document.getElementById('update-group-membership-$post_id').style.display='none';\"" );
	print( "><img width=\"$avatar_size\" height=\"$avatar_size\" src=\"assets/images/avatars/$author_id\" /><br />" );
	print getAuthorLink( $author_id, $author_visible_name, $author_real_name, $author_public );
	print( "<br />\n" );
	if( $author_username != $_SESSION["logged_in"]  &&  $userID != ""  &&  $userID != 0 )
		{
		print( "<div id=\"author-details-$post_id\" class=\"author-details\" style=\"display: none\">" );
		if( $group_names != "" )
			print( "Member of <strong>$group_names</strong><br />" );
		print( "<div id=\"update-group-membership-$post_id\" class=\"update-group-membership\" style=\"display: none\">$all_groups</div>\n" );
		print( "<a href=\"#\" onmouseover=\"javascript:document.getElementById('update-group-membership-$post_id').style.display='block';return false;\" onmmouseleave=\"javascript:document.getElementById('update-group-membership-$post_id').style.display='none';return false;\">Groups</a> &nbsp; <a href=\"#\">Block</a></div> <!-- #author-details -->\n" );
		}
	print(  "</div> <!-- .$author_class -->\n" );
	}



function compressContent( $content )
	{
	$compressed_content = str_replace( "\r\n", "==[[BR]]==", $content );
	$compressed_content = str_replace( "\n", "==[[BR]]==", $compressed_content );
	$compressed_content = str_replace( "\r", "==[[BR]]==", $compressed_content );
	$compressed_content = str_replace( "\"", "==[[QUOTE]]==", $compressed_content );
	$compressed_content = addslashes( $compressed_content );
	return $compressed_content;
	}

function displayPosts( $db, $db2, $sql, $userID, $max_posts, $param_types, $param1 = "", $param2 = "", $param3 = "" )
	{
	$output = "";
	$stmt = $db->stmt_init();
	//print "$param_types $param1 $param2<br>" ;
	print( "<div id=\"post-container\" class=\"post-container\">\n" );
	if( $stmt->prepare( $sql ) )
		{
		if( $param3 != "" )
			$stmt->bind_param( $param_types, $param1, $param2, $param3 );
		elseif( $param2 != "" )
			$stmt->bind_param( $param_types, $param1, $param2 );
		elseif( $param1 != "" )
			$stmt->bind_param( $param_types, $param1 );
		$stmt->execute();
		print $db->error;
		print $stmt->error;
		$stmt->store_result();
		$num_results = $stmt->num_rows;
		$stmt->bind_result( $post_id, $content, $created, $author_visible_name, $author_real_name, $author_username, $author_public, $author_id, $parent_post_id );
		$post_index = 0;
		while( $stmt->fetch()  &&  $post_index < $max_posts )
			{
			print( "<div class=\"post\">\n" );
			printAuthorInfo( $db2, $userID, $author_id, $author_username, $author_visible_name, $author_real_name, $author_public, $post_id, "full" );
			print( "<div class=\"post-content\" onmouseover=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='visible';\" onmouseleave=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='hidden';\">" );
			print( "<div class=\"timestamp\"><a href=\"post.php?i=$post_id#main-post\">" . getAge( $created ) . "</a></div>\n" );
			// Get world info
			$world_name = "";
			$p_stmt = $db2->stmt_init();
			$p_stmt = $db2->prepare( "SELECT worlds.id, worlds.display_name FROM worlds, world_posts WHERE world_posts.world = worlds.id AND world_posts.post = ?" );
			$p_stmt->bind_param( "s", $post_id );
			$p_stmt->execute();
			$p_stmt->bind_result( $world_id, $world_name );
			$p_stmt->fetch();
			$p_stmt->close();
			if( $world_name != "" )
				print( "<div class=\"in-world\">In the world of <a href=\"world.php?i=$world_id\" class=\"world-name\">$world_name</a>:</div>\n" );
			// Get reply-to info
			if( $parent_post_id != ""  &&  $userID != ""  &&  $userID != 0 )
				{
				$p_stmt = $db2->stmt_init();
				$p_stmt->prepare( "SELECT posts.content, users.visible_name, users.profile_public, users.id FROM posts, users WHERE posts.author = users.id AND posts.id = ?" );
				$p_stmt->bind_param( "s", $parent_post_id );
				$p_stmt->execute();
				$p_stmt->bind_result( $parent_post_content, $parent_author_visible_name, $parent_author_profile_public, $parent_author_id );
				if( $p_stmt->fetch() )
					{
					print( "<div class=\"in-reply-to\">In reply to " );
					if( $parent_author_profile_public == 1 )
						print( "<a href=\"profile.php?i=$parent_author_id\">$parent_author_visible_name</a>" );
					else
						print( "$parent_author_visible_name" );
					print( "'s post <em><a href=\"post.php?i=$parent_post_id#main-post\">" . getPostSnippet( $parent_post_content ) . "</a></em></div>\n" );
					}
				$p_stmt->close();
				}
			// Display the actual post
			print( formatPost( $content ) );
			// Get comments
			$comments_stmt = $db2->stmt_init();
			$comments_sql = "SELECT comments.id, comments.content, comments.created, users.username, users.visible_name, users.real_name, users.profile_public, users.id " .
			                "FROM comments " .
			                "JOIN users ON (comments.author = users.id) " .
							"WHERE comments.post = ? " .
							"ORDER BY comments.created ASC LIMIT 10";
			if( $comments_stmt->prepare( $comments_sql ) )
				{
				$comments_stmt->bind_param( "s", $post_id );
				$comments_stmt->execute();
				$comments_stmt->store_result();
				$comments_stmt->bind_result( $comment_id, $comment_content, $comment_created, $commenter_username, $commenter_visible_name, $commenter_real_name, $commenter_public, $commenter_id );
				if( $comments_stmt->num_rows > 0 )
					print( "<div class=\"comments\">\n" );
				while( $comments_stmt->fetch() )
					{
					print( "<div class=\"comment\"" );
					if( $commenter_id == $userID )
						print( "onmouseover=\"javascript:document.getElementById('comment-edit-link-$comment_id').style.display='block';\" onmouseleave=\"javascript:document.getElementById('comment-edit-link-$comment_id').style.display='none';\"" );
					print( ">\n" );
					printAuthorInfo( $db2, $userID, $commenter_id, $commenter_username, $commenter_visible_name, $commenter_real_name, $commenter_public, $comment_id, "comment" );
					$compressed_comment = compressContent( $comment_content );
					print( "<div class=\"comment-content\"><div class=\"timestamp\">" );
					if( $commenter_id == $userID )
						print( "<a onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content');updatePreview('compose-post','post-preview');return false\" href=\"#\">" );
					print( getAge( $comment_created ) );
					if( $commenter_id == $userID )
						print( "</a><br /><div id=\"comment-edit-link-$comment_id\" style=\"float: right; display: none;\"><a onclick=\"javascript:setComposeForEdit('$post_id','compose-comment-$post_id','$compressed_comment','$comment_id');updatePreview('compose-comment-$post_id','comment-preview-$post_id');return false;\" href=\"#\">Edit</a></div>" );
					print( "</div>" . formatPost( $comment_content ) . "</div>" );
					print( "</div>\n" ); // end .comment
					}
				if( $comments_stmt->num_rows > 0 )
					print( "</div>\n" ); // end .comments
				}
			$snippet = getPostSnippet( $content );
			if( $userID != ""  &&  $userID != 0 )
				{
				print( "<div id=\"post-navigation-$post_id\" class=\"post-navigation\" style=\"visibility: hidden\"><a href=\"post.php?i=$post_id#main-post\">View conversation</a> &nbsp; " );
				if( $author_id == $userID )
					{
					$compressed_content = compressContent( $content );
					print( "<a href=\"#\" onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','');updatePreview('compose-post','post-preview');return false;\">Edit</a> &nbsp; <a onclick=\"javascript:displayDelete('$post_id');return false;\" href=\"#\">Delete</a> &nbsp; " );
					}
				print( "<a onclick=\"javascript:setReplyTo('$post_id', '$author_visible_name', '$snippet');\" href=\"#top\">Reply with post</a> &nbsp; <a onclick=\"javascript:toggleComposePane('compose-tools-$post_id','compose-pane-$post_id','compose-comment-$post_id');return false;\" href=\"#\">Reply with comment</a>&nbsp;&nbsp;</div> <!-- .post-navigation -->\n" );
				displayComposePane( "comment", $db, $userID, $post_id );
				}
			print( "</div>\n" ); // end .post-content
			print( "</div>\n" ); // end .post
			$post_index++;
			}
		}
	if( $post_index < $num_results )
		{
		$tab = "";
		if( isset( $_GET["tab"] ) )
			$tab = $_GET["tab"];
		print( "<div id=\"load-more-posts\">\n" );
		print( "<button onclick=\"javascript:loadMorePosts('$tab','$userID',$post_index);return false;\">Load more results</button>\n" );
		print( "</div>\n" );
		}
	print( "</div>\n" ); // end .post-container
	}

function formatPost( $text )
	{
	// Replace HTML with BBcode and remove the rest.
	$text = preg_replace( "/<strong>|<b>/i", "[b]", $text );
	$text = preg_replace( "/<\/strong>|<\/b>/i", "[/b]", $text );
	$text = preg_replace( "/<em>|<i>/i", "[i]", $text );
	$text = preg_replace( "/<\/em>|<\/i>/i", "[/i]", $text );
	$text = preg_replace( "/<u>/i", "[u]", $text );
	$text = preg_replace( "/<\/u>/i", "[/u]", $text );
	$text = preg_replace( "/<a target=[\"'][\S]+?[\"'] href=[\"']([\S]+?)[\"']>([\S\s]+?)<\/a>/i", "[url=$1]$2[/url]", $text );
	$text = preg_replace( "/<a href=[\"']([\S]+?)[\"'] target=[\"'][\S]+?[\"']>([\S\s]+?)<\/a>/i", "[url=$1]$2[/url]", $text );
	$text = preg_replace( "/<a href=[\"']([\S]+?)[\"']>([\S\s]+?)<\/a>/i", "[url=$1]$2[/url]", $text );
	$text = preg_replace( "/<img src=[\"']([\S]+?)[\"']>/i", "$1", $text );
	$text = strip_tags( $text );
	// Add breaks
	$text = preg_replace( "/\n/", "<br />\n", $text );
	// Process ordered and unordered lists
	$lines = explode( "\n", $text );
	$in_ul = 0;
	$in_ol = 0;
	$in_code = 0;
	for( $i = 0; $i < count($lines); $i++ )
		{
		while( stripos( $lines[$i], "[CODE]" ) !== false  &&
		       stripos( $lines[$i], "[/CODE]" ) !== false )
			{
			$lines[$i] = str_ireplace( "[CODE]", "<span class=\"style-code-snippet\">", $lines[$i] );
			$lines[$i] = str_ireplace( "[/CODE]", "</span>", $lines[$i] );
			}
		if( stripos( $lines[$i], "[CODE]" ) !== false )
			{
			$in_code = 1;
			$lines[$i] = str_ireplace( "[CODE]", "<div class=\"style-code-block\">Code:", $lines[$i] );
			$lines[$i] = str_ireplace( "<br />", "", $lines[$i] );
			$lines[$i] = str_ireplace( "<br></br>", "", $lines[$i] );
			}
		elseif( stripos( $lines[$i], "[/CODE]" ) !== false )
			{
			$in_code = 0;
			$lines[$i] = str_ireplace( "[/CODE]", "</div>", $lines[$i] );
			$lines[$i] = str_ireplace( "<br />", "", $lines[$i] );
			}
		elseif( $in_code >= 1 )
			{
			$lines[$i] = str_replace( "<br />", "", $lines[$i] );
			$lines[$i] = str_replace( "<br></br>", "", $lines[$i] );
			$lines[$i] = "<span class=\"code-line-number\">" . str_pad( $in_code, 3, "0", STR_PAD_LEFT ) . "</span> " . $lines[$i];
			$in_code++;
			}
		if( substr( $lines[$i], 0, 2 ) == "* " )
			{
			$lines[$i] = preg_replace( "/^\* /", "<li> ", $lines[$i] );
			$lines[$i] = preg_replace( "/<br \/>$/", "</li> ", $lines[$i] );
			if( $in_ul == 0 )
				$lines[$i] = "<ul>" . $lines[$i];
			$in_ul = 1;
			}
		elseif( $in_ul == 1 )
			{
			$lines[$i] = "</ul>" . $lines[$i];
			$in_ul = 0;
			}
		if( substr( $lines[$i], 0, 2 ) == "# "  ||
		    preg_match( "/^[0-9] /", $lines[$i] ) == 1 )
			{
			$lines[$i] = "<li> " . substr( $lines[$i], 2 );
			$lines[$i] = preg_replace( "/<br \/>$/", "</li> ", $lines[$i] );
			if( $in_ol == 0 )
				$lines[$i] = "<ol>" . $lines[$i];
			$in_ol = 1;
			}
		elseif( $in_ol == 1 )
			{
			$lines[$i] = "</ol>" . $lines[$i];
			$in_ol = 0;
			}
		}
	$text = implode( "\n", $lines );
	if( $in_ul == 1 )
		$text .= "</ul>";
	if( $in_ol == 1 )
		$text .= "</ol>";
	// Process forum-style formatting
	$text = preg_replace( "/\[B\]([\S\s]+?)\[\/B\]/i", "<strong>$1</strong>", $text );
	$text = preg_replace( "/\[I\]([\S\s]+?)\[\/I\]/i", "<em>$1</em>", $text );
	$text = preg_replace( "/\[U\]([\S\s]+?)\[\/U\]/i", "<span style=\"text-decoration: underline\">$1</span>", $text );
	$text = preg_replace( "/\[COLOR=([\S]+?)\]([\S\s]+?)\[\/COLOR\]/i", "<span style=\"color: $1\">$2</span>", $text );
	$text = preg_replace( "/\[SIZE=([\S]+?)\]([\S\s]+?)\[\/SIZE\]/i", "<span style=\"font-size: $1\">$2</span>", $text );
	$text = preg_replace( "/\[FONT=([\S]+?)\]([\S\s]+?)\[\/FONT\]/i", "<span style=\"font-family: $1\">$2</span>", $text );
	$text = preg_replace( "/\[ALIGN=(LEFT|CENTER|RIGHT)\]([\S\s]+?)\[\/ALIGN\]/i", "<div style=\"text-align: $1\">$2</div>", $text );
	$text = preg_replace( "/\[INDENT\]([\S\s]+?)\[\/INDENT\]/i", "<div style=\"padding-left: 25px;\">$1</div>", $text );
	$text = preg_replace( "/\[EMAIL\]([\S\s]+?)\[\/EMAIL\]/i", "<a href=\"mailto:$1\">$1</a>", $text );
	$text = preg_replace( "/\[URL\]([\S\s]+?)\[\/URL\]/i", "<a href=\"$1\">$1</a>", $text );
	$text = preg_replace( "/\[URL=([\S]+?)\]([\S\s]+?)\[\/URL\]/i", "<a href=\"$1\">$2</a>", $text );
	$text = preg_replace( "/\[IMG\]([\S\s]+?)\[\/IMG\]/i", "<img src=\"$1\" style=\"max-width: 500px\" />", $text );
	// Process other stuff
	$text = preg_replace( "/<br \/>\n<br \/>\n<ul>/", "<br />\n<ul>", $text );
	$text = preg_replace( "/<br \/>\n<br \/>\n<ol>/", "<br />\n<ol>", $text );
	$text = preg_replace( "/'''([\S\s]+?)'''/", "<strong>$1</strong>", $text );
	$text = preg_replace( "/''([\S\s]+?)''/", "<em>$1</em>", $text );
	$text = preg_replace( "/(\s|^)__([\S\s]+?)__(\s|$)/", "$1<strong>$2</strong>$3", $text );
	$text = preg_replace( "/\*\*([\S\s]+?)\*\*/", "<strong>$1</strong>", $text );
	//$text = preg_replace( "/(\s|^)_([\S\s]+?)_(\s|\.|$)/", "$1<em>$2</em>$3", $text );
	$text = preg_replace( "/(\s|^)_([\S\s]+?)_(\s|\n|\.|\,|\:|$)/", "$1<em>$2</em>$3", $text );
	$text = preg_replace( "/\*([\S\s]+?)\*/", "<em>$1</em>", $text );
	$text = preg_replace( "/(http|https):\/\/www\.youtube\.com\/watch\?v=([\S]+)/i", "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"$1://www.youtube.com/embed/$2\" frameborder=\"0\"></iframe>", $text );
	$text = preg_replace( "/(http|https):\/\/youtu\.be\/([\S]+)/i", "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"$1://www.youtube.com/embed/$2\" frameborder=\"0\"></iframe>", $text );
	$text = preg_replace( "/(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)\|([0-9]+)/", "<img src=\"$1://$2.$3\" style=\"width: $4px; max-width: 500px\" />", $text );
	$text = preg_replace( "/(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)([^\"])/", "<img src=\"$1://$2.$3\" style=\"max-width: 500px\" />$4", $text );
	$text = preg_replace( "/(http|https):\/\/([A-Za-z0-9\.\%$&\?\#\/\-_=]+)(\s|\n|$)/im", "<a href=\"$1://$2\">$2</a>$3", $text );
	$text = preg_replace( "/(\s|^)#([A-Za-z0-9\-]+)/", "$1<a href=\"hashtag.php?tag=$2\">#$2</a>", $text );
	return $text;
	}

function requireLogin( $db, $db2 )
	{
	if( ! isset( $_SESSION["logged_in"] ) )
		{
		/* if( isset( $_GET["error"] ) )
			printError( $_GET["error"] ); */
		$username = "";
		if( isset( $_GET["username"] ) )
			$username = $_GET["username"];
?>
<h2>Log in</h2>
<form action="login.php" method="post">
<table border="0" style="margin: auto; padding-top: 25px">
	<tr>
		<td class="label">Username</td>
		<td><input type="text" name="username" value="<?php echo $username; ?>"/></td>
	</tr>
	<tr>
		<td class="label">Password</td>
		<td><input type="text" id="login-password" name="password" value="<?php echo $password; ?>"/></td>
		<td><input type="checkbox" id="visible-checkbox" checked="yes" onclick="javascript:hidePasswordField('visible-checkbox','login-password');" /> <label for="visible-checkbox">Display password</label></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" name="submit" value="Log in" /></td>
	</tr>
	<tr>
		<td></td>
		<td colspan="2" style="font-size: 10pt; padding-top: 50px;">
		<p>To create a new account, enter your desired<br />username and password above and click here:</p>
		<input type="submit" name="submit" value="Create account" />
		<p>Passwords must be at least 8 characters,<br />
		   and must contain at least 1 upper-case<br />
		   character, at least 1 number, and at least 1<br />
		   symbol.</p>
		</td>
	</tr>
</table>
</form>
<p><a href="recover.php">Recover your password</a></p>
<h2>Recent Public Posts</h2>
<?php
		$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, " .
		       "users.visible_name, users.real_name, users.username, " .
			   "users.profile_public, posts.author, posts.parent FROM posts " .
			   "JOIN users ON (posts.author = users.id) " .
			   "WHERE posts.public = 1 " .
		       "ORDER BY posts.created DESC";
		displayPosts( $db, $db2, $sql, 0, 10, "none" );
		require_once( "footer.php" );
		exit( 0 );
		} // end if logged_in session variable unset
	} // end requireLogin()



function testPassword( $password )
	{
	if( ( ! preg_match( "/[A-Z]/", $password ) )  ||
	    ( ! preg_match( "/[0-9]/", $password ) )  ||
	    ( ! preg_match( "/[!@#$%^&\*\(\)\-_=+\[{\]}\\|;:'\",<\.>\/\?]/", $password ) ) )
		return 0;
	return 1;
	}




function createAccount( $db, $username, $password )
	{
	if( preg_match( "/[^A-Za-z0-9\_\-]/", $username ) )
		{
		header( "Location: index.php?error=150\n\n" );
		exit(1);
		}
	if( ! testPassword( $password ) )
		{
		header( "Location: index.php?error=152\n\n" );
		exit(1);
		}
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT username FROM users WHERE username = ?" ) )
		{
		$stmt->bind_param( "s", $username );
		$stmt->execute();
		$stmt->bind_result( $returned_username );
		$stmt->fetch();
		if( $returned_username == $username )
			{
			header( "Location: index.php?error=151\n\n" );
			exit(1);
			}
		else
			{
			if( $stmt->prepare( "INSERT INTO users (id, username, visible_name, password, created, paid, profile_public, admin) VALUES (UUID(), ?, ?, ?, ?, 0, 0, 0)" ) )
				{
				$stmt->bind_param( "ssss", $username, $username, $password, time() );
				$stmt->execute();
				$stmt->close();
				$new_user_id = get_db_value( $db, "SELECT MAX(id) FROM users" );
				$stmt = $db->stmt_init();
				$stmt->prepare( "INSERT INTO user_teams (id, user, name) VALUES (UUID(), ?, 'Friends')" );
				$stmt->bind_param( "s", $new_user_id );
				$stmt->execute();
				// Associate random avatar
				$avatar = intval( rand(1, 12) );
				if( file_exists( "assets/images/avatar$avatar.png" ) )
					copy( "assets/images/avatar$avatar.png", "assets/images/avatars/$new_user_id" );
				else
					copy( "assets/images/avatar$avatar.jpg", "assets/images/avatars/$new_user_id" );
				// Log in and go to home page.
				$_SESSION["logged_in"] = $username;
				header( "Location: index.php?message=Done\n\n" );
				exit( 0 );
				}
			}
		}
	} // end createAccount()




function processWorldNameForDisplay( $topic )
	{
	$topic = trim( $topic );
	$topic = preg_replace( "/[^A-Za-z0-9 \'\/,!:;\-+_\&]/", "", $topic );
	$topic = str_replace( "  ", " ", $topic );
	return $topic;
	}

function processWorldNameForBasic( $topic )
	{
	/* $topic = trim( $topic );
	$topic = str_replace( "  ", " ", $topic ); */
	$topic = processWorldNameForDisplay( $topic );
	$topic = strtolower( $topic );
	return $topic;
	}




function redirectToNewPage( $redirect )
	{
	$filename = $redirect;
	// For purposes of making sure the redirect is valid,
	// remove parameters and strip off initial "/"
	if( strpos( $filename, '?' ) !== false )
		$filename = substr( $filename, 0, strpos( $filename, '?' ) );
	if( substr( $filename, 0, 1 ) == "/" )
		$filename = substr( $filename, 1 );
	//print( "Redirecting: $filename $redirect<br>\n" );
	if( file_exists( $filename ) )
		{
		header( "Location: $redirect\n\n" );
		exit( 0 );
		}
	}



function postingIdenticalToLastPost( $db, $content, $table, $user_id )
	{
	// Returns 1 if the author's most recent post matches $content
	require_once( "database.php" );
	$last_content = get_db_value( $db, "SELECT content FROM $table WHERE author = ? ORDER BY created DESC LIMIT 1", "s", $user_id );
	if( $last_content == $content )
		return 1;
	return 0;
	}




function addPings( $db, $content_id, $ping_type, $userID )
	{
	/* Adds pings for the specified comment */
	$post_id = $content_id;
	if( $ping_type == "c" )
		$post_id = get_db_value( $db, "SELECT post FROM comments WHERE id = ?", "s", $content_id );
	// Find author and all commenters
	$users_to_notify = array();
	$author = get_db_value( $db, "SELECT author FROM posts WHERE id = ?", "s", $post_id );
	if( $author != $userID )
		array_push( $users_to_notify, $author );
	$sql = "SELECT DISTINCT author FROM comments WHERE post = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $post_id );
	$stmt->execute();
	$stmt->bind_result( $author );
	while( $stmt->fetch() )
		{
		// Add commenter, as long as it's not the current user
		if( $author != $userID )
			array_push( $users_to_notify, $author );
		}
	$stmt->close();
	// Add a ping record for each author
	foreach( $users_to_notify as $user_id )
		{
		$sql = "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, ?, ?, 0)";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		if( $ping_type == "c" )
			$stmt->bind_param( "siss", $user_id, time(), $ping_type, $content_id );
		else
			$stmt->bind_param( "siss", $user_id, time(), $ping_type, $post_id );
		$stmt->execute();
		$stmt->close();
		}
	}

function displayWorldOrRoomList( $db, $type, $display = "popular" )
	{
	// $type == "world" or "room"
	$name_field = "name";
	if( $type == "world" )
		$name_field = "display_name";
	if( $display == "popular" )
		{
		print( "<div title=\"These are the 25 $type" . "s with the most posts.\">" .
		       "<p><strong>Popular</strong>: \n" );
		$sql = "SELECT $type" . "s.id, $type" . "s.$name_field, (SELECT COUNT(*) FROM $type" . "_posts c WHERE c.$type = $type" . "s.id) AS post_count FROM $type" . "s " .
		       "ORDER BY post_count DESC, $type" . "s.$name_field LIMIT 25";
		}
	else
		{
		$sql = "SELECT id, $name_field FROM $type" . "s ORDER BY $name_field";
		}
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->execute();
	if( $display == "popular" )
		$stmt->bind_result( $id, $name, $count );
	else
		$stmt->bind_result( $id, $name );
	while( $stmt->fetch() )
		{
		print( "<a href=\"$type.php?i=$id\" title=\"$count posts\">$name</a> " );
		if( $display != "popular" )
			print( "<br />\n" );
		}
	$stmt->close();
	if( $display == "popular" )
		print( "</div>\n" );
	}


function getPostSnippet( $post_content )
	{ // Returns a sane first line of the beginning of a post.
	$snippet = trim( strip_tags( $post_content ) );
	$max_length = 50;
	if( strlen($snippet) > $max_length )
		{
		$snippet = substr( $snippet, 0, 50 );
		$snippet .= "...";
		}
	$snippet = addslashes( $snippet );
	return $snippet;
	}

function handleErrors( $errno, $errstr, $errfile, $errline )
	{
	$fp = fopen( "errors.txt", "a" );
	fputs( $fp, "====================\n" .
	            date( "Y-m-d H:i:s" ) . "\n" .
				"====================\n" );
	if( ! ( error_reporting() & $errno ) )
		return;
	
	$msg = "";
	switch( $errno )
		{
		case E_USER_ERROR:
			$msg = "<b>ERROR</b> [$errno] $errstr<br />\n" .
			       "Fatal error on line $errline in file $errfile<br />\n" .
			       "PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
			fputs( $fp, $msg );
			fclose( $fp );
			exit( 1 );
			break;
		case E_USER_WARNING:
			$msg = "<b>WARNING</b> [$errno] $errstr<br />\n";
			fputs( $fp, $msg );
			fclose( $fp );
			exit( 1 );
			break;
		case E_USER_NOTICE:
			$msg = "<b>NOTICE</b> [$errno] $errstr<br />\n";
			fputs( $fp, $msg );
			fclose( $fp );
			exit( 1 );
			break;
		default:
			$msg = "Unknown error type: [$errno] $errstr<br />\n";
			fputs( $fp, $msg );
			fclose( $fp );
			exit( 1 );
			break;
		}
	return true;
	}

// $err_handler = set_error_handler( "handleErrors" );
?>
