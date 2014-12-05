<?php
// Process an actual reset request that contains a new password
if( isset( $_POST["r"] )  &&  isset( $_POST["new-password"] ) )
	{
	require_once( "database.php" );
	$sql = "SELECT user, created FROM account_recovery WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $_POST["r"] );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $user_id, $created );
	if( $stmt->num_rows > 0 )
		{
		$stmt->fetch();
		$stmt->close();
		if( (time() - $created) < 3600 )
			{
			$stmt = $db->stmt_init();
			$sql = "UPDATE users SET password = ? WHERE id = ?";
			$stmt->prepare( $sql );
			$stmt->bind_param( "ss", crypt( $_POST["new-password"], $crypt_salt ), $user_id );
			$stmt->execute();
			$stmt->close();
			header( "Location: index.php\n\n" );
			exit( 0 );
			}
		}
	}
$page_title = "Recover Account";
require_once( "header.php" );

?>
<h1>Recover Password</h1>

<?php
function send_recovery_message( $db, $type )
	{
	require_once( "database.php" );
	// Get user ID
	$sql = "SELECT id FROM users WHERE username = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $_POST["username"] );
	$stmt->execute();
	$stmt->store_result();
	$stmt->bind_result( $userID );
	if( $stmt->num_rows == 0 )
		{
		?>
		<p class="error">That username does not exist in the system.</p>
		<?php
		return;
		}
	$stmt->fetch();
	$stmt->close();
	// Generate account_recovery record
	$sql = "INSERT INTO account_recovery (id, created, user) VALUES (UUID(), ?, ?)";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "is", time(), $userID );
	$stmt->execute();
	$recovery_id = get_db_value( $db, "SELECT MAX(id) FROM account_recovery" );
	if( $type == "phone" )
		{
		// For each phone number in account,
		$sql = "SELECT user_phones.phone, carriers.sms_domain FROM user_phones, carriers WHERE user_phones.user = ? AND user_phones.carrier = carriers.id";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $phone_number, $carrier_domain );
		$count = 0;
		while( $stmt->fetch() )
			{
			// Send SMS with link to recover.php?r=<ID>
			$bad_characters = array( "-", "+", "(", ")" );
			$phone_number = str_replace( $bad_characters, "", $phone_number );
			mail( $phone_number . "@" . $carrier_domain, "Hai Account Info", "To reset your Hai password, go to http://hai.social/recover.php?r=$recovery_id" );
			$count++;
			}
		print( "<p>$count password reset SMS message(s) sent." );
		}
	else
		{ // Email
		// For each email in account,
		$sql = "SELECT user_emails.email FROM user_emails WHERE user_emails.user = ?";
		$stmt = $db->stmt_init();
		$stmt->prepare( $sql );
		$stmt->bind_param( "s", $userID );
		$stmt->execute();
		$stmt->bind_result( $email_address );
		$headers = "From: brent@brentnewhall.com\r\n" .
		           "Reply-To: brent@brentnewhall.com\r\n" .
		           "Return-Path: brent@brentnewhall.com\r\n" .
		           "X-Mailer: PHP/" . phpversion();
		$message = "Someone submitted a password reset for you at Hai.\r\n\r\nTo reset your Hai password, go to http://hai.social/recover.php?r=$recovery_id\r\n\r\nThis link will expire in 1 hour.\r\n";
		$message = wordwrap( $message, 70, "\r\n" );
		$count = 0;
		while( $stmt->fetch() )
			{
			// Send email with link to recover.php?r=<ID>
			mail( $email_address, "Hai Account Info", $message, $headers, "-f brent@brentnewhall.com" );
			$count++;
			}
		print( "<p>$count password reset email(s) sent." );
		}
	}

if( isset( $_GET["r"] ) )
	{
	// Find that in the table
	require_once( "database.php" );
	$sql = "SELECT created FROM account_recovery WHERE id = ?";
	$stmt = $db->stmt_init();
	$stmt->prepare( $sql );
	$stmt->bind_param( "s", $_GET["r"] );
	$stmt->execute();
	$stmt->store_result();
	if( $stmt->num_rows == 0 )
		{
		print( "<p class='error'>Invalid recovery ID.</p>\n" );
		}
	else
		{
		$stmt->bind_result( $created );
		$stmt->fetch();
		$stmt->close();
		if( (time() - $created) > 3600 )
			{
			// Too old. Display error and destroy the record.
			print( "<p class='error'>Invalid recovery ID.</p>\n" );
			$stmt->prepare( "DELETE FROM account_recovery WHERE id = ?" );
			$stmt->bind_param( "s", $_GET["r"] );
			$stmt->execute();
			$stmt->close();
			}
		else
			{
			?>
			<p>Okay, enter your new password in the field below.</p>
			<form action="recover.php" method="post"><input type="hidden" name="r" value="<?php echo $_GET["r"]; ?>" /><input type="text" name="new-password" size="20" /><input type="submit" name="set-new-password" value="Update" /></form>
			<?php
			}
		}
	}
if( isset( $_GET["action"] )  &&  $_GET["action"] == "request-sms" )
	{
	?>
	<h2>Send SMS</h2>
	<p>Okay, enter your username in the field below. An SMS will be sent to the <?php echo $phones; ?> you entered in your Hai account.</p>
	<form action="recover.php" method="post"><input type="text" name="username" size="20" /><input type="submit" name="action" value="Send SMS" /></form>
	<p>The SMS you receive will contain a link to a password recovery page. The link will expire after 1 hour. Standard SMS data charges may apply.</p>
	<?php
	}
elseif( isset( $_GET["action"] )  &&  $_GET["action"] == "request-email" )
	{
	?>
	<h2>Send Email</h2>
	<p>Okay, enter your username in the field below. An email will be sent to the <?php echo $emails; ?> you entered in your Hai account.</p>
	<form action="recover.php" method="post"><input type="text" name="username" size="20" /><input type="submit" name="action" value="Send Email" /></form>
	<p>The email you receive will contain a link to a password recovery page. The link will expire after 1 hour.</p>
	<?php
	}
elseif( isset( $_POST["action"] )  &&  $_POST["action"] == "Send SMS" )
	{
	send_recovery_message( $db, "phone" );
	}
elseif( isset( $_POST["action"] )  &&  $_POST["action"] == "Send Email" )
	{
	send_recovery_message( $db, "email" );
	}
else
	{

?>

<p>There are 3 ways you can recover your password, from most secure to least secure:</p>
<ol>
<li> <a href="recover.php?action=list-security-questions">Answer your security questions</a>, if you filled those out.</li>
<li> <a href="recover.php?action=request-sms">Send an SMS</a> to every phone on your account.</li>
<li> <a href="recover.php?action=request-email">Send an email</a> to every email address on your account.</li>
</ol>
<p>You will need your username. If you can't remember that, then unfortunately you're out of options.</p>
<?php
	}

require_once( "footer.php" );
?>
