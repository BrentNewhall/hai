var STR_PAD_LEFT = 1;
var STR_PAD_RIGHT = 2;

function str_pad( str, len, pad, dir )
	{
	if (typeof(len) == "undefined") { var len = 0; }
	if (typeof(pad) == "undefined") { var pad = ' '; }
	if (typeof(dir) == "undefined") { var dir = STR_PAD_RIGHT; }
	if( len + 1 >= str.length )
		{
		switch( dir )
			{
			case STR_PAD_LEFT:
				str = Array(len + 1 - str.length).join(pad) + str;
				break;
			default: // RIGHT
				str = str + Array(len + 1 - str.length).join(pad);
				break;
			} // end switch
		} // end if string has length
	return str;
	} // str_pad()

function displayDelete( post_id )
	{
	var box = document.getElementById("dialog-box");
	box.style.display = "block";
	box.innerHTML = "<p>Are you sure you want to delete this post?</p>" + 
	                "<center><button onclick=\"location.href='delete.php?i=" + post_id + "'\">Yes</button> &nbsp; &nbsp; " + 
	                "<button onclick=\"javascript:hideDelete();\">No</button></center>";
	}

function hideDelete()
	{
	document.getElementById("dialog-box").style.display = "none";
	}

function hidePasswordField( checkbox_id, password_id )
	{
	if( document.getElementById(checkbox_id).checked )
		{
		//document.getElementById(password_id).style.color = "black";
		//document.getElementById(password_id).style.background_color = "white";
		document.getElementById(password_id).setAttribute("type","text");
		}
	else
		{
		//document.getElementById(password_id).style.color = "white";
		//document.getElementById(password_id).style.background_color = "white";
		document.getElementById(password_id).setAttribute("type","password");
		}
	}

