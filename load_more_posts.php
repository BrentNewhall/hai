<?php

// Returns more paged posts

require_once( "database.php" );
require_once( "functions.php" );
$tab = 0;
if( isset( $_GET["tab"] ) )
	$tab = $_GET["tab"];
$start_index = 0;
if( isset( $_GET["index"] ) )
	$start_index = $_GET["index"];
$userID = "";
if( isset( $_GET["u"] ) )
	$userID = $_GET["u"];

//print( "$start_index user $user_id<br>\n" );;

if( $tab == "Global" )
	{
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, posts.parent FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "WHERE posts.public = 1 " .
	       "ORDER BY posts.created DESC LIMIT $start_index, 25";
	displayPosts( $db, $db2, $sql, $userID, 25, "none" );
	}
elseif( $tab != "" )
	{
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, parent_posts.id FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "JOIN user_groups ON (user_groups.user = ? AND user_groups.id = ?) " .
		   "JOIN user_group_members ON (user_groups.id = user_group_members.usergroup AND users.id = user_group_members.user) " .
		   "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
	       "ORDER BY posts.created LIMIT $start_index, 25 DESC";
	displayPosts( $db, $db2, $sql, $userID, 25, "ss", $userID, $tab );
	}
else
	{
	$sql = "SELECT DISTINCT posts.id, posts.content, posts.created, users.visible_name, users.real_name, users.username, users.profile_public, posts.author, parent_posts.id FROM posts " .
		   "JOIN users ON (posts.author = users.id) " .
		   "LEFT JOIN user_groups ON (user_groups.user = ?) " .
		   "LEFT JOIN user_group_members ON (user_groups.id = user_group_members.usergroup) " .
		   "LEFT JOIN posts parent_posts on (parent_posts.id = posts.parent) " .
		   #"LEFT JOIN post_groups pg ON (posts.id = post_groups.post) " .
		   #"LEFT JOIN user_group_members ugm2 ON (ugm2.usergroup = pg.usergroup AND ugm2.user = ?) " . // ? = userID
		   "WHERE posts.author = ? OR posts.author = user_group_members.user " .
	       "ORDER BY posts.created DESC";
	displayPosts( $db, $db2, $sql, $userID, 25, "ss", $userID, $userID );
	}
?>
