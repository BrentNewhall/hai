<?php
$page_title = "Help";
require_once( "header.php" );

displayNavbar( $db, $userID );

?>
<h1>Help</h1>

<p>Hai is intended as a next-generation social platform. It's not so much a place to talk as a place to store conversations.</p>

<h2>The Sidebar</h2>
<p>The sidebar on the left-hand side of each page contains the following sections:
<ul>
<li> <a href="/pings.php" style="font-weight: bold">Pings</a> are notifications of activity. If you write a post or comment on someone else's post, each new comment on those posts will generate a ping. You can also click the <em>Track</em> link underneath someone else's post to receive pings about that post.</li>
<li> <a href="/waves.php" style="font-weight: bold">Waves</a> are private messages between two users on Hai. Either users can post comments on that wave.</li>
<li> <a href="/teams.php" style="font-weight: bold">Teams</a> are groups of users. You can create as many Teams as you want and add whoever you want to those Teams. Each Team you create is listed on the navbar, and clicking on the Team's name displays a stream of posts by everyone in that Team.</li>
<li> <a href="/world.php" style="font-weight: bold">Worlds</a> are groups of posts that have all been tagged with the same topic, such as <em>Star Trek</em> or <em>Marvel</em>. Any regular post can be associated with one World.</li>
<li> <a href="/room.php" style="font-weight: bold">Rooms</a> are named areas of conversation which can be limited to certain people. This allows you to create a "walled garden" for certain topics or games.</li>
<li> <a href="/hashtag.php" style="font-weight: bold">Hashtags</a> are created by starting any word in a post with a # character. You can see hashtags used on Hai, and all the posts using them, on this page.</li>
<li> The <a href="/index.php?tab=Everything" style="font-weight: bold">Everything</a> page combines all your Teams and Waves into one stream of posts. This page displays all posts by anyone you've added to a Team or sent to you as a Wave.</li>
<li> You can <a href="/search.php" style="font-weight: bold">search</a> all public posts (including those written by people you haven't added to Teams).</li>
<li> <a href="/account.php" style="font-weight: bold">Account</a> lets you change your name, profile picture, associated email addresses and/or phone numbers, <em>etc</em>. You can also export your account as an XML file or delete your account from this page.</li>
<li> Click the <a href="/logout.php" style="font-weight: bold">Logout</a> link to log out of your account.</li>
</ul>

<h2>Rooms</h2>
<ul>
<li> Each room has a <strong>name</strong> (which must be unique across Hai), and can have a <strong>topic</strong> displayed at the top of the room's page.</li>
<li> If a room is <strong>public</strong>, its posts are visible on the web. If not, only those logged into Hai can view its posts.</li>
<li> If a room is <strong>hidden</strong>, it is not displayed in room lists or searches.</li>
<Li> If a room is <strong>invite-only</strong>, nobody can join this room on their own. Only moderators of that room can add members.</li>
<li> A room can have a <strong>password</strong>, in which case a user must enter that password to join the room.</li>
<li> A post in a room can be <strong>sticky</strong> (if you moderate a room, hover over a post in the room and click "Sticky" in the action bar at the bottom). A sticky post will always appear at the top of the room's stream of posts.</li>
</ul>

<p>You have many options for <a href="/formatting.php">formatting your posts</a>.</p>

<?php
require_once( "footer.php" );
?>
