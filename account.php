<?php
$page_title = "Account";
require_once( "database.php" );
require_once( "header.php" );

requireLogin( $db, $db2 );
displayNavbar( $db, $userID );

function deleteFromTable( $db, $table, $userID )
	{
	$stmt = $db->stmt_init();
	$stmt->prepare( "DELETE FROM $table WHERE user = ?" );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->close();
	}

function updateInterestsPublic( $db, $vars )
	{
	foreach( array_keys( $vars ) as $id )
		{
		if( substr( $id, 0, 6 ) == "Rooms-" )
			{
			$room_id = substr( $id, 6 );
			update_db( $db, "UPDATE room_members SET public = 1 WHERE id = ?", "s", $room_id );
			}
		elseif( substr( $id, 0, 7 ) == "Worlds-" )
			{
			$world_id = substr( $id, 7 );
			update_db( $db, "UPDATE user_worlds SET public = 1 WHERE id = ?", "s", $world_id );
			}
		}
	}

if( isset( $_POST["upload"] ) )
	{
	$allowed_extensions = array( "gif", "jpg", "jpeg", "png" );
	$allowed_mime_types = array( "image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png" );
	$temp = explode(".", $_FILES["file"]["name"]);
	$extension = end($temp);
	if( $_FILES["file"]["error"] > 0 )
		{
		echo "<p class=\"error\">File upload error: " . $_FILES["file"]["error"] . "</p>";
		}
	else
		{
		/* echo "Upload: " . $_FILES["file"]["name"] . "<br>";
		echo "Type: " . $_FILES["file"]["type"] . "<br>";
		echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
		echo "Stored in: " . $_FILES["file"]["tmp_name"]; */
		// Only upload if less than 2 MB
		if( $_FILES["file"]["size"] > (1024 * 1024) * 2 )
			{
			print( "<p class=\"error\">File too large. Please upload a file smaller than 2 megabytes.</p>" );
			}
		elseif( $_FILES["file"]["type"] != ""  &&  ! in_array( $_FILES["file"]["type"], $allowed_mime_types ) )
			{
			print( "<p class=\"error\">Only gifs, jpegs, and pngs allowed.</p> " . $_FILES["file"]["type"] );
			}
		elseif( ! in_array( $extension, $allowed_extensions ) )
			{
			print( "<p class=\"error\">Only gifs, jpegs, and pngs allowed. Wrong extension of $extension</p>" );
			}
		else
			{
			move_uploaded_file( $_FILES["file"]["tmp_name"],
			                    "assets/images/avatars/" .
			                    $userID );
			}
		}
	}

$visible_name   = "";
$real_name      = "";
$profile_public = "";
if( isset( $_POST["visible-name"] ) )
	{
	$visible_name   = $_POST["visible-name"];
	$real_name      = $_POST["real-name"];
	$password       = $_POST["new-password"];
	$profile_public = $_POST["profile-public"];
	$about          = $_POST["about"];
	if( $profile_public == "" )
		$profile_public = 0;
	else
		$profile_public = 1;
	$sql = "UPDATE users SET visible_name = ?, real_name = ?, profile_public = ?, about = ?";
	if( $password != ""  &&  ! testPassword( $password ) )
		{
		echo "<p class=\"error\">Passwords must be at least 8 characters and must contain at least one upper-case letter, at least one number, and at least one symbol.</p>";
		}
	else
		{
		if( $password != "" )
			$sql .= ", password = ?";
		$sql .= " WHERE id = ?";
		$stmt = $db->stmt_init();
		if( $stmt->prepare( $sql ) )
			{
			if( $password != "" )
				{
				$pwd = crypt( $password, $crypt_salt );
				$stmt->bind_param( "ssisss", $visible_name, $real_name, $profile_public, $about, $pwd, $userID );
				}
			else
				$stmt->bind_param( "ssiss", $visible_name, $real_name, $profile_public, $about, $userID );
			$stmt->execute();
			$stmt->close();
			// Wipe out all phone numbers
			deleteFromTable( $db, "user_phones", $userID );
			// Add all phone numbers
			$phones   = $_POST["phones"];
			$carriers = $_POST["carriers"];
			$public_phones = $_POST["public-phones"];
			foreach( $phones as $key => $phone )
				{
				if( $phone != "" )
					{
					$carrier = $carriers[$key];
					$public = 0;
					if( $public_phones[$key] != "" )
						$public = 1;
					$stmt = $db->stmt_init();
					$stmt->prepare( "INSERT INTO user_phones (user, phone, carrier, public) VALUES (?, ?, ?, ?)" );
					$stmt->bind_param( "sssi", $userID, $phone, $carrier, $public );
					$stmt->execute();
					$stmt->close();
					}
				}
			// Wipe out all email addresses
			deleteFromTable( $db, "user_emails", $userID );
			// Add all email addresses
			$emails        = $_POST["emails"];
			$public_emails = $_POST["public-emails"];
			foreach( $emails as $key => $email )
				{
				if( $email != "" )
					{
					$public = 0;
					if( $public_emails[$key] != "" )
						$public = 1;
					$stmt = $db->stmt_init();
					$stmt->prepare( "INSERT INTO user_emails (user, email, public) VALUES (?, ?, ?)" );
					$stmt->bind_param( "ssi", $userID, $email, $public );
					$stmt->execute();
					$stmt->close();
					}
				}
			// Wipe out all public rooms and worlds
			update_db( $db, "UPDATE room_members SET public = 0 WHERE user = ?", "s", $userID );
			update_db( $db, "UPDATE user_worlds SET public = 0 WHERE user = ?", "s", $userID );
			// Add all public rooms and worlds
			updateInterestsPublic( $db, $_POST );
			print( "<p class=\"info\">Your information is updated.</p>\n" );
			}
		}
	}



