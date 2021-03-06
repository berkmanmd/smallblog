<?php

include("include/include.php");

// Register the session variables.
session_start();

$u = $_SESSION['username'];
$n = $_SESSION['nickname'];
$motd = '';


// Get the news from the database.
$query = "SELECT news_id, subject, username, body, posted, comment_fl, image FROM public.news ORDER BY news_id DESC";
$r = pg_query($query) or die ("Error with query");
$num_rows = pg_num_rows($r);


// Determine how many news items to display on the main page.
$query2 = "SELECT show_num FROM options";
$r2 = pg_query($query2) or die ("Error with query2");
$row = pg_fetch_array($r2);
$show_num = $row[0];

if ($num_rows < $show_num) {
	$show = $num_rows;
	$archive_link = false;
}
else {
	$show = $show_num;
	$archive_link = true;
}


// Determine who else is logged into the website.
$query3 = "SELECT username FROM public.login WHERE loggedin='y'";
$r4 = pg_query($query3) or die ("Error with query3.");
$row4 = pg_fetch_array($r4);
$logged = pg_num_rows($r4);


// Print the page header.
include ('include/header.php');


// Check for user's birthday.
$bday = "SELECT username, nickname FROM public.user WHERE date_part('day', current_timestamp) = date_part('day', dob::date) AND date_part('month', current_timestamp) = date_part('month', dob::date)";
$bresult = pg_query($bday) or die ("Error with bday query");
$todaybday = pg_fetch_array($bresult);

if ($todaybday) {
	$motd = 'Happy Birthday ' .$todaybday['nickname']. '!';
}



// Message of the day.
if (!$todaybday) {
	$query5 = "SELECT motd FROM public.options";
	$mess = pg_query($query5) or die ("Error with query5");
	$temp = pg_fetch_array($mess);

	if ($temp) {
		$motd = $temp['motd'];
	}
}


if ($motd != '') {
	echo '<p class="motd">[ '.$motd.' ]</p>';
}


// Logged in user.
if ($u != "") {
	// Retrieve the date from the last users login.
	$query3 = "SELECT last_login FROM public.login WHERE username='$u'";
	$r3 = pg_query($query3) or die ("Error with query3");
	$row3 = pg_fetch_array($r3);


	// Greet the user.
	if ($n != "")
		echo "<p>Welcome Back, " .$n. ".</p>\n";
	else
		echo "<p>Welcome Back, " .$u. ".</p>\n";

	if ($row3[0] == "")
		echo "<p>This is your first login.</p>\n";
	else
		echo "<p>You last logged in: [ " .$row3[0]. " ].<br />\n";


	// Display the other logged in users.
	if ($logged == 1)
		echo "<p>You're the only user logged in.</p>\n";
	else {
		echo "Logged in users:";
		for ($i = 0; $i < $logged; $i++) {
			if ($row4[0] != $u)
				echo " [ " .$row4[0]. " ] ";
			$row4 = pg_fetch_array($r4);
		}
		echo "</p>\n";
	}


	// Display the menu.
	echo '<p>' . "\n";
	echo '  [ <a href="post.php">Post</a> ]<br />' . "\n";
	echo '  [ <a href="options.php">Options</a> ]<br />' . "\n";
	echo '  [ <a href="logout.php">Log Out</a> ]' . "\n";
	echo '</p>' . "\n";
}


// No User logged in.
else {
	echo "<p>Welcome, Visitor.</p>\n";
	echo '<p>[ <a href="login.php">Log In</a> ]</p>' . "\n";
}


// Display the news.
for ($i = 0; $i < $show; $i++) {
	$row = pg_fetch_array($r);
	$date = $row[4];


	// Get the users nickname and e-mail.
	$query9 = "SELECT nickname, email, picture FROM public.user WHERE username='" . $row['username'] . "'";
	$r9 = pg_query($query9) or die ("Error with query9");
	$e = pg_fetch_array($r9);


	// Determine the users preference for their post name.
	$query4 = "SELECT post_name FROM public.pref WHERE username='" . $row['username'] . "'";
	$r3 = pg_query($query4) or die ("Error with query4");
	$p = pg_fetch_array($r3);


	echo '<table>' . "\n";


	// Print the user's icon, if they have one.
	if($e['picture'] != '')
		echo '  <tr><td class="subj"><img src="uploads/' .$e['picture']. '" alt="'.$row['username'].'\'s icon" border="1" /> ' . $row['subject'] . '</td></tr>' . "\n";
	else
		echo '  <tr><td class="subj">' . $row['subject'] . '</td></tr>' . "\n";


	// Print the information line for the post based on user's preference.
  	if ($p[0] == 'username') {
		if ($e[1] != "") {
			echo '  <tr><td>Posted ' .$date. ' by <a href="mailto:' .$e[1]. '">' .$row['username']. '</a></td></tr>' . "\n";
		}
		else {
			echo '  <tr><td>Posted ' .$date. ' by ' .$row['username']. '</td></tr>' . "\n";
		}
	}
	else {
		if ($e[1] != "") {
			echo '  <tr><td>Posted ' .$date. ' by <a href="mailto:' .$e[1]. '">' .$e[0]. '</a></td></tr>' . "\n";
		}
		else {
			echo '  <tr><td>Posted ' .$date. ' by ' .$e[0]. '</td></tr>' . "\n";
		}
	}

	// Print the post's body.
	echo '  <tr><td>' . "\n";
	echo '    ' . $row['body'] . "\n";
	echo '  </td></tr>' . "\n";


	// Print the post's image, if there is one.
	if ($row['image'] != "") {
		echo '  <tr><td>&nbsp;</td></tr>' . "\n";
		echo '  <tr><td><img src="uploads/' .$row['image']. '" /></td></tr>' . "\n";
		echo '  <tr><td>&nbsp;</td></tr>' . "\n";
	}


	// Print the comments link, if there are comments.
	if ($row['comment_fl'] == 'y') {
		$query_cmnt = "SELECT cmntID FROM public.comments WHERE newsID='".$row['newsID']."'";
		$cmnt_rslt = pg_query($query_cmnt) or die ("Error with query.");
		$num_cmnts = pg_num_rows($cmnt_rslt);
		echo '  <tr><td colspan="3"><a href="comments.php?newsID=' . $row['newsID'] . '">Comments ('.$num_cmnts.')</a></td></tr>' . "\n";
	}

	echo '</table>' . "\n";
	echo '<br />' . "\n";
}


// Print the archives link, if needed.
if ($archive_link)
	echo '<p>[ <a href="archive.php?s=' . $show_num . '">Archive</a> ]</p>' . "\n";


// Print the page footer.
include ('include/footer.php');

?>
