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
		displayPosts( $db, $db2, $sql, "", 10, "none" );
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
?>