$stmt = $db->stmt_init();
$sql = "SELECT visible_name, real_name, profile_public, about FROM users WHERE username = ?";
if( $stmt->prepare( $sql ) )
	{
	$stmt->bind_param( "s", $_SESSION["logged_in"] );
	$stmt->execute();
	$stmt->bind_result( $visible_name, $real_name, $profile_public, $about );
	$stmt->fetch();
	$stmt->close();
	}

function getList( $db, $type, $userID )
	{
	$stmt = $db->stmt_init();
	$sql = "SELECT $type FROM user_$type" . "s WHERE user = ?";
	if( $stmt->prepare( $sql ) )
		{
		$results = array();
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $result );
		while( $stmt->fetch() )
			{
			array_push( $results, $result );
			}
		}
		return $results;
	}
$emails = array();
$emails = getList( $db, "email", $userID );
$phones = array();
$phones = getList( $db, "phone", $userID );


?>
<h1>Account</h1>
<form action="account.php" method="post">
<h2>Names</h2>
<table border="0">
	<tr>
		<td class="label">Username</td>
		<td><?php echo $_SESSION["logged_in"]; ?></td>
	</tr>
	<tr>
		<td class="label">Visible Name</td>
		<td><input type="text" name="visible-name" value="<?php echo $visible_name; ?>"/></td>
	</tr>
	<tr>
		<td class="label">Real Name</td>
		<td><input type="text" name="real-name" value="<?php echo $real_name; ?>"/></td>
		<td>Never visible.</td>
	</tr>
	<tr>
		<td class="label">New Password</td>
		<td><input type="text" id="new-password" name="new-password" onkeyup="javascript:passwordHint('new-password','password-hint');"/></td>
		<td>Entering a password here will change your password.</td>
	</tr>
	<tr>
		<td></td>
		<td colspan="2" id="password-hint">Passwords must have at least 8 characters,<br />
		   and must contain at least 1 upper-case<br />
		   character, at least 1 number, and at least 1<br />
		   symbol.</td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" value="Update" /></td>
	</tr>
</table>
<br />

<?php
function getCheckboxCell( $id, $db, $item, $type )
	{
	global $userID;
	//return '';
	$result = "<td><input type=\"checkbox\" id=\"$id\" name=\"public-$type" . "[]\" ";
	if( $type != ""  &&  $item != "" )
		{
		// Pull from type
		$singular_type = substr( $type, 0, strlen($type) - 1 );
		$public = get_db_value( $db, "SELECT public FROM user_$type WHERE user = ? AND $singular_type = ?", "ss", $userID, $item );
		if( $public )
			$result .= "checked=\"checked\" ";
		}
	$result .= "/> <label for=\"$id\">Public</label></td>";
	return $result;
	}


