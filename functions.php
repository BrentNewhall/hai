<?php

require_once( "database.php" );

require_once( "functions/account.php" );
require_once( "functions/posts.php" );

$posts_per_page = 25;

function getStandardSQLselect( $brodcast = "" )
	{
	$text = "SELECT DISTINCT posts.id, " .
			"GREATEST(IFNULL(posts.created,0),IFNULL(broadcasts.created,0)) AS bothcreated " .
			"FROM posts ";
	return $text;
	}

function getStandardSQL( $type )
	{
	global $posts_per_page;
	if( $type == "Everything" )
		return getStandardSQLselect() . 
			   "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
		       "WHERE posts.public = 1 " .
			   "AND posts.id NOT IN (SELECT post FROM room_posts) " .
		       "ORDER BY bothcreated DESC LIMIT $posts_per_page";
	elseif( $type == "Everything User" )
		// Filter out blocks
		return getStandardSQLselect() . 
			   "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
		       "WHERE posts.public = 1 " .
			   "AND posts.id NOT IN (SELECT post FROM room_posts) " .
		       "ORDER BY bothcreated DESC LIMIT $posts_per_page";
	elseif( $type == "team" )
		return getStandardSQLselect() .
		       "JOIN user_teams ut ON (ut.id = ? AND ut.user = ?) " . // ? = team ID, ? = userID
		       "JOIN user_team_members utm ON (ut.id = utm.team AND utm.user = posts.author) " .
		       "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
			   "LEFT JOIN broadcasts ON (broadcasts.post = posts.id AND broadcasts.user = utm.user) " .
			   "WHERE posts.id NOT IN (SELECT post FROM room_posts) " .
		       "ORDER BY bothcreated DESC LIMIT $posts_per_page";
	elseif( $type == "all" )
		return getStandardSQLselect() .
		       "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
		       "LEFT JOIN user_teams ON (user_teams.user = ?) " .
		       "LEFT JOIN user_team_members ON (user_team_members.team = user_teams.id )" .
			   "LEFT JOIN broadcasts ON (broadcasts.post = posts.id AND broadcasts.user = user_team_members.user) " .
		       "WHERE ( posts.author = ? OR user_team_members.user = posts.author ) " .
			   "AND posts.id NOT IN (SELECT post FROM room_posts) " .
		       "ORDER BY bothcreated DESC LIMIT $posts_per_page";
	return "";
	}

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
	
	/* if( isset( $_GET["tab"] ) )
		print( "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?tab=" . $_GET["tab"] . "\" />\n" );
	elseif( isset( $_GET["i"] ) )
		print( "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?i=" . $_GET["i"] . "\" />\n" );
	else */
	print( "<input type=\"hidden\" name=\"redirect\" value=\"" . getRedirectURL() . "\" />\n" );
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
			<div style="width: <?php echo $width; ?>px; background-color: white; display: none" id="<?php echo $compose_pane_id; ?>">
				<textarea class="<?php echo $compose_class; ?>" name="compose-post" id="<?php echo $compose_id; ?>" onkeyup="javascript:updatePreview('<?php echo $compose_id; ?>','<?php echo $preview_id; ?>');" /></textarea><br />
				<div class="progress-bar" id="progress-bar-<?php echo $compose_id; ?>"></div>
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
					World: <input type="text" name="post-world" id="post-world" value="<?php echo $world_value; ?>" size="30" title="A topic of conversation" onchange="javascript:displayWorldRestrictions('post-world','post-restrictions','set-post-public');displayWorldSuggestions('post-world','world-hints');" onkeyup="javascript:displayWorldRestrictions('post-world','post-restrictions','set-post-public' );displayWorldSuggestions('post-world','world-hints');" />
					<input type="checkbox" name="public" id="set-post-public" checked="yes" value="yes" onchange="javascript:displayWorldRestrictions('post-world','post-restrictions','set-post-public' );" /> <label for="set-post-public" class="compose-checkbox" title="Public posts show up in both the world and your general feed. If unchecked, the post only shows up in the world.">Public</label>
					<input type="checkbox" name="allow-comments" id="set-post-allow-comments" checked="yes" value="yes" /> <label for="set-post-allow-comments" class="compose-checkbox" title="If checked, anyone who can see this post can comment on it.">Comments</label>
					<input type="checkbox" name="editable" id="set-post-editable" value="yes" /> <label for="set-post-editable" class="compose-checkbox" title="If checked, anyone logged in to Hai who can see this post can edit it.">Editable</label><br />
					<div id="world-hints"></div>
					<?php
					} // end if flavor == "post"
				?>
				<div id="<?php echo $in_reply_to_id; ?>" style="display: none"></div>
				<div class="reply-suggestions" id="<?php echo $preview_id; ?>-reply-suggestions"></div>
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
	<script type="text/javascript">
	InitFileDrag( '<?php echo $compose_id; ?>', '<?php echo $userID; ?>' );
	</script>
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
					$age = $weeks . "w " . intval($days - ($weeks*7)) . "d";
				else
					{
					$months = intval( $minutes / 60 / 24 / 30 ); // Months
					if( $months < 12 )
						$age = $months . "mo " . intval($weeks - ($months*4)) . "w";
					else
						{
						$years = intval( $minutes / 12 / 60 / 24 / 30 ); // Years
						$age = $years . "y";
						}
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
	$errors[152] = "Passwords must have at least 8 characters and must contain at least one upper-case letter, at least one number, and at least one symbol.";
	$errors[201] = "That post does not exist.";
	$errors[202] = "You can't delete that post.";
	$errors[301] = "A room named <a href=\"room.php?r=" . $_GET["room_id"] . "\">" . $_GET["room_name"] . "</a> already exists. Please choose another name.";
	$errors[302] = "You cannot create a room with that name. Please choose another name.";
	if( array_key_exists( $code, $errors ) )
		print( "<p class=\"error\">" . $errors[$code] . "</p>\n" );
	}


