<?php
// Login and account-related functions

function requireLogin( $db, $db2 )
	{
	if( ! isset( $_SESSION["logged_in"] ) )
		{
		/* if( isset( $_GET["error"] ) )
			printError( $_GET["error"] ); */
		$username = "";
		if( isset( $_GET["username"] ) )
			$username = $_GET["username"];
		$password = "";
		if( isset( $_GET["password"] ) )
			$password = $_GET["password"];
?>
<h2>Log in</h2>
<form action="login.php" method="post">
<table border="0" style="margin: auto; padding-top: 25px">
	<tr>
		<td class="label">Username</td>
		<td><input type="text" name="username" autofocus="autofucus" value="<?php echo $username; ?>"/></td>
	</tr>
	<tr>
		<td class="label">Password</td>
		<td><input type="text" id="login-password" name="password" value="<?php echo $password; ?>" onkeyup="javascript:passwordHint('login-password','password-hint');"/></td>
		<td><input type="checkbox" id="visible-checkbox" checked="yes" onclick="javascript:hidePasswordField('visible-checkbox','login-password');" /> <label for="visible-checkbox">Display password</label></td>
	</tr>
	<tr>
		<td></td>
		<td colspan="2" id="ppassword-hint"></td>
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
		<p id="password-hint">Passwords must have at least 8 characters,<br />
		   and must contain at least 1 upper-case<br />
		   character, at least 1 number, and at least 1<br />
		   symbol.</p>
		</td>
	</tr>
</table>
</form>
<p><a href="recover.php">Recover your password</a></p>

<div style="float: left; width: 300px">
<h2>Worlds</h2>
<ul>
<?php
$stmt = $db->stmt_init();
$stmt->prepare( "SELECT id, display_name FROM worlds ORDER BY display_name" );
$stmt->execute();
$stmt->bind_result( $id, $name );
while( $stmt->fetch() )
	{
	print( "<li> <a href=\"world.php?i=$id\">$name</a></li>\n" );
	}
?>
</ul>
</div>

<div style="float: left; width: 300px">
<h2>Rooms</h2>
<ul>
<?php
$stmt = $db->stmt_init();
$stmt->prepare( "SELECT id, name FROM rooms ORDER BY name" );
$stmt->execute();
$stmt->bind_result( $id, $name );
while( $stmt->fetch() )
	{
	print( "<li> <a href=\"room.php?i=$id\">$name</a></li>\n" );
	}
?>
</ul>
</div>

<br style="clear: both" />
<h2>Recent Public Posts</h2>
<?php
		$sql = getStandardSQL( "Everything" );
		displayPostsV2( $db, $db2, $sql, "", 10, "none" );
		require_once( "footer.php" );
		exit( 0 );
		} // end if logged_in session variable unset
	} // end requireLogin()



function getLogin()
	{
	if( ! isset( $_SESSION["logged_in"] ) )
		{
?>
<div id="login-box">
<form action="login.php" method="post">
<table border="0" style="margin: auto; padding: 0px" cellpadding="0" cellspacing="0">
<tr>
<td class="label">Username</td><td><input type="text" name="username" autofocus="autofucus" size="8" value="<?php echo $username; ?>"/></tr>
<td class="label">Password</td><td><input type="text" id="login-password" name="password" size="8" value="<?php echo $password; ?>" onkeyup="javascript:passwordHint('login-password','password-hint');"/></tr>
</table>
<input type="checkbox" id="visible-checkbox" checked="yes" onclick="javascript:hidePasswordField('visible-checkbox','login-password');" /> <label for="visible-checkbox">Display password</label><br />
<input type="submit" name="submit" value="Log in" /><br />
<br />
<input type="submit" name="submit" value="Create account" />
</form>
<div id="password-hint"></div>
</form>
<p class="recover-password"><a href="recover.php">Recover your password</a></p>
</div>
<?php
		}
	}




function testPassword( $password )
	{
	if( ( ! preg_match( "/[A-Z]/", $password ) )  ||
	    ( ! preg_match( "/[0-9]/", $password ) )  ||
	    ( ! preg_match( "/[!@#$%^&\*\(\)\-_=+\[{\]}\\|;:'\",<\.>\/\?]/", $password ) ) )
		return 0;
	if( strlen($password) < 8 )
		return 0;
	return 1;
	}




function createAccount( $db, $username, $password )
	{
	global $crypt_salt;
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
				$password = crypt( $password, $crypt_salt );
				$stmt->bind_param( "ssss", $username, $username, $password, time() );
				$stmt->execute();
				$stmt->close();
				$new_user_id = get_db_value( $db, "SELECT id FROM users ORDER BY created DESC LIMIT 1" );
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
				header( "Location: account.php\n\n" );
				exit( 0 );
				}
			}
		}
	} // end createAccount()