function getCarriersBox( $db, $item )
	{
	global $userID;
	$this_carrier_id = "";
	if( $item != "" )
		$this_carrier_id = get_db_value( $db, "SELECT carrier FROM user_phones WHERE user = ? AND phone = ?", "ss", $userID, $item );
	$text = "<td><select name=\"carriers[]\">\n";
	$stmt = $db->stmt_init();
	$stmt->prepare( "SELECT id, name FROM carriers ORDER BY name" );
	$stmt->execute();
	$stmt->bind_result( $carrier_id, $carrier_name );
	while( $stmt->fetch() )
		{
		$carrier_name = htmlentities( $carrier_name );
		$text .= "\t<option ";
		if( $this_carrier_id == $carrier_id )
			$text .= "selected ";
		$text .= "value=\"$carrier_id\">$carrier_name</option>\n";
		}
	$text .= "</select></td>\n";
	return $text;
	}

function printSet( $db, $set, $input_name )
	{
	print( "<table border='0'>\n" );
	foreach( $set as $item )
		{
		print( "<tr><td><input type=\"text\" name=\"$input_name" . "[]\" value=\"$item\" size=\"30\" /></td>" );
		if( $input_name == "phones" )
			print( getCarriersBox( $db, $item ) );
		print( getCheckboxCell( "$input_name-public", $db, $item, $input_name ) );
		print( "</tr>\n" );
		}
	print( "<tr><td><input type=\"text\" name=\"$input_name" . "[]\" size=\"30\" /></td>" );
	if( $input_name == "phones" )
		print( getCarriersBox( $db, "" ) );
	print( "</tr>\n" );
	print( "</table>\n" );
	}

print( "<h2>Email Addresses</h2>\n" );
printSet( $db, $emails, "emails" );
print( "<h2>Phone Numbers</h2>\n" );
printSet( $db, $phones, "phones" );
?>

<br />
<input type="checkbox" name="profile-public" id="profile-public" <?php
if( $profile_public == 1 )
	print( "checked=\"checked\" " );
?>/> <label for="profile-public">Make my profile publicly visible on the web</label><br />
<br />
<input type="submit" value="Update" />
<br />
<br />

<h2>About</h2>
<textarea name="about" id="about-user" style="width: 510px; height: 125px" onkeyup="javascript:updatePreview('about-user','about-preview');"><?php echo $about; ?></textarea>
<div id="about-preview" class="post-preview"></div>

<br />
<input type="submit" value="Update" />

<h2>Public Interests</h2>
<p>These are all the Worlds and Rooms to which you've subscribed. If checked, that World/Room will be displayed on <a href="profile.php?i=<?php echo $userID; ?>">your profile page</a>.</p>
<?php
function displayInterest( $db, $interest_name, $sql, $userID )
	{
	print( "<div style=\"float: left; margin: 0px 25px 10px 0px;\">\n" .
	       "<h2>$interest_name</h2>\n" );
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $userID );
	$stmt->execute();
	$stmt->bind_result( $id, $name, $public );
	while( $stmt->fetch() )
		{
		print( "<input type=\"checkbox\" name=\"$interest_name-$id\" " );
		if( $public == 1 )
			print( " checked=\"checked\" " );
		print( "/> $name<br />\n" );
		}
	print( "</div>\n" );
	}

displayInterest( $db, "Worlds", "SELECT user_worlds.id, worlds.display_name, user_worlds.public FROM worlds JOIN user_worlds ON user_worlds.world = worlds.id AND user_worlds.user = ? ORDER BY display_name", $userID );
displayInterest( $db, "Rooms", "SELECT room_members.id, rooms.name, room_members.public FROM rooms JOIN room_members ON room_members.room = rooms.id AND room_members.user = ? ORDER BY rooms.name", $userID );
?>
<br style="clear: both" />
<input type="submit" value="Update" />
</form>

<h2>Profile Image</h2>
<img src="assets/images/avatars/<?php echo $userID; ?>" id="profile-image" style="max-width: 500px;" alt="Profile" />
<br />
<form action="account.php" method="post" enctype="multipart/form-data">
<label for="profile-picture">Select a new profile image:</label>
<input type="file" name="file" id="file" />
<input type="submit" name="upload" value="Upload" /><br />
JPG, GIF, or PNG only, please.
</form>
<br />
<br />

<br />
<br />

<button>Import or Export Data</button> Not working yet.
<br />
<button>Delete Account</button> Not working yet.
<script type="text/javascript">
updatePreview('about-user','about-preview');
</script>
<?php

require_once( "footer.php" );
?>