function updatePreview( source_div_id, target_div_id )
	{
	var text = document.getElementById(source_div_id).value;
	if( text.match( /^THis / ) )
		{
		text = text.replace( /^THis /, "This " );
		document.getElementById(source_div_id).value = text;
		}
	if( text.match( /^THe / ) )
		{
		text = text.replace( /^THe /, "The " );
		document.getElementById(source_div_id).value = text;
		}
	// Replace HTML with BBcode and remove the rest.
	text = text.replace( /<strong>|<b>/i, "[b]" );
	text = text.replace( /<\/strong>|<\/b>/i, "[/b]" );
	text = text.replace( /<em>|<i>/i, "[i]" );
	text = text.replace( /<\/em>|<\/i>/i, "[/i]" );
	text = text.replace( /<u>/i, "[u]" );
	text = text.replace( /<\/u>/i, "[/u]" );
	text = text.replace( /<a target=["'][\S]+?["'] href=["']([\S]+?)["']>([\S\s]+?)<\/a>/ig, "[url=$1]$2[/url]" );
	text = text.replace( /<a href=["']([\S]+?)["'] target=["'][\S]+?["']>([\S\s]+?)<\/a>/ig, "[url=$1]$2[/url]" );
	text = text.replace( /<a href=["']([\S]+?)["']>([\S\s]+?)<\/a>/ig, "[url=$1]$2[/url]" );
	text = text.replace( /<img src=["']([\S]+?)["']>/ig, "$1" );
	text = text.replace(/(<([^>]+)>)/ig,"");
	// Add breaks
	text = text.replace( /\n/g, "<br />\n" );
	// Process unordered and ordered lists
	lines = text.split( "\n" );
	var in_ul = 0;
	var in_ol = 0;
	var in_code = 0;
	for( i = 0; i < lines.length; i++ )
		{
		while( lines[i].search( /\[CODE\]/i ) >= 0  &&
		       lines[i].search( /\[\/CODE\]/i ) >= 0 )
			{
			lines[i] = lines[i].replace( /\[CODE\]/i, "<span class=\"style-code-snippet\">" );
			lines[i] = lines[i].replace( /\[\/CODE\]/i, "</span>" );
			}
		if( lines[i].search( /\[CODE\]/i ) >= 0 )
			{
			in_code = 1;
			lines[i] = lines[i].replace( /\[CODE\]/i, "<div class=\"style-code-block\">Code:" );
			lines[i] = lines[i].replace( "<br />", "" );
			lines[i] = lines[i].replace( "<br></br>", "" );
			}
		else if( lines[i].search( /\[\/CODE\]/i ) >= 0 )
			{
			in_code = 0;
			lines[i] = lines[i].replace( /\[\/CODE\]/i, "</div>" );
			}
		else if( in_code >= 1 )
			{
			lines[i] = lines[i].replace( "<br />", "" );
			lines[i] = lines[i].replace( "<br></br>", "" );
			lines[i] = "<span class=\"code-line-number\">" + str_pad( String(in_code), 3, "0", STR_PAD_LEFT ) + "</span> " + lines[i];
			in_code = in_code + 1;
			}
		if( lines[i].substr( 0, 2 ) == "* " )
			{
			lines[i] = lines[i].replace( /^\* /, "<li> " );
			lines[i] = lines[i].replace( /<br \/>$/, "</li>" );
			if( in_ul == 0 )
				lines[i] = "<ul>" + lines[i];
			in_ul = 1;
			}
		else if( in_ul == 1 )
			{
			lines[i] = "</ul>" + lines[i];
			in_ul = 0;
			}
		if( lines[i].substr( 0, 2 ) == "# "  ||
		    lines[i].substr( 0, 2 ).match( "[0-9] " ) )
			{
			lines[i] = "<li> " + lines[i].substr( 2 );
			lines[i] = lines[i].replace( /<br \/>$/, "</li>" );
			if( in_ol == 0 )
				lines[i] = "<ol>" + lines[i];
			in_ol = 1;
			}
		else if( in_ol == 1 )
			{
			lines[i] = "</ol>" + lines[i];
			in_ol = 0;
			}
		}
	text = lines.join( "\n" );
	if( in_ul == 1 )
		text += "</ul>";
	if( in_ol == 1 )
		text += "</ol>";
	// Process forum-style formatting
	text = text.replace( /\[B\]([\S\s]+?)\[\/B\]/ig, "<strong>$1</strong>" );
	text = text.replace( /\[I\]([\S\s]+?)\[\/I\]/ig, "<em>$1</em>" );
	text = text.replace( /\[U\]([\S\s]+?)\[\/U\]/ig, "<span style=\"text-decoration: underline\">$1</span>" );
	text = text.replace( /\[COLOR=([\S]+?)\]([\S\s]+?)\[\/COLOR\]/ig, "<span style=\"color: $1\">$2</span>" );
	text = text.replace( /\[SIZE=([\S]+?)\]([\S\s]+?)\[\/SIZE\]/ig, "<span style=\"font-size: $1\">$2</span>" );
	text = text.replace( /\[FONT=([\S]+?)\]([\S\s]+?)\[\/FONT\]/ig, "<span style=\"font-family: $1\">$2</span>" );
	text = text.replace( /\[ALIGN=(LEFT|CENTER|RIGHT)\]([\S\s]+?)\[\/ALIGN\]/ig, "<div style=\"text-align: $1\">$2</div>" );
	text = text.replace( /\[INDENT]([\S\s]+?)\[\/INDENT\]/ig, "<div style=\"padding-left: 25px;\">$1</div>" );
	text = text.replace( /\[EMAIL\]([\S\s]+?)\[\/EMAIL\]/ig, "<a href=\"mailto:$1\">$1</a>" );
	text = text.replace( /\[URL\]([\S\s]+?)\[\/URL\]/ig, "<a href=\"$1\">$1</a>" );
	text = text.replace( /\[URL=([\S]+?)\]([\S\s]+?)\[\/URL\]/ig, "<a href=\"$1\">$2</a>" );
	text = text.replace( /\[IMG\]([\S\s]+?)\[\/IMG\]/ig, "<img src=\"$1\" style=\"max-width: 500px\" />" );
	// Process other stuff
	text = text.replace( /<br \/>\n<br \/>\n<ul>/g, "<br />\n<ul>" );
	text = text.replace( /<br \/>\n<br \/>\n<ol>/g, "<br />\n<ol>" );
	text = text.replace( /'''([\S\s]+?)'''/g, "<strong>$1</strong>" );
	text = text.replace( /''([\S\s]+?)''/g, "<em>$1</em>" );
	text = text.replace( /__([\S\s]+?)__/g, "<strong>$1</strong>" );
	text = text.replace( /\*\*([\S\s]+?)\*\*/g, "<strong>$1</strong>" );
	text = text.replace( /(\s|^)_([\S\s]+?)_(\s|\n|\.|\,|$)/g, "$1<em>$2</em>$3" );
	text = text.replace( /\*([\S\s]+?)\*/g, "<em>$1</em>" );
	text = text.replace( /(http|https):\/\/www\.youtube\.com\/watch\?v=([\S]+)/ig, "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"$1://www.youtube.com/embed/$2\" frameborder=\"0\" />" );
	text = text.replace( /(http|https):\/\/youtu\.be\/([\S]+)/ig, "<iframe type=\"text/html\" width=\"500\" height=\"320\" src=\"$1://www.youtube.com/embed/$2\" frameborder=\"0\"></iframe>" );
	text = text.replace( /(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)\|([0-9]+)/ig, "<img src=\"$1://$2.$3\" style=\"width: $4px; max-width: 500px\" />" );
	text = text.replace( /(http|https):\/\/([\S]+)\.(jpg|jpeg|gif|png)([^\"])/ig, "<img src=\"$1://$2.$3\" style=\"max-width: 500px\" />$4" );
	text = text.replace( /(http|https):\/\/([A-Za-z0-9\.\%\$&\?\#\/\-_=]+)(\s|\n|$)/igm, "<a href=\"$1://$2\">$2</a>$3" );
	text = text.replace( /(\s|^)#([A-Za-z0-9\-]+)/g, "$1<a href=\"hashtag.php?tag=$2\">#$2</a>" );
	document.getElementById(target_div_id).innerHTML = text;
	}

function toggleComposePane( tools_id, compose_pane_id, compose_post_id )
	{
	var button_label = "Write";
	if( compose_post_id != "compose-post" )
		button_label = "Reply";
	if( document.getElementById(compose_pane_id).style.display == "none" )
		{
		document.getElementById(tools_id).innerHTML = "<input type=\"submit\" value=\"Post\" /><br /><br /><button onclick='javascript:toggleComposePane(\"" + tools_id + "\",\"" + compose_pane_id + "\",\"" + compose_post_id + "\");return false;'>Discard</button>";
		document.getElementById(compose_pane_id).style.display = "block";
		document.getElementById(compose_post_id).focus();
		}
	else
		{
		document.getElementById(tools_id).innerHTML = "<button onclick='javascript:toggleComposePane(\"" + tools_id + "\",\"" + compose_pane_id + "\",\"" + compose_post_id + "\");return false;'>" + button_label + "</button>";
		document.getElementById(compose_pane_id).style.display = "none";
		}
	}

function setReplyTo( post_id, author, content )
	{
	// Updates the reply-to field
	// Create snippet
	var snippet_length = 100;
	var space_cutoff = 80;
	var snippet;
	if( content.length < snippet_length )
		snippet = content;
	else
		{
		snippet = content.substring( 0, snippet_length );
		var space_pos = snippet.lastIndexOf( " " );
		if( space_pos < space_cutoff )
			space_pos = snippet.search( "\n" );
		if( space_pos >= space_cutoff )
			snippet = snippet.substring( 0, space_pos ).trim();
		}
	// Update div
	var div = document.getElementById('reply-to');
	div.innerHTML = "Replying to " + author + "'s post <em>" + snippet + "</em>...<input type='hidden' name='reply-to-post-id' value='" + post_id + "' />";
	// Display compose pane, if not visible
	div.style.display = "block";
	if( document.getElementById('compose-pane').style.display == "none" )
		toggleComposePane( 'compose-tools', 'compose-pane', 'compose-post' );
	}

function setComposeForEdit( post_id, compose_div_id, content, comment_id )
	{
	var content2 = content.replace( /==\[\[BR\]\]==/g, "\n" );
	var content2 = content2.replace( /==\[\[QUOTE\]\]==/g, "\"" );
	// Update div
	var div = document.getElementById(compose_div_id);
	div.value = content2;
	if( compose_div_id == "compose-post" )
		{
		var div = document.getElementById('reply-to');
		div.innerHTML = "<strong>Editing</strong> <input type='hidden' name='editing-post-id' value='" + post_id + "' />";
		// Display compose pane, if not visible
		div.style.display = "block";
		if( document.getElementById('compose-pane').style.display == "none" )
			toggleComposePane( 'compose-tools', 'compose-pane', 'compose-post' );
		}
	else
		{
		var div = document.getElementById('reply-to-'+post_id);
		div.innerHTML = "</strong> <input type='hidden' name='editing-comment-id' value='" + comment_id + "' />";
		// Display compose pane, if not visible
		if( document.getElementById('compose-pane-' + post_id).style.display == "none" )
			toggleComposePane( 'compose-tools-' + post_id, 'compose-pane-' + post_id, 'compose-comment-' + post_id );
		}
	}

function sendData( params )
	{
	if (window.XMLHttpRequest)
   		// Create the object for browsers
   		xmlhttp = new XMLHttpRequest();
   	else
   		// Create the object for browser versions prior to IE 7
   		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
   	xmlhttp.onreadystatechange=function()
   		{
   		// if server is ready with the response
   		if (xmlhttp.readyState==4)
   			{
   			// if everything is Ok on browser
   			if(xmlhttp.status==200)
   				{    
				// Remove old button
				var div = document.getElementById("load-more-posts");
				div.parentNode.removeChild(div);
   				// Update the div with the response
   				var div = document.getElementById("body-container");
				var contents = div.innerHTML;
   				contents += xmlhttp.responseText;
				//contents += "<button>Load more posts?</button>";
				div.innerHTML = contents;
   				}
   			}
   		}
   	//send the selected data to the php page
   	xmlhttp.open("GET","load_more_posts.php" + params,true);
	xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
	xmlhttp.send(null);
	}


function loadMorePosts( tab, user_id, start_index )
	{
	sendData( "?tab=" + tab + "&u=" + user_id + "&index=" + start_index );
	}

function displayWorldSuggestions( source_div, restrictions_div, public_checkbox )
	{
	var text = document.getElementById( source_div ).value;
	if( text == "" )
		{
		document.getElementById(restrictions_div).innerHTML = "This post will appear in the \"Everything\" stream, and in the streams of anyone who's added you to a Team.";
		}
	else
		{
		if( document.getElementById(public_checkbox).checked )
			document.getElementById(restrictions_div).innerHTML = "This post will appear in the \"Everything\" stream, and in the streams of anyone who's added you to a Team, and in the \"" + text + "\" World.";
		else
			document.getElementById(restrictions_div).innerHTML = "This post will appear in the \"" + text + "\" World.";
		}
	}
