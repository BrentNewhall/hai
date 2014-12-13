<html>
<body>

<?php
define( FACEBOOK_APP_ID, 752695678143085 );
define( FACEBOOK_SECRET, 'db5fdaa5b17e21566ce4a98e27eb987f' );

function callFacebook( $url, $post = "no" )
	{
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	if( $post != "no" )
		{
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl_handle, CURLOPT_POST, true);
		}
	curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,6);
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	$buffer = curl_exec($curl_handle);
	curl_close($curl_handle);    
	return $buffer;
	}

if(!isset($_GET["error"]))
{

 if(isset($_GET["code"]))
 {
  $code = $_GET["code"];    
  print( "Got code $code<br>\n" );
  $url = 'https://graph.facebook.com/oauth/access_token?client_id='.FACEBOOK_APP_ID.'&redirect_uri='.urlencode('http://hai.social/facebook_login.php').'&client_secret='.FACEBOOK_SECRET.'&code='.$code;
  
  $curl_handle=curl_init();
  curl_setopt($curl_handle,CURLOPT_URL,$url);
  curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,6);
  curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
  $buffer = curl_exec($curl_handle);
  curl_close($curl_handle);    
  print( "Got $buffer from Facebook<br>\n" );
  if(strpos($buffer, 'access_token=') === 0)
  {
   //if you requested offline acces save this token to db 
   //for use later   
   $token = str_replace('access_token=', '', $buffer);

   /* $access_request_result = callFacebook( "https://graph.facebook.com/oauth/authorize?client_id=".FACEBOOK_APP_ID."&redirect_uri=".urlencode("http://hai.social/facebook_login.php")."&scope=publish_stream" );
   print( "Access request result: $access_request_result<br>\n" ); */
      
   //this is just to demo how to use the token and 
   //retrieves the users facebook_id
   $url = 'https://graph.facebook.com/me/?access_token='.$token;
   $curl_handle=curl_init();
   curl_setopt($curl_handle,CURLOPT_URL,$url);
   curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,2);
   curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,true);
   $buffer = curl_exec($curl_handle);
   curl_close($curl_handle);
   $jobj = json_decode($buffer);
   $facebook_id = $jobj->id;
   
   print( "Your Facebook ID is $facebook_id<br>\n" );
		$fields = array(
			"message" => "Gray, drizzly days are made for tea, a comfy chair, and a good book.",
			"name" => "Check out my new social network",
			"link" => "http://hai.social/",
			"description" => "Grey, drizzly days are made for tea, a comfy chair, and a good book.",
		);
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/$facebook_id/feed");
   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
   curl_setopt($ch, CURLOPT_POST, true);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $fields );
   curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,5);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  //to suppress the curl output 
   $result = curl_exec($ch);
   curl_close ($ch);
   print( "Posted to Facebook with result $result.<br />\n" );
   
  }
  else
  {
   // Do "error stuff"
  }
 }
 else
 {
   // Display Facebook connect bit
?>
<a href="https://www.facebook.com/dialog/oauth?client_id=<?=FACEBOOK_APP_ID?>&redirect_uri=
<?=urlencode('http://hai.social/facebook_login.php')?>
&scope=offline_access,user_checkins,friends_checkins,user_status,publish_actions">Connect with Facebook</a>
<?php
 }
}
else
{
 //do error stuff
}
?>

</body>
</html>
