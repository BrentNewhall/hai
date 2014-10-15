<?php
// Connect to database.
DEFINE( "PROD_DEV", "PROD" );
require_once( '../hai_db.cfg' );
$db = new mysqli( DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME );
if( $db->connect_error )
	die( "<html><body>Could not connect to the database. Please <a href=\"mailto:brent@brentnewhall.com\">email brent@brentnewhall.com</a>.</body></html>\n" );
$db2 = new mysqli( DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME );

$admin = 'Admin';

session_start();


function delete_avatar_images( $path )
	{
	$dir = opendir( $path );
	while( $file = readdir( $dir ) )
		{
		if( substr( $file, -4 ) == ".png" )
			unlink( $path . "/" . $file );
		}
	closedir( $dir );
	}

function add_avatar_image_to_db( $db, $user_id, $filename )
	{
	if( isset( $filename )  &&
	    filesize($filename) > 0 )
		{
		/* $fp = fopen( $filename, 'r' );
		$data = fread( $fp, filesize( $filename ) );
		fclose( $fp );
		$encoded_data = base64_encode( $data );
		
		$stmt = $db->prepare( "INSERT INTO user_avatars (user, data) VALUES (?, ?)" );
		$stmt->bind_param( "is", $user_id, $encoded_data );
		$stmt->execute();
		$stmt->close();
		*/
		copy( $filename, "assets/images/avatars/" . $user_id );
		}
	}

