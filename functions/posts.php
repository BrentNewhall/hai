<?php
function displayPosts( $db, $db2, $sql, $userID, $max_posts, $param_types, $param1 = "", $param2 = "", $param3 = "" )
	{
	$total_count_sql = substr( $sql, strpos( $sql, " FROM " ) );
	$total_count_sql = substr( $total_count_sql, 0, strpos( $total_count_sql, " ORDER BY " ) );
	$total_count_sql = "SELECT COUNT(*)" . $total_count_sql;
	//print( "SQL: $sql<br>\n$total_count_sql<br>" );
	//print "$param_types $param1 $param2<br>" ;
	if( $param3 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1, $param2, $param3 );
	elseif( $param2 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1, $param2 );
	elseif( $param1 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1 );
	else
		$total_count = get_db_value( $db, $total_count_sql );
	//print( "Total: $total_count<br>\n" );
	$output = "";
	$stmt = $db->stmt_init();
	// If not logged in, only display public posts
	if( $userID == "" )
		{
		$p = strpos( $sql, " WHERE " );
		if( $p > 0 )
			$sql = str_replace( " WHERE ", " WHERE posts.public = 1 AND ", $sql );
		else
			$sql = str_replace( "ORDER BY", " WHERE posts.public = 1 ORDER BY", $sql );
		}
	//print( "FINAL SQL: $sql<br>\n" );
	if( $stmt->prepare( $sql )  &&  $total_count > 0 )
		{
		print( "<div id=\"post-container\" class=\"post-container\">\n" );
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
		$stmt->bind_result( $post_id, $content, $created, $author_visible_name, $author_real_name, $author_username, $author_public, $author_id, $parent_post_id, $editable, $broadcast_id );
		$post_index = 0;
		while( $stmt->fetch()  &&  $post_index < $max_posts )
			{
			// Get world info
			$world_name = "";
			$p_stmt = $db2->stmt_init();
			$p_stmt = $db2->prepare( "SELECT worlds.id, worlds.display_name FROM worlds, world_posts WHERE world_posts.world = worlds.id AND world_posts.post = ?" );
			$p_stmt->bind_param( "s", $post_id );
			$p_stmt->execute();
			$p_stmt->bind_result( $world_id, $world_name );
			$p_stmt->fetch();
			$p_stmt->close();
			print( "<div class=\"post\">\n" );
			printAuthorInfo( $db2, $userID, $author_id, $author_username, $author_visible_name, $author_real_name, $author_public, $post_id, "full" );
			print( "<div class=\"post-content\"" );
			if( $userID != ""  &&  $userID != 0 )
				print( " onmouseover=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='visible';\" onmouseleave=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='hidden';\"" );
			print( ">" );
			print( "<div class=\"timestamp\"><a href=\"post.php?i=$post_id#main-post\">" . getAge( $created ) . "</a></div>\n" );
			if( $editable == 1 )
				{
				$timeout = intval( get_db_value( $db2, "SELECT timeout FROM post_locks WHERE post = ?", "s", $post_id ) );
				if( $timeout < time() )
					{
					$compressed_content = compressContent( $content );
					$author_is_editor = 0;
					if( $userID == $author_id )
						$author_is_editor = 1;
					print( "<div class=\"edit-icon\"><a href=\"#\" onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','','',$author_is_editor);document.getElementById('set-post-editable').checked=true;updatePreview('compose-post','post-preview');return false;\"><img src=\"assets/images/pencil.png\" width=\"16\" height=\"16\" alt=\"Edit\" title=\"Click here to edit this post.\" /></a></div>\n" );
					}
				else
					{
					$name_of_locked_user = intval( get_db_value( $db2, "SELECT visible_name FROM users JOIN post_locks ON (post_locks.user = users.id AND post_locks.post = ?)", "s", $post_id ) );
					$minutes_until_unlocked = intval( ($timeout - time()) / 60 );
					$msg = "$minutes_until_unlocked minutes";
					if( $minutes_until_unlocked == 0 )
						$msg = "less than a minute";
					elseif( $minutes_until_unlocked == 1 )
						$msg = "1 minute";
					print( "<div class=\"edit-icon\"><img src=\"assets/images/pencil-disabled.png\" width=\"16\" height=\"16\" alt=\"Edit\" title=\"$name_of_locked_user is editing this post. It will become unlocked in $msg.\" /></div>\n" );
					}
				}
			if( $world_name != "" )
				print( "<div class=\"in-world\">In the world of <a href=\"world.php?i=$world_id\" class=\"world-name\">$world_name</a>:</div>\n" );
			if( $broadcast_id != "" )
				{
				$u_stmt = $db2->prepare( "SELECT broadcasts.created, users.id, users.real_name, users.visible_name, users.profile_public FROM broadcasts JOIN users ON (broadcasts.user = users.id) WHERE broadcasts.id = ?" );
				$u_stmt->bind_param( "s", $broadcast_id );
				$u_stmt->execute();
				$u_stmt->bind_result( $b_time, $b_user_id, $b_real_name, $b_visible_name, $b_public );
				$u_stmt->fetch();
				$u_stmt->close();
				print( "<div class=\"broadcast\">Broadcast " . getAge( $b_time) . " ago by " . getAuthorLink( $b_user_id, $b_visible_name, $b_real_name, $b_public ) . "</div>\n" );
				}
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
						print( "<a onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','','',1);updatePreview('compose-post','post-preview');return false\" href=\"#\">" );
					print( getAge( $comment_created ) );
					if( $commenter_id == $userID )
						print( "</a><br /><div id=\"comment-edit-link-$comment_id\" style=\"float: right; display: none;\"><a onclick=\"javascript:setComposeForEdit('$post_id','compose-comment-$post_id','$compressed_comment','','$comment_id','','',1);updatePreview('compose-comment-$post_id','comment-preview-$post_id');return false;\" href=\"#\">Edit</a></div>" );
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
					$post_is_public = get_db_value( $db, "SELECT public FROM posts WHERE id = ?", "s", $post_id );
					print( "<a href=\"#\" onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','$editable','$post_is_public',1);updatePreview('compose-post','post-preview');return false;\">Edit</a> &nbsp; <a onclick=\"javascript:displayDelete('$post_id');return false;\" href=\"#\">Delete</a> &nbsp; " );
					}
				else
					{
					$tracking = get_db_value( $db, "SELECT COUNT(*) FROM tracking WHERE user = ? AND post = ?", "ss", $userID, $post_id );
					if( $tracking >= 1 )
						print( "Tracking &nbsp; " );
					else
						print( "<a title=\"Get pinged when comments are added to this post.\" href=\"track.php?i=$post_id&redirect=" . getRedirectURL() . "\">Track</a> &nbsp; " );
					print( "<a title=\"Share this post with people who have you in their teams.\" href=\"broadcast.php?i=$post_id&redirect=" . getRedirectURL() . "\">Broadcast</a> &nbsp; " );
					}
				print( "<a onclick=\"javascript:setReplyTo('$post_id', '$author_visible_name', '$snippet');\" href=\"#top\">Reply with post</a> &nbsp; <a onclick=\"javascript:toggleComposePane('compose-tools-$post_id','compose-pane-$post_id','compose-comment-$post_id');return false;\" href=\"#\">Reply with comment</a>&nbsp;&nbsp;</div> <!-- .post-navigation -->\n" );
				displayComposePane( "comment", $db, $userID, $post_id );
				}
			print( "</div>\n" ); // end .post-content
			print( "</div>\n" ); // end .post
			$post_index++;
			}
		// If there are more results even than this,
		if( $post_index < $total_count )
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
		print $db->error;
		print $stmt->error;
	}

