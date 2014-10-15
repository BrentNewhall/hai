<?php
$page_title = "About";
require_once( "header.php" );

require_once( "database.php" );
if( $userID != "" )
	displayNavbar( $db, $userID );

?>
<h1>About</h1>
<p>Hai is an experiment in social networking. It attempts to combine powerful features from various online social services.</p>
<p>Features <span class="not-yet">in gray</span> are not yet implemented.</p>
<ul>
<li> Hai stores only the personal information you share with Hai. I do not share your personal information with anyone, unless you explicitly mark it "public."</li>
  <ul>
  <li> I will share aggregate usage information (such as, "How many people posted about fish in October?").</li>
  </ul>
<li> Hai provides multiple levels of anonymity and real names. You can use any name you want. If you use your real name, it is displayed distinctively (in bold).</li>
<li> Hai does not require use of an email address or any other personal information.</li>
  <ul>
  <li> You won't be able to recover your password unless you provide an email address, phone number, or answers to identifying questions. This is completely up to you.</li>
  <li> You can associate multiple email addresses and/or multiple phone numbers with your account.</li>
  </ul>
<li> Hai lets you own multiple accounts simultaneously.</li>
<li class="not-yet"> Hai lets you export all your data. Hai lets you import all this data into any account you own. This lets you merge accounts.</li>
<li class="notyet"> You can delete your account quickly and easily. When you do, all your information is completely deleted from our servers.</li>
<Li> Hai does not filter which posts you see.</li>
<li class="not-yet"> Hai lets you block anyone (their content does not appear for you, and they cannot interact with you in any way).</li>
<li class="not-yet"> Hai supports HTML, photos, and videos in your posts. Hai can host photos and videos (you can also link and embed).</li>
	<ul>
	<li> Hai shows you a preview of what your post will look like before you post it.</li>
	<li> Hai supports a <a href="formatting.php">useful subset of HTML, Markdown, and BBCode formatting</a>.</li>
	<li class="not-yet"> Hai suggests helpful links for movie titles and book titles, so you don't have to italicize and/or link them to Wikipedia.</li>
	</ul>
<li> Every post and comment can be linked back to an originating post. You can view threads of posts this way.</li>
<li class="not-yet"> All posts on Hai can be posted to public areas of interest (worlds) or private groups (rooms). In other words, you can restrict your posts to private conversations among only a few people.</li>
<li class="not-yet"> Hai's users pay Hai's bills. USD $2 per month gives you a "Sponsor" badge next to your avatar.</li>
<li class="not-yet"> Hai lets you cross-post to Facebook and Twitter, if you desire.</li>
<li class="not-yet"> Hai provides a robust RESTful API for posting and accessing Hai.</li>
<li> Hai is <a href="https://github.com/BrentNewhall/hai">open source</a>.</li>
</ul>

<p><a href="stats.php">Stats</a></p>
<?php

require_once( "footer.php" );
?>
