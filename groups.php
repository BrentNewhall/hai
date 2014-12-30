<?php
$page_title = "Groups";
require_once( "header.php" );
require_once( "database.php" );

displayNavbar( $db, $userID );

?>
<h1>Groups</h1>
<?php

if( isset( $_POST["new-group"] )  &&
   $_POST["new-group"] != "" )
   	{
	$invalid_group_names = array( "", "All", "Global", "Public" );
	$group_name = $_POST["new-group"];
	$stmt = $db->stmt_init();
	$sql = "SELECT name FROM user_groups WHERE user = ? AND name = ?";
	$stmt->prepare( $sql );
	$stmt->bind_param( "ss", $userID, $group_name );
	$stmt->execute();
	$stmt->bind_result( $returned_group_name );
	$stmt->fetch();
	if( $returned_group_name == $group_name )
		print( "<p class=\"error\">You already have a group with that name. Please choose a different name.</p>\n" );
	elseif( in_array( $group_name, $invalid_group_names ) )
		print( "<p class=\"error\">You cannot create a group with that name.</p>\n" );
	else
		{
		$stmt = $db->stmt_init();
		$sql = "INSERT INTO user_groups (id, user, name) VALUES (UUID(), ?, ?)";
		$stmt->prepare( $sql );
		$stmt->bind_param( "ss", $userID, $group_name );
		$stmt->execute();
		$stmt->close();
		}
	}

$groups = array();
$stmt = $db->stmt_init();
$sql = "SELECT id, name FROM user_groups WHERE user = ? ORDER BY name";
$stmt->prepare( $sql );
$stmt->bind_param( "s", $userID );
$stmt->execute();
$stmt->bind_result( $group_id, $group_name );
while( $stmt->fetch() )
	{
	$groups[$group_id] = $group_name;
	}
$stmt->close();
foreach( array_keys( $groups ) as $group_id )
	{
	$stmt = $db->stmt_init();
	$sql = "SELECT users.username, users.visible_name FROM user_group_members, users WHERE user_group_members.usergroup = ? AND user_group_members.user = users.id ORDER BY users.visible_name LIMIT 5";
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $group_id );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $username, $user_visible_name );
	$membership_count = $stmt->num_rows;
	print( "<p><a href=\"groups.php?g=$group_id\">" . $groups[$group_id] . "</a> ($membership_count members" );
	if( $membership_count > 0 )
		{
		print( ": " );
		$members = array();
		while( $stmt->fetch() )
			{
			array_push( $members, "<a href=\"user.php?id=$username\">$user_visible_name</a>" );
			}
		print( implode( ", ", $members ) );
		if( $membership_count > 5 )
			print( "..." );
		}
	$stmt->close();
	print( ") <button>Rename</button> <button>Merge</button> <button>Delete</button></p>\n" );
	//print( "<p><a href=\"groups.php?g=$group_id\">" . $groups[$group_id] . "</a> ($membership_count members)</p>\n" );
	}

?>
<form action="groups.php" method="post">
<input type="submit" value="Add group" />
<input type="text" name="new-group" size="20" />
</form>
<?php

require_once( "footer.php" );
?>
