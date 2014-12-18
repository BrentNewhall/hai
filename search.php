<?php

function getNiceList( $words )
	{
	if( ! is_array( $words )  ||  count( $words ) == 0 )
		return "";
	elseif( count( $words ) == 1 )
		return "<strong>" . $words[0] . "</strong>";
	elseif( count( $words ) == 2 )
		return "<strong>" . $words[0] . "</strong> and <strong>" . $words[1] . "</strong>";
	else
		{
		$t = "";
		for( $i = 0; $i < count($words) - 1; $i++ )
			$t .= "<strong>" . $words[$i] . "</strong>, ";
		return $t . "and <strong>" . $words[count($words)-1] . "</strong>";
		}
	}

$page_title = "Search";
$query = "";
if( isset( $_GET["q"] )  &&  $_GET["q"] != "" )
	{
	$query = $_GET["q"];
	$page_title = "$query - $page_title";
	}
require_once( "header.php" );
require_once( "database.php" );

displayNavbar( $db, $userID );

print( "<h1>Search</h1>\n" .
       "<form action=\"search.php\" method=\"get\">\n" .
       "<input type=\"text\" name=\"q\" value=\"$query\" size=\"20\" />\n" .
       "<input type=\"submit\" value=\"search\" />\n" .
       "</form>\n" );

if( $query != "" )
	{
	$sql = getStandardSQLselect() .
		   "LEFT JOIN broadcasts ON (broadcasts.post = posts.id) " .
		   "LEFT JOIN comments ON (comments.post = posts.id) " .
	       "WHERE ";
	
	$contains_negation = false;
	$query = str_replace( "  ", " ", $query );
	$terms = explode( " ", $query );
	// Implode quoted strings
	for( $i = 0; $i < count($terms); $i++ )
		{
		if( substr( $terms[$i], 0, 1 ) == "\"" )
			{
			$quoted_string = array( $terms[$i] );
			for( $j = $i+1; $j < count($terms); $j++ )
				{
				array_push( $quoted_string, $terms[$j] );
				if( substr( $terms[$j], strlen($terms[$j]) - 1 ) == "\"" )
					{
					//$terms = array_splice( $terms, $i, $j-$i, implode( " ", $temp ) );
					$qs = implode( " ", $quoted_string );
					$qs = substr( $qs, 1, strlen($qs) - 2 );
					array_splice( $terms, $i, $j-$i+1, array( $qs ) );
					}
				}
			}
		}
	$query_terms = array( "" );
	foreach( $terms as $term )
		{
		// This regex simply returns nothing. Need to dig in more.
		/* array_push( $query_terms, "[[:<:]]$term" . "[[:>:]]" );
		array_push( $query_terms, "[[:<:]]$term" . "[[:>:]]" );
		$query_terms[0] .= "ss";
		$sql .= "(posts.content REGEXP ? OR comments.content REGEXP ?) AND "; */
		$query_terms[0] .= "ss";
		if( substr( $term, 0, 1 ) == "-" )
			{
			$term = substr( $term, 1 );
			$sql .= "(posts.content NOT LIKE ? OR comments.content NOT LIKE ?) AND ";
			$contains_negation = true;
			}
		else
			{
			$sql .= "(posts.content LIKE ? OR comments.content LIKE ?) AND ";
			}
		array_push( $query_terms, "%$term%" );
		array_push( $query_terms, "%$term%" );
		}
	if( $contains_negation )
		{
		$positive = array();
		$negative = array();
		foreach( $terms as $term )
			if( substr( $term, 0, 1 ) == "-" )
				array_push( $negative, substr( $term, 1 ) );
			else
				array_push( $positive, $term );
		print( "<p>Searching posts and comments that contain " . getNiceList( $positive ) . " and do <em>not</em> contain " . getNiceList( $negative ) . ".</p>\n" );
		}
	else
		print( "<p>Searching posts and comments that contain " . getNiceList( $terms ) . ".</p>\n" );
	
	// Display posts that match that hashtag
	$sql .= "posts.public = 1 " .
	        "ORDER BY posts.created DESC LIMIT 25";
	
	displayPosts( $db, $db2, $sql, $userID, 25, $query_terms );
	}

?>
<hr />
<p>Surround multiple words with " (double quotes) to search for phrases. Start a word with - to exclude that word from your search.</p>
<p>Examples:</p>
<table border="0">
	<tr>
		<th>Query</th>
		<th>Result</th>
	</tr>
	<tr>
		<td style="white-space: nowrap">doctor who</td>
		<td>Search for posts and comments containing the word <strong>doctor</strong> and the word <strong>who</strong></td>
	</tr>
	<tr>
		<td style="white-space: nowrap">doctor who -dalek</td>
		<td>Search for posts and comments containing the word <strong>doctor</strong> and the word <strong>who</strong>, and that do not contain the word <strong>dalek</strong></td>
	</tr>
	<tr>
		<td style="white-space: nowrap">"doctor who" cybermen</td>
		<td>Search for posts and comments containing the phrase <strong>doctor who</strong> and the word <strong>cybermen</strong></td>
	</tr>
</table>
<?php

require_once( "footer.php" );
?>
