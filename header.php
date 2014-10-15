<?php
require_once( "functions.php" );
?>
<html>
<head>
<meta charset="UTF-8" />
<title><?php
if( isset( $page_title ) )
	print( "$page_title - " );
?>Hai</title>
<link rel="stylesheet" type="text/css" href="assets/css/screen.css" media="screen" />
<link rel="stylesheet" type="text/css" href="assets/css/print.css" media="print" />
<link rel="stylesheet" type="text/css" href="assets/css/mobile.css" media="only screen and (max-device-width: 480px)" />
<link href='http://fonts.googleapis.com/css?family=Oxygen:300,700&subset=latin,latin-ext' rel='stylesheet' type='text/css' />
<script type="text/javascript" src="assets/js/main.js"></script>
</head>
<body>

<?php
print( "<div style=\"position: fixed; bottom: 5px; right: 5px; color: white; font-size: 16pt;\">" . PROD_DEV . "</div>" );
?>
<div id="logo">
<a href="index.php">Hai</a>
</div>

<div id="header">
<a href="about.php">About</a> &nbsp;
</div>

<?php
/*
print( "<div style=\"margin: auto; width: 800px; background-color: white; padding: 10px; font-size: 14pt;\">\n" );
print( "<h1>One sec; Hai is being fixed</h1>\n" . "<p>I have to align six hydrocoptic marzul vanes so fitted to the ambaphascient lunar wain shaft that side fumbling can be effectively prevented.</p><p>Be back shortly.</p>\n" );
print( "<center><img src=\"assets/images/my-little-pony-friendship-is-magic-brony-there-i-fixed-it.gif\" align=\"center\" width=\"500\" height=\"332\" /></center>\n" );
print( "</div>\n" ); require_once( "footer.php" ); exit( 0 );
*/
?>

<a name="top"></a>
<div id="body-container">
<?php
if( isset( $_GET["error"] ) )
	printError( $_GET["error"] );
?>
