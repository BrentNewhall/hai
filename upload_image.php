<?php

require_once( "database.php" );

$fn = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);

$file_type = "jpg";
if( isset( $_GET["type"] )  &&  $_GET["type"] != "" )
	$file_type = $_GET["type"];
$user_id = "";
if( isset( $_GET["user"] )  &&  $_GET["user"] != "" )
	$user_id = $_GET["user"];

function getGUID()
	{
	// This function is from http://guid.us/GUID/PHP
    if (function_exists('com_create_guid'))
		{
        return com_create_guid();
    	}
	else
		{
        mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);
        return $uuid;
    	}
	}

function add_user_image_record( $db, $user_id, $filename )
	{
	update_db( $db, "INSERT INTO user_images (id, user, filename) VALUES (UUID(), ?, ?)", "ss", $user_id, $filename );
	}


if ($fn)
	{
	$new_filename = getGUID() . "." . $file_type;
	// AJAX call
	file_put_contents(
		'assets/images/uploads/' . $new_filename,
		file_get_contents('php://input')
	);
	//echo "$fn uploaded.";
	echo "http://hai.social/assets/images/uploads/$new_filename";
	add_user_image_record( $db, $user_id, $new_filename );
	exit();
	}
else
	{
	// form submit
	$files = $_FILES['fileselect'];
	foreach ($files['error'] as $id => $err)
		{
		if ($err == UPLOAD_ERR_OK)
			{
			$new_filename = getGUID() . "." . $file_type;
			$fn = $files['name'][$id];
			move_uploaded_file(
				$files['tmp_name'][$id],
				'assets/images/uploads/' . $new_filename
			);
			//echo "<p>File $fn uploaded.</p>";
			echo "http://hai.social/assets/images/uploads/$new_filename";
			add_user_image_record( $db, $user_id, $new_filename );
			}
		}
	}

