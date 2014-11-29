<?php

require_once( "database.php" );

$fn = (isset($_SERVER['HTTP_X_FILENAME']) ? $_SERVER['HTTP_X_FILENAME'] : false);

$file_type = "jpg";
if( isset( $_GET["type"] )  &&  $_GET["type"] != "" )
	$file_type = $_GET["type"];
$user_id = "";
if( isset( $_GET["user"] )  &&  $_GET["user"] != "" )
	$user_id = $_GET["user"];

function add_user_media( $db, $user_id, $filename, $type )
	{
	update_db( $db, "INSERT INTO user_media (id, created, user, filename, type) VALUES (UUID(), ?, ?, ?, '$type')", "iss", time(), $user_id, $filename );
	}

if ($fn)
	{
	// AJAX call
	$new_filename = getGUID() . "." . $file_type;
	if( $file_type == "mp4" )
		{
		file_put_contents(
			'assets/video/uploads/' . $new_filename,
			file_get_contents('php://input')
		);
		add_user_media( $db, $user_id, $new_filename, "video" );
		echo "http://hai.social/assets/video/uploads/$new_filename";
		}
	else
		{
		file_put_contents(
			'assets/images/uploads/' . $new_filename,
			file_get_contents('php://input')
		);
		//echo "$fn uploaded.";
		add_user_media( $db, $user_id, $new_filename, "image" );
		echo "http://hai.social/assets/images/uploads/$new_filename";
		}
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
			if( $file_type == "mp4" )
				{
				move_uploaded_file(
					$files['tmp_name'][$id],
					'assets/video/uploads/' . $new_filename
				);
				add_user_media_record( $db, $user_id, $new_filename, "video" );
				echo "http://hai.social/assets/video/uploads/$new_filename";
				}
			else
				{
				move_uploaded_file(
					$files['tmp_name'][$id],
					'assets/images/uploads/' . $new_filename
				);
				add_user_media_record( $db, $user_id, $new_filename, "image" );
				echo "http://hai.social/assets/images/uploads/$new_filename";
				}
			//echo "<p>File $fn uploaded.</p>";
			}
		}
	}