function deleteAccount( $db, $user_id )
	{
	// Delete posts and every related related post table.
	$post_ids = array();
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT id FROM posts WHERE author = ?" ) )
		{
		$stmt->bind_param( "s", $user_id );
		$stmt->execute();
		$stmt->bind_result( $post_id );
		while( $stmt->fetch() )
			{
			array_push( $post_ids, $post_id );
			}
		$stmt->close();
		foreach( $post_ids as $post_id )
			{
			update_db( $db, "DELETE FROM post_groups WHERE post = ?", "s", $post_id );
			update_db( $db, "DELETE FROM post_history WHERE post = ?", "s", $post_id );
			update_db( $db, "DELETE FROM post_locks WHERE post = ?", "s", $post_id );
			update_db( $db, "DELETE FROM posts WHERE id = ?", "s", $post_id );
			}
		}
	// Delete comments.
	update_db( $db, "DELETE FROM comments WHERE author = ?", "s", $user_id );
	// Delete images and video.
	$media_ids = array();
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT id FROM user_media WHERE user = ?" ) )
		{
		$stmt->bind_param( "s", $user_id );
		$stmt->execute();
		$stmt->bind_result( $media_id );
		while( $stmt->fetch() )
			{
			array_push( $media_ids, $media_id );
			}
		$stmt->close();
		foreach( $media_ids as $media_id )
			{
			$filename = get_db_value( $db, "SELECT filename FROM user_media WHERE id = ?", "s", $media_id );
			// Delete file
			unlink( "assets/images/uploads/$filename" );
			// Delete media record
			update_db( $db, "DELETE FROM user_media WHERE id = ?", "s", $media_id );
			}
		}
	// Delete profile picture
	if( file_exists( "assets/images/avatars/$user_id" ) )
		unlink( "assets/images/avatars/$user_id" );
	// Delete account itself.
	update_db( $db, "DELETE FROM users WHERE id = ?", "s", $user_id );
	}




function exportAccount( $db, $userID )
	{
	print "<account>\n";
	print getAccountBasics( $db, $userID );
	exportAccountFiles( $db, $userID );
	print "</account>\n";
	}



function getAccountBasics( $db, $userID )
	{
	$results = "\<?xml version=\"1.0\" encoding=\"utf-8\" ?\>\n";
	$results = "\t<user>\n";
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT username, visible_name, real_name, created, paid, profile_public, about FROM users WHERE id = ?" ) )
		{
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $username, $visible_name, $real_name, $created, $paid, $profile_public, $about );
		$stmt->fetch();
		$results .= "\t\t<id>$userID</id>\n" .
		        "\t\t<username>$username</username>\n" .
		        "\t\t<visible-name>$visible_name</visible-name>\n" .
		        "\t\t<real-name>$real_name</real-name>\n" .
		        "\t\t<created>$created</created>\n" .
		        "\t\t<paid>$paid</paid>\n" .
		        "\t\t<profile-public>$profile_public</profile-public>\n" .
		        "\t\t<about>$about</about>\n";
		$stmt->close();
		}
	// Emails
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT email FROM user_emails WHERE user = ?" ) )
		{
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $email );
		while( $stmt->fetch() )
			$results .= "\t\t<email>$email</email>\n";
		$stmt->close();
		}
	// Phones
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT phone FROM user_phones WHERE user = ?" ) )
		{
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $phone );
		while( $stmt->fetch() )
			$results .= "\t\t<phone>$phone</phone>\n";
		$stmt->close();
		}
	$results .= "\t</user>\n";
	// Posts
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT id, created, content, parent, public, editable FROM posts WHERE author = ?" ) )
		{
		$results .= "\t<posts>\n";
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $post_id, $created, $content, $parent, $public, $editable );
		while( $stmt->fetch() )
			$results .= "\t\t<post>\n" .
			        "\t\t\t<id>$post_id</id>\n" .
			        "\t\t\t<created>$created</created>\n" .
			        "\t\t\t<content>$content</content>\n" .
			        "\t\t\t<parent>$parent</parent>\n" .
			        "\t\t\t<public>$public</public>\n" .
			        "\t\t\t<editable>$editable</editable>\n" .
					"\t\t</post>\n";
		$stmt->close();
		$results .= "\t</posts>\n";
		}
	// Comments
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT id, created, post, content FROM comments WHERE author = ?" ) )
		{
		$results .= "\t<comments>\n";
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $comment_id, $created, $post, $content );
		while( $stmt->fetch() )
			$results .= "\t\t<comment>\n" .
			        "\t\t\t<id>$comment_id</id>\n" .
			        "\t\t\t<created>$created</created>\n" .
			        "\t\t\t<post>$post</post>\n" .
			        "\t\t\t<content>$content</content>\n" .
					"\t\t</comment>\n";
		$stmt->close();
		$results .= "\t</comments>\n";
		}
	return $results;
	}