function abbreviateName( $name )
	{
	$short_name = $name;
	if( strlen($short_name) > 15 )
		{
		$short_name = substr( $short_name, 0, 15 ) . "...";
		}
	return $short_name;
	}

function displayNavbar( $db, $userID )
	{
	global $page_title;
	?>
	<div id="navbar">
	<?php
	if( $userID != "" )
		{
		$unread_pings = get_db_value( $db, "SELECT COUNT(*) FROM pings WHERE user = ? AND is_read = 0", "s", $userID );
		print( "<p><a" );
		if( $_SERVER["PHP_SELF"] == "/pings.php" )
			print( " style=\"font-weight: bold\"" );
		print( " title=\"Notifications of new comments on posts you wrote or commented on.\" href=\"pings.php\">Pings" );
		if( $unread_pings > 0 )
			print( " <strong>($unread_pings)</strong>" );
		print( "</a></p>\n" );
		}
	print( "<p" );
	if( isset( $_GET["tab"] )  &&  $_GET["tab"] == "Everything" )
		print( " style=\"font-weight: bold\"" );
	print( " title=\"All posts marked public, from everyone. Kinda like Twitter!\"><a href=\"index.php?tab=Everything\">Everything</a></p>\n" );
	if( $userID != "" )
		{
		// Teams
		print( "<p class=\"view-header\"><a title=\"Modify membership of your teams and create new teams.\" href=\"teams.php\">Teams</a></p>\n" );
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
		}
	// Worlds
	print( "<p" );
	if( $_SERVER["PHP_SELF"] == "/world.php"  &&
	    ( isset( $_GET["i"] )  &&  $_GET["i"] == "*" ) )
		print( " style=\"font-weight: bold\"" );
	print( " class=\"view-header\" title=\"View topics of conversation.\"><a href=\"world.php?world=*\">Worlds</a></p>\n" );
	$stmt = $db->stmt_init();
	if( $userID != "" )
		$sql = "SELECT worlds.id, worlds.display_name, worlds.basic_name FROM user_worlds, worlds WHERE user_worlds.world = worlds.id AND user_worlds.user = ? ORDER BY worlds.display_name";
	else
		$sql = "SELECT worlds.id, worlds.display_name, worlds.basic_name FROM worlds ORDER BY worlds.display_name LIMIT 25";
	$stmt->prepare( $sql );
	if( $userID != "" )
		$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $world_id, $world_name, $basic_name );
	while( $stmt->fetch() )
		{
		$world_short_name = abbreviateName( $world_name );
		print( "<p class=\"view-content\"" );
		if( $_SERVER["PHP_SELF"] == "/world.php"  &&
		    ( isset( $_GET["i"] )  &&  $_GET["i"] == $world_id ) )
			print( " style=\"font-weight: bold\"" );
		print( "><a title=\"Posts in the '$world_name' world.\" href=\"/world/$basic_name\">$world_short_name</a>" );
		//print( "><a title=\"Posts in the '$world_name' world.\" href=\"/room.php?i=$world_id\">$world_short_name</a>" );
		print( "</p>\n" );
		}
	// Rooms
	print( "<p" );
	if( $_SERVER["PHP_SELF"] == "/room.php"  &&
	    ( isset( $_GET["i"] )  &&  $_GET["i"] == "*" ) )
		print( " style=\"font-weight: bold\"" );
	print( " class=\"view-header\" title=\"Browse private areas of conversation.\"><a href=\"room.php\">Rooms</a></p>\n" );
	$stmt = $db->stmt_init();
	if( $userID != "" )
		$sql = "SELECT rooms.id, rooms.name FROM room_members JOIN rooms ON (room_members.room = rooms.id) WHERE room_members.user = ? ORDER BY rooms.name";
	else
		$sql = "SELECT rooms.id, rooms.name FROM rooms ORDER BY rooms.name LIMIT 25";
	$stmt->prepare( $sql );
	if( $userID != "" )
		$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $room_id, $room_name );
	while( $stmt->fetch() )
		{
		$room_short_name = abbreviateName( $room_name );
		print( "<p class=\"view-content\"" );
		if( $_SERVER["PHP_SELF"] == "/room.php"  &&
		    ( ( isset( $_GET["i"] )  &&  $_GET["i"] == $room_id )  ||
		      ( isset( $_POST["room-id"] )  &&  $_POST["room-id"] == $room_id )  ) )
			print( " style=\"font-weight: bold\"" );
		print( "><a title=\"Posts in the '$room_name' room.\" href=\"/room/$room_name\">$room_short_name</a>" );
		//print( "><a title=\"Posts in the '$room_name' room.\" href=\"/room.php?i=$room_id\">$room_short_name</a>" );
		print( "</p>\n" );
		}
	// Hashtags
	print( "<p style=\"padding-top: 10px\"><a " );
	if( $_SERVER["PHP_SELF"] == "/hashtag.php" )
		print( " style=\"font-weight: bold\"" );
	print( "href=\"hashtag.php\">Hashtags</a></p>\n" );
	// Search
	print( "<form action=\"search.php\" method=\"get\"><input type=\"text\" size=\"8\" name=\"q\" value=\"" );
	if( $_SERVER["PHP_SELF"] == "/search.php"  &&  isset( $_GET["q"] ) )
		print( htmlentities( $_GET["q"] ) );
	print( "\" /><input type=\"submit\" value=\"search\" /></form>\n" );
	// Account links
	if( $userID != "" )
		{
		?>
		<p class="view-header"><a href="account.php">Account</a></p>
		<p class="view-content"><a title="Any images you've uploaded to Hai will appear here." href="media.php?type=images">Images</a></p>
		<p class="view-content"><a title="Any videos you've uploaded to Hai will appear here." href="media.php?type=videos">Videos</a></p>
		<p style="padding-top: 10px"><a href="logout.php">Logout</a></p>
		<?php
		}
		?>
	</div>
	<div id="formatting-hints">
	<p>Post Formatting:</p>
	<p><em>''italics''</em>, <em>*italics*</em>, <em>_italics_</em>, <em>[i]italics[/i]</em>, <strong>'''bold'''</strong>, <strong>**bold**</strong>, <strong>__bold__</strong>, <strong>[b]bold[/b]</strong>, <span style="text-decoration: underline">[u]underline[/u]</span>, <span style="color: blue">[color=blue]color[/color]</span>, <span style="font-size: 8pt">[size=8]size[/size]</span>, <a href="mailto:me@me.com">[email]me@me.com<br />[/email]</a>, <a href="http://test.com">http://test.com</a>.</p>
	<p>Start a line with<br />"* " for a bulleted list; "# " for a numbered list.</p>
	<p>Web, image, and YouTube addresses are automatically embedded.</p>
	<p>Drag and drop images into the text box to upload and embed them.</p>
	<p><a href="formatting.php">More</a></p>
	</div>
	<?php
	}




