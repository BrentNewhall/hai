<?php

require_once( "database.php" );

$page_title = "Create Account";
require_once( "header.php" );

print( "<h1>Create Account</h1>" );

$words = array();
$fp = fopen( "words.txt", "r" );
while( ! feof( $fp ) )
	{
	array_push( $words, trim( fgets( $fp, 128 ) ) );
	}
fclose( $fp );
function getLongPassword( $words )
	{
	$password = "";
	for( $i = 0; $i < 4; $i ++ )
		{
		$num_words = count( $words );
		$password .= $words[array_rand($words)] . " ";
		}
	return trim( $password );
	}

?>

<p>Enter your desired account information below:</p>
<form action="login.php" method="post">
<table border="0" style="margin: auto;">
	<tr>
		<td class="label">Username</td>
		<td><input type="text" id="login-username" name="username" autofocus="autofocus" onblur="javascript:document.getElementById('username-valid').innerHTML='Checking...';checkUsername('login-username','username-valid');" value="<?php echo $username; ?>"/></td>
		<td id="username-valid">Not your email address</td>
	</tr>
	<tr>
		<td class="label">Password</td>
		<td><input type="text" id="login-password" name="password" value="<?php echo $password; ?>" onkeyup="javascript:passwordHint('login-password','password-hint');"/></td>
		<td><input type="checkbox" id="visible-checkbox" checked="yes" onclick="javascript:hidePasswordField('visible-checkbox','login-password');" /> <label for="visible-checkbox">Display password</label></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" name="submit" value="Create account" /></td>
	</tr>
	<tr>
		<td></td>
		<td colspan="2" style="font-size: 10pt; padding-top: 50px;">
		<p id="password-hint">Passwords must have at least 10 characters,<br />
		   and must contain at least 1 upper-case<br />
		   character, at least 1 number, and at least 1<br />
		   symbol.</p>
		</td>
	<tr>
		<td></td>
		<td colspan="2" style="font-size: 10pt; padding-top: 50px;">
		<p>A good password is long. Here are some long ones:</p>
<pre>
<?php
for( $i = 0; $i < 4; $i++ )
	print getLongPassword( $words ) . "\n";
?>
</pre>
		</td>
	</tr>
</table>
</form>

<?php
require_once( "footer.php" );
?>
