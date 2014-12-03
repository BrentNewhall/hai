hai
===

Hai is a little social platform. It's an experiment in social networking.

Hai was conceived and built by [Brent P. Newhall](mailto:brent@brentnewhall.com).

The architecture is basic LAMP (Linux, Apache, MySQL, and PHP). The PHP uses no libraries; it's all written from scratch.

Features include:

  * A robust set of post formatting options, using a restricted set of HTML, BBCode, and Markdown.
  * Anonymity. An account can consist solely of a username and password.
  * Live preview of post formatting, using JavaScript.
  * Drag-and-drop image and video uploads, using JavaScript.
  * Each post can given a topic (called a "World"), and users can subscribe to Worlds.
  * Users can create and join Rooms, which function much like IRC chatrooms. They even update live with new posts as they're posted.
  * Hashtags.
  * Password recovery via email or SMS, and users can specify multiple emails and phone numbers.
  * Passwords are stored as encrypted DES hashes, using a 36-character salt.
  * Notifications ("pings") of comments on your posts, of comments on posts you've commented on, and of posts in which you've been specifically mentioned.

You will need to create a database using the database structure described in hai\_prod.sql, and you will need to create a database configuration file named hai\_db.cfg (ideally stored outside of your web root) with the following statements:

<pre>
DEFINE( "DB\_SERVER",   "(database hostname or IP address)" );
DEFINE( "DB\_NAME",     "(database name)" );
DEFINE( "DB\_USER",     "(username)" );
DEFINE( "DB\_PASSWORD", "(password)" );

$crypt\_salt = "(some text)";
</pre>