function getAuthorLink( $id, $visible_name, $real_name, $profile_public )
	{
	$result = "";
	if( $profile_public )
		$result .= "<a href=\"/profile.php?i=$id\" ";
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
	/* if( isset( $_GET["tab"] ) )
		$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?tab=" . $_GET["tab"] . "\" />\n";
	elseif( isset( $_GET["i"] ) )
		$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . $_SERVER["PHP_SELF"] . "?i=" . $_GET["i"] . "\" />\n";
	else */
	$all_groups .= "<input type=\"hidden\" name=\"redirect\" value=\"" . getRedirectURL() . "\" />\n";
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
	if( $author_username != $_SESSION["logged_in"]  &&  $userID != "" )
		print( "onmouseover=\"javascript:document.getElementById('author-details-$post_id').style.display='block';\" onmouseleave=\"javascript:document.getElementById('author-details-$post_id').style.display='none';document.getElementById('update-group-membership-$post_id').style.display='none';\"" );
	print( "><img width=\"$avatar_size\" height=\"$avatar_size\" src=\"/assets/images/avatars/$author_id\" /><br />" );
	print getAuthorLink( $author_id, $author_visible_name, $author_real_name, $author_public );
	print( "<br />\n" );
	if( $author_username != $_SESSION["logged_in"]  &&  $userID != ""  &&  $userID != 0 )
		{
		print( "<div id=\"author-details-$post_id\" class=\"author-details\" style=\"display: none\">" );
		if( $group_names != "" )
			print( "Member of <strong>$group_names</strong><br />" );
		print( "<div id=\"update-group-membership-$post_id\" class=\"update-group-membership\" style=\"display: none\">$all_groups</div>\n" );
		print( "<a href=\"#\" onmouseover=\"javascript:document.getElementById('update-group-membership-$post_id').style.display='block';return false;\" onmmouseleave=\"javascript:document.getElementById('update-group-membership-$post_id').style.display='none';return false;\">Teams</a> &nbsp; <a href=\"#\" onclick=\"javascript:displayBlock('$author_visible_name','$author_id');return false;\">Block</a></div> <!-- #author-details -->\n" );
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



function getRedirectURL()
	{
	$page = $_SERVER["PHP_SELF"];
	if( isset( $_GET["tab"] ) )
		$page .= "?tab=" . $_GET["tab"];
	elseif( isset( $_GET["i"] ) )
		$page .= "?i=" . $_GET["i"];
	return $page;
	}

function processWorldNameForDisplay( $topic )
	{
	$topic = trim( $topic );
	$topic = preg_replace( "/[^A-Za-z0-9 \'\/,!:;\-+_\&]/", "", $topic );
	$topic = str_replace( "  ", " ", $topic );
	return $topic;
	}

function processWorldNameForBasic( $topic )
	{
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
	// Find author
	$users_to_notify = array();
	$author = get_db_value( $db, "SELECT author FROM posts WHERE id = ?", "s", $post_id );
	if( $author != $userID )
		array_push( $users_to_notify, $author );
	// Find all commenters
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
	// Find all tracks
	$sql = "SELECT DISTINCT user FROM tracking WHERE post = ?";
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
	// Remove duplicates.
	$users_to_notify = array_unique( $users_to_notify );
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

function performDieOp( $operation, $result, $min, $max, $extra = "" )
	{
	global $printed_rolls;
	$add = 0;
	$roll = rand( $min, $max );
	while( strpos( $extra, "explode" ) !== false  &&  $roll == $max )
		{
		$add += $roll;
		if( strpos( $extra, "print" ) !== false )
			$printed_rolls .= " $roll";
		$roll = rand( $min, $max );
		}
	$result += $roll + $add;
	if( strpos( $extra, "print" ) !== false )
		$printed_rolls .= " $roll";
	return $result;
	}

function rollDie( $die, $operation, $result )
	{
	$extra = "";
	$c = substr( $die, -1 );
	while( ! in_array( $c, array( 'F', 'f', '%' ) )  &&  ! is_numeric( $c ) )
		{
		if( $c == "p" )
			$extra .= "print ";
		elseif( $c == "e" )
			$extra .= "explode ";
		$die = substr( $die, 0, -1 );
		$c = substr( $die, -1 );
		}
	if( substr( $die, -2 ) == 'd%'  &&  is_numeric( substr( $die, 0, -2 ) ) )
		{
		$num_dice = intval( substr( $die, 0, -2 ) );
		for( $j = 0; $j < $num_dice; $j++ )
			{
			$result = performDieOp( $operation, $result, 1, 100, $extra );
			}
		}
	if( strtolower( substr( $die, -2 ) ) == 'df'  &&
	    is_numeric( substr( $die, 0, -2 ) ) )
		{
		$num_dice = intval( substr( $die, 0, -2 ) );
		for( $j = 0; $j < $num_dice; $j++ )
			{
			$result = performDieOp( $operation, $result, -1, 1, $extra );
			}
		}
	$die_parts = explode( "d", $die );
	if( count($die_parts == 2 )  &&
	    ( $die_parts[0] == ""  ||  is_numeric( $die_parts[0] ) )  &&
	    is_numeric( $die_parts[1] ) )
		{
		if( $die_parts[0] == "" )
			$num_dice = 1;
		else
			$num_dice = intval( $die_parts[0] );
		$num_faces = intval( $die_parts[1] );
		for( $j = 0; $j < $num_dice; $j++ )
			{
			$result = performDieOp( $operation, $result, 1, $num_faces, $extra );
			}
		}
	return $result;
	}

function rollDice( $text )
	{
	global $printed_rolls;
	$printed_rolls = "";
	$dice = array();
	$operations = array( '+' );
	$curr_op = 0;
	for( $i = 0; $i < strlen($text); $i++ )
		{
		if( substr( $text, $i, 1 ) == "+" )
			{
			array_push( $dice, substr( $text, $curr_op, $i - $curr_op ) );
			array_push( $operations, "+" );
			$curr_op = $i + 1;
			}
		elseif( substr( $text, $i, 1 ) == "-" )
			{
			array_push( $dice, substr( $text, $curr_op, $i - $curr_op ) );
			array_push( $operations, "-" );
			$curr_op = $i + 1;
			}
		elseif( substr( $text, $i, 1 ) == "*" )
			{
			array_push( $dice, substr( $text, $curr_op, $i - $curr_op ) );
			array_push( $operations, "*" );
			$curr_op = $i + 1;
			}
		}
	if( $i != $curr_op )
		{
		array_push( $dice, substr( $text, $curr_op, $i - $curr_op ) );
		array_push( $operations, " " );
		}
	$result = 0;
	for( $i = 0; $i < count($dice); $i++ )
		{
		$die = $dice[$i];
		$operation = $operations[$i];
		if( is_numeric( $die ) )
			{
			if( $operation == "+" )  $result += $die;
			if( $operation == "-" )  $result -= $die;
			if( $operation == "*" )  $result *= $die;
			}
		elseif( stripos( $die, 'd' ) !== false )
			{
			if( $operation == "+" )  $result += rollDie( $die, $operation, $result );
			if( $operation == "-" )  $result -= rollDie( $die, $operation, $result );
			if( $operation == "*" )  $result *= rollDie( $die, $operation, $result );
			}
		}
	if( $printed_rolls != "" )
		$result .= " {" . str_replace( " ", ",", trim($printed_rolls) ) . "} ";
	return $result;
	}

// $err_handler = set_error_handler( "handleErrors" );
?>
