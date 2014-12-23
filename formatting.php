<?php
$page_title = "Formatting Posts";
require_once( "header.php" );

displayNavbar( $db, $userID );

?>
<h1>Formatting Posts</h1>
<table border="0">
	<tr>
		<th></th>
		<th>Code</th>
		<th>Result</th>
	</tr>
	<tr>
		<td class="label">Italics</td>
		<td>Books like ''Dune'', *Starship Troopers*, _Foundation_, [i]The Martian Chronicles[/i], &lt;i&gt;Ringworld&lt;/i&gt;, and &lt;em&gt;Ender's Game&lt;/em&gt;.</td>
		<td>Books like <em>Dune</em>, <em>Starship Troopers</em>, <em>Foundation</em>, <em>The Martian Chronicles</em>, <em>Ringworld</em>, and <em>Ender's Game</em>.</td>
	</tr>
	<tr>
		<td class="label">Bold</td>
		<td>It is '''very''' **important** to __not__ press the [b]energize[/b] button, or &lt;b&gt;death&lt;/b&gt; is &lt;strong&gt;likely&lt;/strong&gt;.</td>
		<td>It is <strong>very</strong> <strong>important</strong> to <strong>not</strong> press the <strong>energize</strong> button, or <strong>death</strong> is <strong>likely</strong>.</p>
	</tr>
	<tr>
		<td class="label">Underline</td>
		<td>I prefer to [u]underline[/u] &lt;u&gt;things&lt;/u&gt;.</td>
		<td>I prefer to <span style="text-decoration: underline">underline</span> <span style="text-decoration: underline">things</span>.</td>
	</tr>
	<tr>
		<td class="label">Color</td>
		<td>They had [color=blue]blue-in-blue[/color] eyes.</td>
		<td>They had <span style="color: blue">blue-in-blue</span> eyes.</td>
	</tr>
	<tr>
		<td class="label">Size</td>
		<td>Stuart [size=9]Little[/size].</td>
		<td>Stuart <span style="font-size: 9pt">Little</span>.</td>
	</tr>
	<tr>
		<td class="label">Font</td>
		<td>[font=Verdana]Shiny.[/font]</td>
		<td><span style="font-family: Verdana">Shiny.</span></td>
	</tr>
	<tr>
		<td class="label">Align</td>
		<td><div>[align=left]Aligned left.[/align]</div>
		    <div>[align=center]Aligned center.[/align]</div>
		    <div>[align=right]Aligned right.[/align]</div></td>
		<td><div style="text-align: left">Aligned left.</div>
		    <div style="text-align: center">Aligned center.</div>
		    <div style="text-align: right">Aligned right.</div></td>
	</tr>
	<tr>
		<td class="label">Indent</td>
		<td>[indent]I must not fear.<br />Fear is the mind-killer.<br />Fear is the little-death that brings total obliteration.[/indent]</td>
		<td><div style="padding-left: 25px">I must not fear.<br />Fear is the mind-killer.<br />Fear is the little-death that brings total obliteration.</div></td>
	</tr>
	<tr>
		<td class="label">Links</td>
		<td>http://hai.social<br />
		    &lt;a href="http://crunchyroll.com"&gt;Crunchyroll&lt;/a&gt;<br />
		    [URL]https://google.com[/URL]<br />
			[URL=http://hai.social]Hai[/URL]</td>
		<td><a href="http://hai.social">hai.social</a><br />
		    <a href="http://www.crunchyroll.com">Crunchyroll</a><br />
		    <a href="https://google.com">google.com</a><br />
			<a href="http://hai.social">Hai</a></td>
	</tr>
	<tr>
		<td class="label">Images</td>
		<td>http://hai.social/assets/images/moon.jpg<br />
		    http://hai.social/assets/images/moon.jpg|100<br />
		    [IMG]http://hai.social/assets/images/moon.jpg[/IMG]<br />
		    &lt;img src="http://hai.social/assets/images/moon.jpg"&gt;</td>
		<td><img src="http://hai.social/assets/images/moon.jpg" /><br />
		    <img src="http://hai.social/assets/images/moon.jpg" width="100" /><br />
		    <img src="http://hai.social/assets/images/moon.jpg" /><br />
		    <img src="http://hai.social/assets/images/moon.jpg" /></td>
	</tr>
	<tr>
		<td class="label">YouTube</td>
		<td style="white-space: nowrap">https://www.youtube.com/watch?v=qAHSUTB5BJc</td>
		<td><iframe type="text/html" width="200" height="130" src="https://www.youtube.com/embed/qAHSUTB5BJc" frameborder="0"></iframe></td>
	</tr>
	<tr>
		<td class="label">Email</td>
		<td>[email]me@example.com[/email]</td>
		<td><a href="mailto:me@example.com">me@example.com</a></td>
	</tr>
	<tr>
		<td class="label">Hashtags</td>
		<td>#awesomephoto</td>
		<td><a href="hashtag.php?tag=awesomephoto">#awesomephoto</a></td>
	</tr>
	<tr>
		<td class="label">Lists</td>
		<td>
			* Bulleted list<br />
			* with multiple items<br />
			** and sub-items<br />
			<br />
			# Numbered lists<br />
			## with sub-items<br />
			1 Which can have any digits<br />
			5 As items<br />
		</td>
		<td><ul>
		    <li> Bulleted list</li>
			<li> with multiple items</li>
			  <ul><li> and sub-items</li></ul>
			</ul>
			<ol>
			<li> Numbered lists</li>
			  <ol><li> with sub-items</li></ol>
			<li> Which can have any digits</li>
			<li> As items</li>
			</ul></td>
	</tr>
	<tr>
		<td class="label">Code</td>
		<td>You can [CODE]embed code words[/CODE] or longer samples:<br />
		    [code]<br />
		    PRINT "WELCOME TO THE CITADEL"<br />
			PRINT "WHAT IS YOUR NAME?"<br />
			INPUT N<br />
			PRINT "WELCOME, "; N<br />
			[/code]</td>
		<td>You can <span class="style-code-snippet">embed code words</span> or longer samples:<br />
			<div class="style-code-block">Code:
<span class="code-line-number">001</span> PRINT "WELCOME TO THE CITADEL"
<span class="code-line-number">002</span> PRINT "WHAT IS YOUR NAME?"
<span class="code-line-number">003</span> INPUT N
<span class="code-line-number">004</span> PRINT "WELCOME, "; N
</div>
		</td></tr>
	<tr>
		<td class="label">Dice</td>
		<td>[roll]d6[/roll]<br />
		    [roll]1d6[/roll]<br />
		    [roll]2d8+4[/roll]<br />
		    [roll]2d6-2d6[/roll]<br />
		    [roll]4dF[/roll] (Fudge dice, -1 to 1)<br />
		    [roll]1d%[/roll] (percentile, 1 to 100)<br />
		    [roll]2d8e[/roll] (max rolls "explode")<br />
		    [roll]3d10p[/roll] (print each roll)<br />
			Alternately: [roll 2d6]<br />
		</td>
		<td><?php echo formatPost( processDieRoll( array("[roll d6]") ) ); ?><br />
			<?php echo formatPost( processDieRoll( array("[roll 1d6]") ) ); ?><br />
			<?php echo formatPost( processDieRoll( array("[roll 2d8+4]") ) ); ?><br />
			<?php echo formatPost( processDieRoll( array("[roll 2d6-2d6]") ) ); ?><br />
			<?php echo formatPost( processDieRoll( array("[roll 4dF]") ) ); ?> (Fudge dice, -1 to 1)<br />
			<?php echo formatPost( processDieRoll( array("[roll 1d%]") ) ); ?> (percentile, 1 to 100)<br />
			<?php echo formatPost( processDieRoll( array("[roll 2d8e]") ) ); ?> (max rolls "explode")<br />
			<?php echo formatPost( processDieRoll( array("[roll 3d10p]") ) ); ?> (print each roll)<br />
			Alternately: <?php echo formatPost( processDieRoll( array("[roll 2d6]") ) ); ?><br />
		</td>
	</tr>
</table>

<p>All HTML (like &lt;strong&gt;) and bracketed BBcode (like [CODE]) can be upper-case or lower-case.</p>

<?php
require_once( "footer.php" );
?>
