<?php

// Delete a post from the database

require_once( "database.php" );

// Get post ID. If obviously invalid, kick back to main page.
if( ! isset( $_GET["i"] ) )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }
$post_id = $_GET["i"];
if( $post_id == ""  || strlen($post_id) != 36 )
	{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }

function deleteFromTable( $db, $table, $column, $post_id )
	{
	$sql = "DELETE FROM $table WHERE $column = ?";
	$stmt = $db->stmt_init();
	if( $stmt->prepare( $sql ) )
		{
		$stmt->bind_param( "s", $post_id );
		$stmt->execute();
		}
	$stmt->close();
	}

// Find that post's author's user ID
$sql = "SELECT users.id FROM posts, users WHERE posts.id = ? AND posts.author = users.id";
$stmt = $db->stmt_init();
if( $stmt->prepare( $sql ) )
	{
	$stmt->bind_param( "s", $post_id );
	$stmt->execute();
	$stmt->bind_result( $author_id );
	if( $stmt->fetch() )
		{
		if( $author_id == $userID )
			{
			$stmt->close();
			deleteFromTable( $db, "posts", "id", $post_id );
			deleteFromTable( $db, "comments", "post", $post_id );
			header( "Location: index.php\n\n" );
			exit( 0 );
			}
		else // That post isn't owned by you.
			{  header( "Location: index.php?error=202\n\n" ); exit( 0 );  }
		}
	else // That ID didn't return a record from the database.
		{  header( "Location: index.php?error=201\n\n" ); exit( 0 );  }
	}
?>