// Re-build database from scratch.
function rebuild_db( $db, $admin, $crypt_salt )
	{
	//delete_avatar_images( "assets/images/avatars" );
	$start_time = 1412898245; // A convenient time for the beginning of time. All other times are calculated off this.
	$result = $db->query( "DROP TABLE users" );
	$result = $db->query( 'CREATE TABLE users (id CHAR(36) NOT NULL PRIMARY KEY, username TEXT NOT NULL, visible_name TEXT NOT NULL, password TEXT NOT NULL, real_name TEXT, created INTEGER NOT NULL, paid BOOLEAN NOT NULL, profile_public BOOLEAN NOT NULL, admin BOOLEAN NOT NULL)' );
	$result = $db->query( 'INSERT INTO users (id, username, password, real_name, visible_name, created, paid, profile_public, admin) VALUES (UUID(), "' . $admin . '", "' . crypt( "bd8FG09ast", $crypt_salt ) . '", "Brent P. Newhall", "Brent P. Newhall", "' . $start_time . '", 1, 1, 1)' );
	$userIdAdmin = get_db_value( $db, "SELECT MAX(id) FROM users" );
	add_avatar_image_to_db( $db, $userIdAdmin, "assets/images/avatar1.png" );
	$result = $db->query( 'INSERT INTO users (id, username, visible_name, password, created, paid, profile_public, admin) VALUES (UUID(), "JamesKirk", "James Kirk", "' . crypt( "blah", $crypt_salt ) . '", "' . ($start_time + 100) . '", 1, 1, 0)' );
	$userIdKirk = get_db_value( $db, "SELECT MAX(id) FROM users" );
	add_avatar_image_to_db( $db, $userIdKirk, "assets/images/avatar4.png" );
	$result = $db->query( 'INSERT INTO users (id, username, visible_name, password, created, paid, profile_public, admin) VALUES (UUID(), "CaptPicard", "Jean-Luc Picard", "' . crypt( "blah", $crypt_salt ) . '", "' . ($start_time + 200) . '", 0, 0, 0)' );
	$userIdPicard = get_db_value( $db, "SELECT MAX(id) FROM users" );
	add_avatar_image_to_db( $db, $userIdPicard, "assets/images/avatar2.png" );
	$result = $db->query( 'INSERT INTO users (id, username, visible_name, password, created, paid, profile_public, admin) VALUES (UUID(), "Uhura", "Uhura", "' . crypt( "blah", $crypt_salt ) . '", "' . ($start_time + 300) . '", 0, 0, 0)' );
	$userIdUhura = get_db_value( $db, "SELECT MAX(id) FROM users" );
	add_avatar_image_to_db( $db, $userIdUhura, "assets/images/avatar3.png" );
	$result = $db->query( "DROP TABLE carriers" );
	$result = $db->query( 'CREATE TABLE carriers (id CHAR(36) NOT NULL PRIMARY KEY, name TEXT, sms_domain TEXT)' );
	$a = getCarriers();
	foreach( array_keys($a) as $carrier_name )
		{
		$result = $db->query( 'INSERT INTO carriers (id, name, sms_domain) VALUES (UUID(), "' . $carrier_name . '", "' . $a[$carrier_name] . '")' );
		}
	$att_carrier_id = get_db_value( $db, "SELECT id FROM carriers WHERE name = 'AT&T'" );
	$result = $db->query( "DROP TABLE user_emails" );
	$result = $db->query( 'CREATE TABLE user_emails (user CHAR(36) NOT NULL, email TEXT, public BOOLEAN NOT NULL)' );
	$result = $db->query( 'INSERT INTO user_emails (user, email, public) VALUES ("' . $userIdAdmin . '", "brent@brentnewhall.com", 1)' );
	$result = $db->query( 'INSERT INTO user_emails (user, email, public) VALUES ("' . $userIdKirk . '", "brentnewhall+kirk@gmail.com", 1)' );
	$result = $db->query( "DROP TABLE user_phones" );
	$result = $db->query( 'CREATE TABLE user_phones (user CHAR(36) NOT NULL, phone TEXT, carrier CHAR(36) NOT NULL, public BOOLEAN NOT NULL)' );
	$result = $db->query( 'INSERT INTO user_phones (user, phone, carrier, public) VALUES ("' . $userIdAdmin . '", "7034701289", "' . $att_carrier_id . '", 1)' );
	$result = $db->query( "DROP TABLE user_groups" );
	$result = $db->query( 'CREATE TABLE user_groups (id CHAR(36) NOT NULL PRIMARY KEY, user CHAR(36) NOT NULL, name TEXT)' );
	$result = $db->query( 'INSERT INTO user_groups (id, user, name) VALUES (UUID(), "' . $userIdAdmin . '", "Friends")' );
	$user_group_id = get_db_value( $db, "SELECT MAX(id) FROM user_groups" );
	$result = $db->query( "DROP TABLE user_group_members" );
	$result = $db->query( 'CREATE TABLE user_group_members (usergroup CHAR(36) NOT NULL, user CHAR(36) NOT NULL)' );
	$result = $db->query( 'INSERT INTO user_group_members (usergroup, user) VALUES ("' . $user_group_id . '", "' . $userIdKirk . '")' );
	$result = $db->query( "DROP TABLE blocks" );
	$result = $db->query( 'CREATE TABLE blocks (id CHAR(36) NOT NULL PRIMARY KEY, blocker CHAR(36) NOT NULL, troll CHAR(36) NOT NULL)' );
	$result = $db->query( "DROP TABLE account_recovery" );
	$result = $db->query( 'CREATE TABLE account_recovery (id CHAR(36) NOT NULL PRIMARY KEY, created INT NOT NULL, user CHAR(36) NOT NULL)' );
	$result = $db->query( "DROP TABLE posts" );
	$result = $db->query( 'CREATE TABLE posts (id CHAR(36) NOT NULL PRIMARY KEY, created INT NOT NULL, author CHAR(36) NOT NULL, content TEXT NOT NULL, parent CHAR(36), public BOOLEAN)' );
	$result = $db->query( 'INSERT INTO posts (id, author, created, content, parent, public) VALUES (UUID(), "' . $userIdAdmin . '", ' . ($start_time + 200) . ', "Hello, world!\n\nHow do I love Hai? Let me count the ways:\n\n# Numbered lists\n1 Flexible, too!\n\n* And bulleted lists\n* that can be as long\n* as you want", 0, 1)' );
	$postAdmin1 = get_db_value( $db, "SELECT MAX(id) FROM posts" );
	$result = $db->query( 'INSERT INTO posts (id, author, created, content, parent, public) VALUES (UUID(), "' . $userIdKirk . '", ' . ($start_time + 400) . ', "Captain\'s Log, Startdate 4024.4.\n\nhttp://brentnewhall.com/graphics/profile.jpg|50 \n\n_These_ __are__ *the* **voyages**.", 0, 1)' );
	$postKirk1 = get_db_value( $db, "SELECT MAX(id) FROM posts" );
	$result = $db->query( 'INSERT INTO posts (id, author, created, content, parent, public) VALUES (UUID(), "' . $userIdKirk . '", ' . ($start_time + 600) . ', "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur ut quam nec justo efficitur fermentum. Nam vulputate lorem dictum commodo aliquet. Maecenas interdum eros a metus vehicula consequat. Phasellus varius vitae augue vitae malesuada. Donec ac erat orci. Ut placerat lectus eget tellus blandit, quis pharetra felis pharetra. Quisque euismod eros sed orci maximus, in pharetra lacus congue. Pellentesque lobortis quam eu risus sagittis suscipit. Nullam sit amet odio blandit, lobortis justo sed, consectetur odio. Nunc vel est ipsum. Phasellus eleifend bibendum commodo. Maecenas ultrices, diam sit amet mollis rutrum, purus ante congue odio, ac mattis nisi nunc et nisl. Phasellus consequat velit id faucibus gravida. Praesent at nisl vel neque volutpat cursus ac sit amet ipsum. ", 0, 1)' );
	$postKirk2 = get_db_value( $db, "SELECT MAX(id) FROM posts" );
	$result = $db->query( 'INSERT INTO posts (id, author, created, content, parent, public) VALUES (UUID(), "' . $userIdUhura . '", ' . ($start_time + 800) . ', "So this is my #firstpost on Hai.", 0, 1)' );
	$postUhura1 = get_db_value( $db, "SELECT MAX(id) FROM posts" );
	$result = $db->query( 'INSERT INTO posts (id, author, created, content, parent, public) VALUES (UUID(), "' . $userIdPicard . '", ' . ($start_time + 800) . ', "This is a post from Jean-Luc Picard, which should not show up in Brent\'s stream.", 0, 1)' );
	$postPicard1 = get_db_value( $db, "SELECT MAX(id) FROM posts" );
	$result = $db->query( "DROP TABLE post_groups" );
	$result = $db->query( 'CREATE TABLE post_groups (post CHAR(36) NOT NULL, usergroup CHAR(36) NOT NULL)' );
	$result = $db->query( "DROP TABLE comments" );
	$result = $db->query( 'CREATE TABLE comments (id CHAR(36) NOT NULL PRIMARY KEY, created INT NOT NULL, author CHAR(36) NOT NULL, post CHAR(36) NOT NULL, content TEXT)' );
	$result = $db->query( 'INSERT INTO comments (id, created, author, post, content) VALUES (UUID(), ' . ($start_time + 1000) . ', "' . $userIdKirk . '", "' . $postAdmin1 . '", "That is a **very** interesting point.")' );
	$result = $db->query( 'INSERT INTO comments (id, created, author, post, content) VALUES (UUID(), ' . ($start_time + 2000) . ', "' . $userIdUhura . '", "' . $postAdmin1 . '", "I \'\'completely\'\' agree!")' );
	print $db->error;
	}