function exportAccountFiles( $db, $userID )
	{
	// Images and video
	$stmt = $db->stmt_init();
	if( $stmt->prepare( "SELECT id, created, filename, type FROM user_media WHERE user = ? ORDER BY type" ) )
		{
		print "\t<media>\n";
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $media_id, $created, $filename, $type );
		while( $stmt->fetch() )
			{
			if( $type == "image" )
				$folder = "images";
			else
				$folder = "video";
			$file = "assets/$folder/uploads/$filename";
			if( file_exists( $file ) )
				{
				$data = base64_encode( file_get_contents( $file ) );
				if( $type == "image" )
					print "\t\t<image>\n" .
							"\t\t\t<id>$media_id</id>\n" .
							"\t\t\t<created>$created</created>\n" .
							"\t\t\t<filename>$filename</filename>\n" .
							"\t\t\t<file>$data</file>\n" .
							"\t\t</image>\n";
				else
					print "\t\t<video>\n" .
							"\t\t\t<id>$media_id</id>\n" .
							"\t\t\t<created>$created</created>\n" .
							"\t\t\t<filename>$filename</filename>\n" .
							"\t\t\t<file>$data</file>\n" .
							"\t\t</video>\n";
				}
			}
		$stmt->close();
		print "\t</media>\n";
		}
	}




function importAccount( $db, $userID, $file_pointer )
	{
	$data = "";
	// Read file data
	while( ! feof( $file_pointer ) )
		{
		$data .= fgets( $file_pointer, 4096 );
		}
	// Read data into XML data structure and get variables.
	$xml = new SimpleXMLElement( $data );
	$user_id = $xml->user[0]->id;
	$username = $xml->user[0]->username;
	$visible_name = $xml->user[0]->visible_name;
	$real_name = $xml->user[0]->real_name;
	$created = $xml->user[0]->created;
	$paid = $xml->user[0]->paid;
	$profile_public = $xml->user[0]->profile_public;
	$about = $xml->user[0]->about;
	// Insert into database.
	update_db( $db, "UPDATE users SET username = ?, visible_name = ?, real_name = ?, created = ?, paid = ?, profile_public = ?, about = ? WHERE id = ?", "sssiiiss", $username, $visible_name, $real_name, $created, $paid, $profile_public, $about, $userID );
	foreach( $xml->user[0]->email as $email_address )
		{
		update_db( $db, "INSERT INTO user_emails (user, email, public) VALUES (?, ?, 0)", "ss", $userID, $email_address );
		}
	foreach( $xml->user[0]->phone as $phone_number )
		{
		update_db( $db, "INSERT INTO user_phones (user, phone, public) VALUES (?, ?, 0)", "ss", $userID, $phone_number );
		}
	// Insert posts
	foreach( $xml->posts->post as $post )
		{
		$post_id = $post->id;
		$created = $post->created;
		$content = $post->content;
		$parent = $post->parent;
		$public = $post->public;
		$editable = $post->editable;
		update_db( $db, "INSERT INTO posts (id, created, author, content, parent, public, editable) VALUES (?, ?, ?, ?, ?, ?, ?)", "sisssii", $post_id, $created, $userID, $content, $parent, $public, $editable );
		}
	// Insert comments
	foreach( $xml->comments->comment as $comment )
		{
		$comment_id = $comment->id;
		$created = $comment->created;
		$post_id = $comment->post;
		$content = $comment->content;
		update_db( $db, "INSERT INTO comments (id, created, author, post, content) VALUES (?, ?, ?, ?, ?)", "sisss", $comment_id, $created, $userID, $post_id, $content );
		}
	// Insert images
	foreach( $xml->media->image as $image )
		{
		$image_id = $image->id;
		$created = $image->created;
		$filename = $image->filename;
		$file_contents = base64_decode( $image->file );
		update_db( $db, "INSERT INTO user_media (id, created, user, filename, type) VALUES (?, ?, ?, ?, 'image')", "siss", $media_id, $created, $userID, $filename );
		$fp = fopen( "assets/images/uploads/$filename", "w" );
		if( $fp )
			{
			fputs( $fp, $file_contents );
			fclose( $fp );
			}
		}
	// Insert video
	foreach( $xml->media->video as $video )
		{
		$video_id = $video->id;
		$created = $video->created;
		$filename = $video->filename;
		$file_contents = base64_decode( $video->file );
		update_db( $db, "INSERT INTO user_media (id, created, user, filename, type) VALUES (?, ?, ?, ?, 'video')", "siss", $media_id, $created, $userID, $filename );
		$fp = fopen( "assets/video/uploads/$filename", "w" );
		if( $fp )
			{
			fputs( $fp, $file_contents );
			fclose( $fp );
			}
		}
	}
?>