function displayPostsV2( $db, $db2, $sql, $userID, $max_posts, $param_types, $param1 = "", $param2 = "", $param3 = "" )
	{
	$total_count_sql = substr( $sql, strpos( $sql, " FROM " ) );
	$total_count_sql = substr( $total_count_sql, 0, strpos( $total_count_sql, " ORDER BY " ) );
	$total_count_sql = "SELECT COUNT(*)" . $total_count_sql;
	//print( "SQL: $sql<br>\n$total_count_sql<br>" );
	//print "$param_types $param1 $param2<br>" ;
	if( $param3 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1, $param2, $param3 );
	elseif( $param2 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1, $param2 );
	elseif( $param1 != "" )
		$total_count = get_db_value( $db, $total_count_sql, $param_types, $param1 );
	else
		$total_count = get_db_value( $db, $total_count_sql );
	//print( "Total: $total_count<br>\n" );
	$output = "";
	$stmt = $db->stmt_init();
	// If not logged in, only display public posts
	if( $userID == "" )
		{
		$p = strpos( $sql, " WHERE " );
		if( $p > 0 )
			$sql = str_replace( " WHERE ", " WHERE posts.public = 1 AND ", $sql );
		else
			$sql = str_replace( "ORDER BY", " WHERE posts.public = 1 ORDER BY", $sql );
		}
	//print( "FINAL SQL: $sql<br>\n" );
	if( $stmt->prepare( $sql )  &&  $total_count > 0 )
		{
		print( "<div id=\"post-container\" class=\"post-container\">\n" );
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
		$stmt->bind_result( $post_id, $created_date );
		$post_index = 0;
		$post_ids = array();
		while( $stmt->fetch()  &&  $post_index < $max_posts )
			{
			array_push( $post_ids, $post_id );
			}
		$stmt->close();
		foreach( $post_ids as $post_id )
			{
			displayPost( $db, $db2, $post_id, $userID );
			$post_index++;
			}
		// If there are more results even than this,
		if( $post_index < $total_count )
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
	}

function printCommentEdits( $db, $comment_id )
	{
	global $display_comment_history;
	if( ! isset( $display_comment_history )  ||
	    $display_comment_history != "yes" )
		return;
	$num_edits = get_db_value( $db, "SELECT COUNT(*) FROM comment_history WHERE comment = ?", "s", $comment_id );
	if( $num_edits > 0 )
		{
		print( "<div id=\"comment-history\">\n" );
		$sql = "SELECT original_content, edited, author, real_name, visible_name, profile_public FROM comment_history JOIN users ON (comment_history.author = users.id) WHERE comment_history.comment = ? ORDER BY edited DESC";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		print $stmt->error;
		$stmt->bind_param( "s", $comment_id );
		$stmt->execute();
		$stmt->bind_result( $content, $timestamp, $author, $real_name, $visible_name, $profile_public );
		while( $stmt->fetch() )
			{
			print( "<div class=\"history-info\"><span title=\"" . date( "d M Y @ H:i", $timestamp ) . "\">" . getAge( $timestamp ) . " ago</a>, " .
			       getAuthorLink( $author, $visible_name, $real_name, $profile_public ) .
			       " changed this comment from:</div>\n" .
			       formatPost( $content ) );
			}
		print( "</div>\n" );
		}
	}

function displayPost( $db, $db2, $post_id, $userID )
	{
	$stmt = $db->stmt_init();
	$sql = "SELECT posts.content, " .
	              "GREATEST(IFNULL(posts.created,0)," .
				           "IFNULL(broadcasts.created,0)) AS bothcreated, " .
	              "users.visible_name, users.real_name, " .
	              "users.username, users.profile_public, " .
	              "posts.author, posts.parent, posts.editable, " .
				  "posts.comments, posts.public, " .
	              "broadcasts.id " .
	       "FROM posts " .
	       "JOIN users ON (posts.author = users.id) " .
	       "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
	       "WHERE posts.id = ?";
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $post_id );
		$stmt->execute();
		print $db->error;
		print $stmt->error;
		$stmt->store_result();
		$stmt->bind_result( $content, $created, $author_visible_name, $author_real_name, $author_username, $author_public, $author_id, $parent_post_id, $editable, $comments, $post_public, $broadcast_id );
		$post_index = 0;
		$stmt->fetch();
		$stmt->close();
		// If author is blocked, don't display post.
		$blocked = get_db_value( $db, "SELECT id FROM blocks WHERE blocker = ? AND troll = ?", "ss", $userID, $author_id );
		if( $blocked != "" )
			return;
		// Get world info
		$world_name = "";
		$p_stmt = $db->stmt_init();
		$p_stmt = $db->prepare( "SELECT worlds.id, worlds.display_name FROM worlds, world_posts WHERE world_posts.world = worlds.id AND world_posts.post = ?" );
		$p_stmt->bind_param( "s", $post_id );
		$p_stmt->execute();
		$p_stmt->bind_result( $world_id, $world_name );
		$p_stmt->fetch();
		$p_stmt->close();
		print( "<div class=\"post\">\n" );
		printAuthorInfo( $db, $userID, $author_id, $author_username, $author_visible_name, $author_real_name, $author_public, $post_id, "full" );
		print( "<div class=\"post-content\"" );
		if( $userID != ""  &&  $userID != 0 )
			print( " onmouseover=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='visible';\" onmouseleave=\"javascript:document.getElementById('post-navigation-$post_id').style.visibility='hidden';\"" );
		print( ">" );
		print( "<div class=\"timestamp\"><a href=\"post.php?i=$post_id#main-post\">" . getAge( $created ) . "</a></div>\n" );
		if( $editable == 1 )
			{
			$timeout = intval( get_db_value( $db, "SELECT timeout FROM post_locks WHERE post = ?", "s", $post_id ) );
			if( $timeout < time() )
				{
				$compressed_content = compressContent( $content );
				$author_is_editor = 0;
				if( $userID == $author_id )
					$author_is_editor = 1;
				print( "<div class=\"edit-icon\"><a href=\"#\" onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','$editable','$post_public',$author_is_editor);document.getElementById('set-post-editable').checked=true;updatePreview('compose-post','post-preview');return false;\"><img src=\"assets/images/pencil.png\" width=\"16\" height=\"16\" alt=\"Edit\" title=\"Click here to edit this post.\" /></a></div>\n" );
				}
			else
				{
				$name_of_locked_user = intval( get_db_value( $db, "SELECT visible_name FROM users JOIN post_locks ON (post_locks.user = users.id AND post_locks.post = ?)", "s", $post_id ) );
				$minutes_until_unlocked = intval( ($timeout - time()) / 60 );
				$msg = "$minutes_until_unlocked minutes";
				if( $minutes_until_unlocked == 0 )
					$msg = "less than a minute";
				elseif( $minutes_until_unlocked == 1 )
					$msg = "1 minute";
				print( "<div class=\"edit-icon\"><img src=\"assets/images/pencil-disabled.png\" width=\"16\" height=\"16\" alt=\"Edit\" title=\"$name_of_locked_user is editing this post. It will become unlocked in $msg.\" /></div>\n" );
				}
			}
		if( $world_name != "" )
			print( "<div class=\"in-world\">In the world of <a href=\"world.php?i=$world_id\" class=\"world-name\">$world_name</a>:</div>\n" );
		if( $broadcast_id != "" )
			{
			$u_stmt = $db->prepare( "SELECT broadcasts.created, users.id, users.real_name, users.visible_name, users.profile_public FROM broadcasts JOIN users ON (broadcasts.user = users.id) WHERE broadcasts.id = ?" );
			$u_stmt->bind_param( "s", $broadcast_id );
			$u_stmt->execute();
			$u_stmt->bind_result( $b_time, $b_user_id, $b_real_name, $b_visible_name, $b_public );
			$u_stmt->fetch();
			$u_stmt->close();
			print( "<div class=\"broadcast\">Broadcast " . getAge( $b_time) . " ago by " . getAuthorLink( $b_user_id, $b_visible_name, $b_real_name, $b_public ) . "</div>\n" );
			}
		// Get reply-to info
		if( $parent_post_id != ""  &&  $userID != ""  &&  $userID != 0 )
			{
			$p_stmt = $db->stmt_init();
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
		$comments_stmt = $db->stmt_init();
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
				$blocked = get_db_value( $db, "SELECT id FROM blocks WHERE blocker = ? AND troll = ?", "ss", $userID, $commenter_id );
				if( $blocked == "" )
					{
					print( "<div class=\"comment\"" );
					if( $commenter_id == $userID )
						print( "onmouseover=\"javascript:document.getElementById('comment-edit-link-$comment_id').style.display='block';\" onmouseleave=\"javascript:document.getElementById('comment-edit-link-$comment_id').style.display='none';\"" );
					print( ">\n" );
					printCommentEdits( $db2, $comment_id );
					printAuthorInfo( $db, $userID, $commenter_id, $commenter_username, $commenter_visible_name, $commenter_real_name, $commenter_public, $comment_id, "comment" );
					$compressed_comment = compressContent( $comment_content );
					print( "<div class=\"comment-content\"><div class=\"timestamp\">" );
					if( $commenter_id == $userID )
						print( "<a onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','','',1);updatePreview('compose-post','post-preview');return false\" href=\"#\">" );
					print( getAge( $comment_created ) );
					if( $commenter_id == $userID )
						print( "</a><br /><div id=\"comment-edit-link-$comment_id\" style=\"float: right; display: none;\"><a onclick=\"javascript:setComposeForEdit('$post_id','compose-comment-$post_id','$compressed_comment','','$comment_id','','',1);updatePreview('compose-comment-$post_id','comment-preview-$post_id');return false;\" href=\"#\">Edit</a></div>" );
					print( "</div>" . formatPost( $comment_content ) . "</div>" );
					print( "</div>\n" ); // end .comment
					} // not blocked
				}
			if( $comments_stmt->num_rows > 0 )
				print( "</div>\n" ); // end .comments
			}
		// Display actions bar at bottom of post
		if( $userID != ""  &&  $userID != 0 )
			{
			$snippet = getPostSnippet( $content );
			print( "<div id=\"post-navigation-$post_id\" class=\"post-navigation\" style=\"visibility: hidden\"><a href=\"post.php?i=$post_id#main-post\">View conversation</a> &nbsp; " );
			if( $author_id == $userID )
				{
				$compressed_content = compressContent( $content );
				$post_is_public = get_db_value( $db, "SELECT public FROM posts WHERE id = ?", "s", $post_id );
				print( "<a href=\"#\" onclick=\"javascript:setComposeForEdit('$post_id','compose-post','$compressed_content','$world_name','','$editable','$post_is_public',1);updatePreview('compose-post','post-preview');return false;\">Edit</a> &nbsp; <a onclick=\"javascript:displayDelete('$post_id');return false;\" href=\"#\">Delete</a> &nbsp; " );
				}
			else
				{
				$tracking = get_db_value( $db, "SELECT COUNT(*) FROM tracking WHERE user = ? AND post = ?", "ss", $userID, $post_id );
				$num_user_comments = get_db_value( $db, "SELECT COUNT(*) FROM comments WHERE author = ? AND post = ?", "ss", $userID, $post_id );
				if( $tracking >= 1  ||  $num_user_comments >= 1 )
					print( "Tracking &nbsp; " );
				else
					print( "<a title=\"Get pinged when comments are added to this post.\" href=\"track.php?i=$post_id&redirect=" . getRedirectURL() . "\">Track</a> &nbsp; " );
				print( "<a title=\"Share this post with people who have you in their teams.\" href=\"broadcast.php?i=$post_id&redirect=" . getRedirectURL() . "\">Broadcast</a> &nbsp; " );
				}
			print( "<a onclick=\"javascript:setReplyTo('$post_id', '$author_visible_name', '$snippet');\" href=\"#top\">Reply with post</a> &nbsp; " );
			if( $comments == 1 )
				print( "<a onclick=\"javascript:toggleComposePane('compose-tools-$post_id','compose-pane-$post_id','compose-comment-$post_id');return false;\" href=\"#\">Reply with comment</a>" );
			print( "&nbsp;&nbsp;</div> <!-- .post-navigation -->\n" );
			if( $comments == 1 )
				displayComposePane( "comment", $db, $userID, $post_id );
			}
		print( "</div>\n" ); // end .post-content
		print( "</div>\n" ); // end .post
		}
	}