function get_db_value( $db, $query, $param_types = '', $param1 = '', 
                       $param2 = '', $param3 = '' )
	{
	$stmt = $db->stmt_init();
	$stmt = $db->prepare( $query );
	if( $stmt->error )
		print $stmt->error;
	if( $param3 != '' )
		$stmt->bind_param( $param_types, $param1, $param2, $param3 );
	elseif( $param2 != '' )
		$stmt->bind_param( $param_types, $param1, $param2 );
	elseif( $param1 != '' )
		$stmt->bind_param( $param_types, $param1 );
	$stmt->execute();
	$stmt->bind_result( $value );
	$stmt->fetch();
	$stmt->close();
	return $value;
	}

function getCarriers()
	{
	$a = array();
	$a["3 River Wireless"] = "sms.3rivers.net";
	$a["7-11 Speakout (USA GSM)"] = "cingularme.com";
	$a["ACS Wireless"] = "paging.acswireless.com";
	$a["Advantage Communications"] = "advantagepaging.com";
	$a["Airtel (Karnataka, India)"] = "airtelkk.com";
	$a["Airtel Wireless (Montana, USA)"] = "sms.airtelmontana.com";
	$a["Airtouch Pagers"] = "airtouch.net";
	$a["Alaska Communications Systems"] = "msg.acsalaska.com";
	$a["Alltel"] = "message.alltel.com";
	$a["AlphaNow"] = "alphanow.net";
	$a["American Messaging"] = "page.americanmessaging.net";
	$a["American Messaging (SBC/Ameritech)"] = "page.americanmessaging.net";
	$a["Ameritech Clearpath"] = "clearpath.acswireless.com";
	$a["Ameritech Paging"] = "paging.acswireless.com";
	$a["Andhra Pradesh Airtel"] = "airtelap.com";
	$a["Aql"] = "text.aql.com";
	$a["Arch Pagers (PageNet)"] = "archwireless.net";
	$a["AT&T"] = "mobile.att.net";
	$a["AT&T Enterprise Paging"] = "page.att.net";
	$a["AT&T Free2Go"] = "mmode.com";
	$a["AT&T PCS"] = "mobile.att.net";
	$a["AT&T Pocketnet PCS"] = "dpcs.mobile.att.net";
	$a["BeeLine GSM"] = "sms.beemail.ru";
	$a["Beepwear"] = "beepwear.net";
	$a["Bell Atlantic"] = "message.bam.com";
	$a["Bell Canada"] = "txt.bellmobility.ca";
	$a["Bell Mobility"] = "txt.bell.ca";
	$a["Bell South"] = "sms.bellsouth.com";
	$a["Bell South (Blackberry)"] = "bellsouthtips.com";
	$a["Bell South Mobility"] = "blsdcs.net";
	$a["BigRedGiant Mobile Solutions"] = "tachyonsms.co.uk";
	$a["Blue Sky Frog"] = "blueskyfrog.com";
	$a["Bluegrass Cellular"] = "sms.bluecell.com";
	$a["Boost"] = "myboostmobile.com";
	$a["Boost Mobile"] = "myboostmobile.com";
	$a["BPL Mobile"] = "bplmobile.com";
	$a["BPL Mobile (Mumbai, India)"] = "bplmobile.com";
	$a["Carolina Mobile Communications"] = "cmcpaging.com";
	$a["Carolina West Wireless"] = "cwwsms.com";
	$a["Cellular One"] = "cellularone.textmsg.com";
	$a["Cellular One (Dobson)"] = "mobile.celloneusa.com";
	$a["Cellular One (East Coast)"] = "phone.cellone.net";
	$a["Cellular One (South West)"] = "swmsg.com";
	$a["Cellular One (West)"] = "mycellone.com";
	$a["Cellular One PCS"] = "paging.cellone-sf.com";
	$a["Cellular South"] = "csouth1.com";
	$a["Centennial Wireless"] = "cwemail.com";
	$a["Central Vermont Communications"] = "cvcpaging.com";
	$a["CenturyTel"] = "messaging.centurytel.net";
	$a["Chennai RPG Cellular"] = "rpgmail.net";
	$a["Chennai Skycell / Airtel"] = "airtelchennai.com";
	$a["Cincinnati Bell"] = "gocbw.com";
	$a["Cincinnati Bell Wireless"] = "gocbw.com";
	$a["Cingular"] = "page.cingular.com";
	$a["Cingular (GoPhone prepaid)"] = "cingularme.com";
	$a["Cingular (Now AT&T)"] = "txt.att.net";
	$a["Cingular (Postpaid)"] = "cingularme.com";
	$a["Cingular Wireless"] = "mycingular.textmsg.com";
	$a["Claro (Brasil)"] = "clarotorpedo.com.br";
	$a["Claro (Nicaragua)"] = "ideasclaro-ca.com";
	$a["Clearnet"] = "msg.clearnet.com";
	$a["Comcast"] = "comcastpcs.textmsg.com";
	$a["Comcel"] = "comcel.com.co";
	$a["Communication Specialist Companies"] = "pager.comspeco.com";
	$a["Communication Specialists"] = "pageme.comspeco.net";
	$a["Comviq"] = "sms.comviq.se";
	$a["Cook Paging"] = "cookmail.com";
	$a["Corr Wireless Communications"] = "corrwireless.net";
	$a["Cricket Wireless"] = "sms.mycricket.com";
	$a["CTI"] = "sms.ctimovil.com.ar";
	$a["Delhi Aritel"] = "airtelmail.com";
	$a["Delhi Hutch"] = "delhi.hutch.co.in";
	$a["Digi-Page / Page Kansas"] = "page.hit.net";
	$a["Dobson"] = "mobile.dobson.net";
	$a["Dobson Cellular Systems"] = "mobile.dobson.net";
	$a["Dobson-Alex Wireless / Dobson-Cellular One"] = "mobile.cellularone.com";
	$a["DT T-Mobile"] = "t-mobile-sms.de";
	$a["Dutchtone / Orange-NL"] = "sms.orange.nl";
	$a["Edge Wireless"] = "sms.edgewireless.com";
	$a["EMT"] = "sms.emt.ee";
	$a["Emtel (Mauritius)"] = "emtelworld.net";
	$a["Escotel"] = "escotelmobile.com";
	$a["Fido"] = "fido.ca";
	$a["Gabriel Wireless"] = "epage.gabrielwireless.com";
	$a["Galaxy Corporation"] = "sendabeep.net";
	$a["GCS Paging"] = "webpager.us";
	$a["General Communications Inc."] = "msg.gci.net";
	$a["German T-Mobile"] = "t-mobile-sms.de";
	$a["Globalstar (satellite)"] = "msg.globalstarusa.com";
	$a["Goa BPLMobil"] = "bplmobile.com";
	$a["Golden Telecom"] = "sms.goldentele.com";
	$a["GrayLink / Porta-Phone"] = "epage.porta-phone.com";
	$a["GTE"] = "messagealert.com";
	$a["Gujarat Celforce"] = "celforce.com";
	$a["Helio"] = "messaging.sprintpcs.com";
	$a["Houston Cellular"] = "text.houstoncellular.net";
	$a["i wireless"] = "number.iws@iwspcs.net";
	$a["Idea Cellular"] = "ideacellular.net";
	$a["Illinois Valley Cellular"] = "ivctext.com";
	$a["Indiana Paging Co"] = "inlandlink.com";
	$a["Infopage Systems"] = "page.infopagesystems.com";
	$a["Inland Cellular Telephone"] = "inlandlink.com";
	$a["Iridium (satellite)"] = "msg.iridium.com";
	$a["Iusacell"] = "rek2.com.mx";
	$a["JSM Tele-Page"] = "jsmtel.com";
	$a["Kerala Escotel"] = "escotelmobile.com";
	$a["Kolkata Airtel"] = "airtelkol.com";
	$a["Koodo Mobile (Canada)"] = "msg.koodomobile.com";
	$a["Kyivstar"] = "smsmail.lmt.lv";
	$a["Lauttamus Communication"] = "e-page.net";
	$a["LMT"] = "smsmail.lmt.lv";
	$a["Maharashtra BPL Mobile"] = "bplmobile.com";
	$a["Maharashtra Idea Cellular"] = "ideacellular.net";
	$a["Manitoba Telecom Systems"] = "text.mtsmobility.com";
	$a["MCI"] = "pagemci.com";
	$a["MCI Phone"] = "mci.com";
	$a["Mero Mobile (Nepal)"] = "977sms.spicenepal.com";
	$a["Meteor"] = "sms.mymeteor.ie";
	$a["Meteor (Ireland)"] = "sms.mymeteor.ie";
	$a["Metrocall"] = "page.metrocall.com";
	$a["Metrocall 2-way"] = "my2way.com";
	$a["MetroPCS"] = "mymetropcs.com";
	$a["Microcell"] = "fido.ca";
	$a["Midwest Wireless"] = "clearlydigital.com";
	$a["MiWorld"] = "m1.com.sg";
	$a["Mobilcomm"] = "mobilecomm.net";
	$a["Mobilecom PA"] = "page.mobilcom.net";
	$a["Mobilfone"] = "page.mobilfone.com";
	$a["Mobility Bermuda"] = "ml.bm";
	$a["MobiPCS (Hawaii only)"] = "mobipcs.net";
	$a["Mobistar Belgium"] = "mobistar.be";
	$a["Mobitel (Sri Lanka)"] = "sms.mobitel.lk";
	$a["Mobitel Tanzania"] = "sms.co.tz";
	$a["Mobtel Srbija"] = "mobtel.co.yu";
	$a["Morris Wireless"] = "beepone.net";
	$a["Motient"] = "isp.com";
	$a["Movicom (Argentina)"] = "sms.movistar.net.ar";
	$a["Movistar"] = "correo.movistar.net";
	$a["Movistar (Colombia)"] = "movistar.com.co";
	$a["MTN (South Africa)"] = "sms.co.za";
	$a["MTS"] = "text.mtsmobility.com";
	$a["Mumbai BPL Mobile"] = "bplmobile.com";
	$a["Mumbai Orange"] = "orangemail.co.in";
	$a["NBTel"] = "wirefree.informe.ca";
	$a["Netcom"] = "sms.netcom.no";
	$a["Nextel"] = "page.nextel.com";
	$a["Nextel (Argentina)"] = "TwoWay.11nextel.net.ar";
	$a["Nextel (United States)"] = "messaging.nextel.com";
	$a["Northeast Paging"] = "pager.ucom.com";
	$a["NPI Wireless"] = "npiwireless.com";
	$a["Ntelos"] = "pcs.ntelos.com";
	$a["O2"] = "o2imail.co.uk";
	$a["O2 (M-mail)"] = "mmail.co.uk";
	$a["Omnipoint"] = "omnipointpcs.com";
	$a["One Connect Austria"] = "onemail.at";
	$a["OnlineBeep"] = "onlinebeep.net";
	$a["Optus Mobile"] = "optusmobile.com.au";
	$a["Orange"] = "orange.net";
	$a["Orange - NL / Dutchtone"] = "sms.orange.nl";
	$a["Orange Mumbai"] = "orangemail.co.in";
	$a["Orange NL / Dutchtone"] = "sms.orange.nl";
	$a["Orange Polska (Poland)"] = "9digit@orange.pl";
	$a["Oskar"] = "mujoskar.cz";
	$a["P&T Luxembourg"] = "sms.luxgsm.lu";
	$a["Pacific Bell"] = "pacbellpcs.net";
	$a["PageMart"] = "7digitpinpagemart.net";
	$a["PageMart Advanced /2way"] = "airmessage.net";
	$a["PageMart Canada"] = "pmcl.net";
	$a["PageNet Canada"] = "pagegate.pagenet.ca";
	$a["PageOne NorthWest"] = "page1nw.com";
	$a["PCS One"] = "pcsone.net";
	$a["Personal (Argentina)"] = "alertas.personal.com.ar";
	/* $a["Personal Communication"] = "sms@pcom.ru (number in subject line)";
	$a["Personal Communication"] = "sms@pcom.ru (put the number in the subject line)"; */
	$a["Pioneer / Enid Cellular"] = "msg.pioneerenidcellular.com";
	$a["Plus GSM (Poland)"] = "+48text.plusgsm.pl";
	$a["PlusGSM"] = "text.plusgsm.pl";
	$a["Pondicherry BPL Mobile"] = "bplmobile.com";
	$a["Powertel"] = "voicestream.net";
	$a["President's Choice"] = "txt.bell.ca";
	$a["Price Communications"] = "mobilecell1se.com";
	$a["Primeco"] = "email.uscc.net";
	$a["Primtel"] = "sms.primtel.ru";
	$a["ProPage"] = "7digitpagerpage.propage.net";
	$a["Public Service Cellular"] = "sms.pscel.com";
	$a["Qualcomm"] = "name@pager.qualcomm.com";
	$a["Qwest"] = "qwestmp.com";
	$a["RAM Page"] = "ram-page.com";
	$a["Rogers"] = "pcs.rogers.com";
	$a["Rogers (Canada)"] = "pcs.rogers.com";
	$a["Rogers AT&T Wireless"] = "pcs.rogers.com";
	$a["Safaricom"] = "safaricomsms.com";
	$a["Sasktel (Canada)"] = "sms.sasktel.com";
	$a["Satelindo GSM"] = "satelindogsm.com";
	$a["Satellink"] = "satellink.net";
	$a["SBC Ameritech Paging"] = "paging.acswireless.com";
	$a["SCS-900"] = "scs-900.ru";
	$a["Setar Mobile email (Aruba)"] = "297+mas.aw";
	$a["SFR France"] = "sfr.fr";
	$a["Simple Freedom"] = "text.simplefreedom.net";
	$a["Skytel Pagers"] = "email.skytel.com";
	$a["SL Interactive (Australia)"] = "slinteractive.com.au";
	$a["Smart Telecom"] = "mysmart.mymobile.ph";
	$a["Solo Mobile"] = "txt.bell.ca";
	$a["Southern LINC"] = "page.southernlinc.com";
	$a["Southwestern Bell"] = "email.swbw.com";
	$a["Sprint"] = "sprintpaging.com";
	$a["Sprint PCS"] = "messaging.sprintpcs.com";
	$a["ST Paging"] = "page.stpaging.com";
	$a["Suncom"] = "tms.suncom.com";
	$a["Sunrise Mobile"] = "swmsg.com";
	$a["Surewest Communicaitons"] = "mobile.surewest.com";
	$a["Swisscom"] = "bluewin.ch";
	$a["Tamil Nadu BPL Mobile"] = "bplmobile.com";
	$a["Tele2 Latvia"] = "sms.tele2.lv";
	$a["Telefonica Movistar"] = "movistar.net";
	$a["Telenor"] = "mobilpost.no";
	$a["Teletouch"] = "pageme.teletouch.com";
	$a["Telia Denmark"] = "gsm1800.telia.dk";
	$a["Telus"] = "msg.telus.com";
	$a["Telus Mobility (Canada)"] = "msg.telus.com";
	$a["The Indiana Paging Co"] = "last4digits@pager.tdspager.com";
	$a["Thumb Cellular"] = "sms.thumbcellular.com";
	$a["Tigo (Formerly Ola)"] = "sms.tigo.com.co";
	$a["TIM"] = "timnet.com";
	$a["T-Mobile"] = "tmomail.net";
	$a["T-Mobile (Austria)"] = "sms.t-mobile.at";
	$a["T-Mobile (Germany)"] = "t-d1-sms.de";
	$a["T-Mobile (UK)"] = "t-mobile.uk.net";
	$a["Tracfone"] = "txt.att.net";
	$a["Tracfone (prepaid)"] = "mmst5.tracfone.com";
	$a["Triton"] = "tms.suncom.com";
	$a["TSR Wireless"] = "beep.com";
	$a["U.S. Cellular"] = "email.uscc.net";
	$a["UCOM"] = "pager.ucom.com";
	$a["UMC"] = "sms.umc.com.ua";
	$a["Unicel"] = "utext.com";
	$a["Uraltel"] = "sms.uraltel.ru";
	$a["US Cellular"] = "uscc.textmsg.com";
	$a["US West"] = "uswestdatamail.com";
	$a["Uttar Pradesh Escotel"] = "escotelmobile.com";
	$a["Verizon"] = "vtext.com";
	$a["Verizon Pagers"] = "myairmail.com";
	$a["Verizon PCS"] = "vtext.com";
	$a["Vessotel"] = "pager.irkutsk.ru";
	$a["Virgin Mobile"] = "vmobl.com ";
	$a["Virgin Mobile (Canada)"] = "vmobile.ca";
	$a["Vodacom (South Africa)"] = "voda.co.za";
	$a["Vodafone (Italy)"] = "sms.vodafone.it";
	$a["Vodafone (Japan)"] = "c.vodafone.ne.jp";
	$a["Vodafone (UK)"] = "vodafone.net";
	$a["VoiceStream"] = "voicestream.net";
	$a["VoiceStream / T-Mobile"] = "voicestream.net";
	$a["WebLink Wireless"] = "airmessage.net";
	$a["West Central Wireless"] = "sms.wcc.net";
	$a["Western Wireless"] = "cellularonewest.com";
	$a["Wyndtell"] = "wyndtell.com";
	$a["YCC"] = "sms.ycc.ru";
	return $a;
	}

//rebuild_db( $db, $admin, $crypt_salt );

$userID = "";
if( isset( $_SESSION["logged_in"] ) )
	$userID = get_db_value( $db, "SELECT id FROM users WHERE username = ?", "s", $_SESSION["logged_in"] );

?>