function processListItem( $list_item, $list_type, $in_list, $num_stars )
	{
	$list_item = substr( $list_item, $num_stars + 1 );
	$list_item = "<li> " . $list_item;
	$diff = $num_stars - $in_list;
	while( $diff > 0 )
		{
		$list_item = "<" . $list_type . ">" . $list_item;
		$diff--;
		}
	while( $diff < 0 )
		{
		$list_item = "</" . $list_type . ">" . $list_item;
		$diff++;
		}
	$list_item = preg_replace( "/<br \/>$/", "</li>", $list_item );
	return $list_item;
	}

function addVideoEmbed( $matches )
	{
	$videoURL = $matches[1] . "://" . $matches[2] . "." . $matches[3];
	$uuid = getGUID();
	$output = "<div id=\"$uuid\">Loading the player...</div>" .
	          "<script type=\"text/javascript\">" .
	          "jwplayer(\"$uuid\").setup({" .
	          "file: \"$videoURL\"," .
	          "image: \"assets/images/video-background.jpg\"," .
	          "width: 500," .
	          "height: 280" .
	          "});" .
	          "</script>";
	return $output;
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
		if( preg_match( "/^[\*]+ /", $lines[$i] ) > 0 )
			{
			$num_stars = strpos( $lines[$i], " " );
			$lines[$i] = processListItem( $lines[$i], "ul", $in_ul, $num_stars );
			$in_ul = $num_stars;
			}
		elseif( $in_ul > 0 )
			{
			while( $in_ul > 0 )
				{
				$lines[$i] = "</ul>" . $lines[$i];
				$in_ul--;
				}
			}
		if( preg_match( "/^[\#]+ /", $lines[$i] ) > 0 )
			{
			$num_stars = strpos( $lines[$i], " " );
			$lines[$i] = processListItem( $lines[$i], "ol", $in_ol, $num_stars );
			$in_ol = $num_stars;
			}
		elseif( preg_match( "/^[0-9] /", $lines[$i] ) > 0 )
			{
			$num_stars = strpos( $lines[$i], " " );
			$lines[$i] = processListItem( $lines[$i], "ol", $in_ol, $num_stars );
			$in_ol = $num_stars;
			}
		elseif( $in_ol > 0 )
			{
			while( $in_ol > 0 )
				{
				$lines[$i] = "</ol>" . $lines[$i];
				$in_ol--;
				}
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
	// Process die rolls
	$text = preg_replace( "/\[ROLLED\]([-]*[0-9]+) \{([\S\s]+?)\} ([\S\s]+?)\[\/ROLLED\]/i", "<span class=\"die-roll\"><strong>$1</strong> (rolled $2 on $3)</span>", $text );
	$text = preg_replace( "/\[ROLLED\]([-]*[0-9]+) ([\S\s]+?)\[\/ROLLED\]/i", "<span class=\"die-roll\"><strong>$1</strong> ($2)</span>", $text );
	// Process other stuff
	$text = preg_replace( "/@\"([\S\s]+?)\"/", "<span class=\"reply-name\">@$1</span>", $text );
	$text = preg_replace( "/<br \/>\n<br \/>\n<ul>/", "<br />\n<ul>", $text );
	$text = preg_replace( "/<br \/>\n<br \/>\n<ol>/", "<br />\n<ol>", $text );
	$text = preg_replace( "/'''([\S\s]+?)'''/", "<strong>$1</strong>", $text );
	$text = preg_replace( "/''([\S\s]+?)''/", "<em>$1</em>", $text );
	$text = preg_replace( "/(\s|^)__([\S\s]+?)__(\s|$)/", "$1<strong>$2</strong>$3", $text );
	$text = preg_replace( "/\*\*([\S\s]+?)\*\*/", "<strong>$1</strong>", $text );
	//$text = preg_replace( "/(\s|^)_([\S\s]+?)_(\s|\.|$)/", "$1<em>$2</em>$3", $text );
	$text = preg_replace( "/(\s|^)_([\S\s]+?)_(\s|\n|\.|\,|\:|$)/", "$1<em>$2</em>$3", $text );
	$text = preg_replace( "/\*([\S\s]+?)\*/", "<em>$1</em>", $text );
	$text = preg_replace( "/\[(http|https):\/\/([\S]+) ([\S\s]+?)\]/i", "<a href=\"$1://$2\">$3</a>", $text );
	$text = preg_replace( "/(http|https):\/\/www\.youtube\.com\/watch\?v=([\S]+)/i", "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"http://www.youtube.com/embed/$2\" frameborder=\"0\"></iframe>", $text );
	$text = preg_replace( "/(http|https):\/\/youtu\.be\/([\S]+)/i", "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"http://www.youtube.com/embed/$2\" frameborder=\"0\"></iframe>", $text );
	$text = preg_replace( "/(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)\|([0-9]+)/", "<img src=\"$1://$2.$3\" style=\"width: $4px; max-width: 500px\" />", $text );
	$text = preg_replace_callback( "/(http|https):\/\/([\S]+)\.(mp4)/", "addVideoEmbed", $text );
	$text = preg_replace( "/(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)([^\"])/", "<img src=\"$1://$2.$3\" style=\"max-width: 500px\" />$4", $text );
	$text = preg_replace( "/(http|https):\/\/([A-Za-z0-9\.\%$&\?\#\/\-_=]+)(\s|\n|$)/im", "<a href=\"$1://$2\">$2</a>$3", $text );
	$text = preg_replace( "/(\s|^)#([A-Za-z0-9\-]+)/", "$1<a href=\"hashtag.php?tag=$2\">#$2</a>", $text );
	return $text;
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




function editPost( $db, $userID, $post_id, $content, $world, $editable, $comments )
	{
	// Editing a post.
	// Add history.
	$original_content = get_db_value( $db, 
	                    "SELECT content FROM posts WHERE id = ?", "s",
						$post_id );
	$sql = "INSERT INTO post_history " .
	       "(id, post, author, edited, original_content) " .
		   "VALUES (UUID(), ?, ?, ?, ?)";
	$result = update_db( $db, $sql, "ssis", $post_id, $userID, time(), $original_content );
	// Update post.
	$sql = "UPDATE posts SET content = ?, editable = ?, comments = ? WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "siis", $content, $editable, $comments, $post_id );
	$stmt->execute();
	$stmt->close();
	// If world is different,
	$current_world_name_basic = processWorldNameForBasic( get_db_value( $db, "SELECT worlds.basic_name FROM worlds JOIN world_posts ON (world_posts.world = worlds.id AND world_posts.post = ?)", "s", $post_id ) );
	$posted_world_name_basic  = processWorldNameForBasic( $world );
	// Update world.
	if( $current_world_name_basic != $posted_world_name_basic )
		{
		$result = update_db( $db, "DELETE FROM world_posts WHERE post = ?", "s", $post_id );
		$new_world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $posted_world_name_basic );
		if( $new_world_id == "" )
			{
			$posted_world_name_display = processWorldNameForDisplay( $world );
			update_db( $db, "INSERT INTO worlds (id, basic_name, display_name, class) VALUES (UUID(), ?, ?, UUID())", "ss", $posted_world_name_basic, $posted_world_name_display );
			$new_world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ? AND display_name = ?", "ss", $posted_world_name_basic, $posted_world_name_display );
			}
		$result = update_db( $db, "INSERT INTO world_posts (id, world, post) VALUES (UUID(), ?, ?)", "ss", $new_world_id, $post_id );
		$_POST["redirect"] = "world.php?i=$new_world_id";
		}
	}

function processDieRoll( $matches )
	{
	$dice = $matches[0];
	if( strtoupper( substr( $dice, 0, 6 ) ) == "[ROLL " )
		$dice = substr( $dice, 6, strlen($dice) - 7 );
	else
		$dice = substr( $dice, 6, strlen($dice) - 13 );
	return "[rolled]" . rollDice( $dice ) . " " . $dice . "[/rolled]";
	}

function insertPost( $db, $userID, $post_content, $parent, $public, $editable, $comments, $world_name = "" )
	{
	// Process die rolls
	$post_content = preg_replace_callback( "/\[ROLL\]([\S\s])+?\[\/ROLL\]/i", "processDieRoll", $post_content );
	$post_content = preg_replace_callback( "/\[ROLL ([\S\s])+?\]/i", "processDieRoll", $post_content );
	update_db( $db, "INSERT INTO posts (id, author, created, content, " .
	                          "parent, public, editable, comments) " .
							  "VALUES (UUID(), ?, ?, ?, ?, ?, ?, ?)",
	           "sissiii", $userID, time(), $post_content, $parent, $public,
	                      $editable, $comments );
	$new_post_id = get_db_value( $db, "SELECT id FROM posts WHERE author = ? " .
	                             "ORDER BY created DESC LIMIT 1", "s", $userID );
	if( $world_name != "" )
		{
		$full_world_name = processWorldNameForDisplay( $world_name );
		$basic_world_name = processWorldNameForBasic( $world_name );
		$world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $basic_world_name );
		if( $world_id == "" )
			{ // It doesn't exist, so create it
			update_db( $db, "INSERT INTO worlds (id, basic_name, display_name, class) VALUES (UUID(), ?, ?, UUID())", "ss", $basic_world_name, $full_world_name );
			$world_id = get_db_value( $db, "SELECT id FROM worlds WHERE basic_name = ?", "s", $basic_world_name );
			}
		update_db( $db, "INSERT INTO world_posts (id, world, post) VALUES (UUID(), ?, ?)", "ss", $world_id, $new_post_id );
		}
	// Post to Facebook
	// Not yet implemented; this is for future use once I get
	// authentication working.
	if( isset( $facebook )  &&  $_POST["post-to-facebook"] != "" )
		{
		// User is logged in to Facebook
		// Strip all formatting from post.
		$fb_content = strip_tags( formatPost( $post_content ) );
		// Construct Facebook message and post.
		$fields = array(
			"message" => $fb_content,
			"name" => "Full post on Hai",
			"link" => "http://hai.social/post.php?i=$new_post_id",
			"description" => $fb_content
		);
		$result = $facebook->api( "/me/feed/", "post", $fields );
		}
	// Process @ replies
	preg_match_all( "/[ ^]@\"[\S\s]+?\"/", $post_content, $matches );
	foreach( $matches[0] as $match )
		{
		// Remove @" and " from match.
		$pos = strpos( $match, '@' );
		$match = substr( $match, $pos + 2 );
		$match = substr( $match, 0, strlen($match) - 1 );
		// If not already tracking,
		$is_tracking = get_db_value( $db, "SELECT pings.id FROM pings JOIN users ON (users.id = pings.user AND users.visible_name = ?) WHERE content_id = ?", "ss", $match, $new_post_id );
		if( $is_tracking == "" )
			{
			// Add ping.
			$match_user_id = get_db_value( $db, "SELECT id FROM users WHERE users.visible_name = ?", "s", $match );
			update_db( $db, "INSERT INTO pings (id, user, created, content_type, content_id, is_read) VALUES (UUID(), ?, ?, 'm', ?, 0)", "sis", $match_user_id, time(), $new_post_id );
			}
		}
	}

function editComment( $db, $userID, $comment_id, $content )
	{
	// Add history.
	$original_content = get_db_value( $db, 
	                    "SELECT content FROM comments WHERE id = ?", "s",
						$comment_id );
	$sql = "INSERT INTO comment_history " .
	       "(id, comment, author, edited, original_content) " .
		   "VALUES (UUID(), ?, ?, ?, ?)";
	update_db( $db, $sql, "ssis", $comment_id, $userID, time(),
	           $original_content );
	// Update comment.
	update_db( $db, "UPDATE comments SET content = ? WHERE id = ?", "ss",
	           $content, $comment_id );
	}
?>
