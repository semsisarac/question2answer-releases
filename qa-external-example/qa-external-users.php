<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-external-example/qa-external-users.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Example of how to integrate with your own user database


	This software is free to use and modify for public websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page. It may
	not be redistributed or resold, nor may any works derived from it.
	
	More about this license: http://www.question2answer.org/license.php


	THIS SOFTWARE IS PROVIDED "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL
	THE COPYRIGHT HOLDER BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
	SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
	TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
	PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
	LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
	NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

/*
	=========================================================================
	THIS FILE ALLOWS YOU TO INTEGRATE WITH AN EXISTING USER MANAGEMENT SYSTEM
	=========================================================================

	It is used if QA_EXTERNAL_USERS is set to true in qa-config.php.
*/

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_get_mysql_user_column_type()
	{
/*
	==========================================================================
	      YOU MUST MODIFY THIS FUNCTION *BEFORE* QA CREATES ITS DATABASE
	==========================================================================

	You should return the appropriate MySQL column type to use for the userid,
	for smooth integration with your existing users. Allowed options are:
	
	SMALLINT, SMALLINT UNSIGNED, MEDIUMINT, MEDIUMINT UNSIGNED, INT, INT UNSIGNED,
	BIGINT, BIGINT UNSIGNED or VARCHAR(x) where x is the maximum length.
*/

	//	Set this before anything else

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers with a maximum length of 32

		return 'VARCHAR(32)';
	*/
	
	/*
		Example 2 - suitable if:
		
		* You use unsigned numerical user identifiers in an UNSIGNED INT column
		
		return 'UNSIGNED INT';
	*/
	}


	function qa_get_login_links($relative_url_prefix, $redirect_back_to_url)
/*
	==========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER QA CREATES ITS DATABASE
	==========================================================================

	You should return an array containing URLs for the login, register and logout pages on
	your site. These URLs will be used as appropriate within the QA site.
	
	You may return absolute or relative URLs for each page. If you do not want one of the links
	to show, omit it from the array, or use null or an empty string.
	
	If you use absolute URLs, then return an array with the URLs in full (see example 1 below).

	If you use relative URLs, the URLs should start with $relative_url_prefix, followed by the
	relative path from the root of the QA site to your login page. Like in example 2 below, if
	the QA site is in a subdirectory, $relative_url_prefix.'../' refers to your site root.
	
	Now, about $redirect_back_to_url. Let's say a user is viewing a page on the QA site, and
	clicks a link to the login URL that you returned from this function. After they log in using
	the form on your main site, they want to automatically go back to the page on the QA site
	where they came from. This can be done with an HTTP redirect, but how does your login page
	know where to redirect the user to? The solution is $redirect_back_to_url, which is the URL
	of the page on the QA site where you should send the user once they've successfully logged
	in. To implement this, you can add $redirect_back_to_url as a parameter to the login URL
	that you return from this function. Your login page can then read it in from this parameter,
	and redirect the user back to the page after they've logged in. The same applies for your
	register and logout pages. Note that the URL you are given in $redirect_back_to_url is
	relative to the root of the QA site, so you may need to add something.
*/
	{

	//	Until you edit this function, don't show login, register or logout links

		return array(
			'login' => null,
			'register' => null,
			'logout' => null
		);

	/*
		Example 1 - using absolute URLs, suitable if:
		
		* Your QA site:        http://qa.mysite.com/
		* Your login page:     http://www.mysite.com/login
		* Your register page:  http://www.mysite.com/register
		* Your logout page:    http://www.mysite.com/logout
		
		return array(
			'login' => 'http://www.mysite.com/login',
			'register' => 'http://www.mysite.com/register',
			'logout' => 'http://www.mysite.com/logout',
		);
		
	*/
	
	/*
		Example 2 - using relative URLs, suitable if:
		
		* Your QA site:        http://www.mysite.com/qa/
		* Your login page:     http://www.mysite.com/login.php
		* Your register page:  http://www.mysite.com/register.php
		* Your logout page:    http://www.mysite.com/logout.php
	
		return array(
			'login' => $relative_url_prefix.'../login.php',
			'register' => $relative_url_prefix.'../register.php',
			'logout' => $relative_url_prefix.'../logout.php',
		);
	*/
		
	/*
		Example 3 - using relative URLs, and implementing $redirect_back_to_url
		
		In this example, your pages login.php, register.php and logout.php should read in the
		parameter $_GET['redirect'], and redirect the user to the page specified by that
		parameter once they have successfully logged in, registered or logged out.
		
		return array(
			'login' => $relative_url_prefix.'../login.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
			'register' => $relative_url_prefix.'../register.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
			'logout' => $relative_url_prefix.'../logout.php?redirect='.urlencode('qa/'.$redirect_back_to_url),
		);
	*/

	}
	

	function qa_get_logged_in_user($qa_db_connection)
/*
	==========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER QA CREATES ITS DATABASE
	==========================================================================

	qa_get_logged_in_user($qa_db_connection)
	
	You should check (using $_COOKIE, $_SESSION or whatever is appropriate) whether a user is
	currently logged in. If not, return null. If so, return an array with the following elements:

	* userid: a user id appropriate for your response to qa_get_mysql_user_column_type()
	* publicusername: a user description you are willing to show publicly, e.g. the username
	* email: the logged in user's email address
	* level: one of the QA_USER_LEVEL_* values below to denote the user's privileges:
	
	QA_USER_LEVEL_BASIC, QA_USER_LEVEL_EDITOR, QA_USER_LEVEL_ADMIN, QA_USER_LEVEL_SUPER
	
	The result of this function will be passed to your other function qa_get_logged_in_user_html()
	so you may add any other elements to the returned array if they will be useful to you.

	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
	
	In order to access the admin interface of your QA site, ensure that the array element 'level'
	contains QA_USER_LEVEL_ADMIN or QA_USER_LEVEL_SUPER when you are logged in.
*/
	{
	
	//	Until you edit this function, nobody is ever logged in
	
		return null;
		
	/*
		Example 1 - suitable if:
		
		* You store the login state and user in a PHP session
		* You use textual user identifiers that also serve as public usernames
		* Your database is shared with the QA site
		* Your database has a users table that contains emails
		* The administrator has the user identifier 'admin'
		
		session_start();

		if ($_SESSION['is_logged_in']) {
			$userid=$_SESSION['logged_in_userid'];

			$result=mysql_fetch_assoc(
				mysql_query(
					"SELECT email FROM users WHERE userid='".mysql_real_escape_string($userid, $qa_db_connection)."'",
					$qa_db_connection
				)
			);
			
			if (is_array($result))
				return array(
					'userid' => $userid,
					'publicusername' => $userid,
					'email' => $result['email'],
					'level' => ($userid=='admin') ? QA_USER_LEVEL_ADMIN : QA_USER_LEVEL_BASIC
				);
		}
		
		return null;
	*/
	
	/*
		Example 2 - suitable if:
		
		* You store a session ID inside a cookie
		* You use numerical user identifiers
		* Your database is shared with the QA site
		* Your database has a sessions table that maps session IDs to users
		* Your database has a users table that contains usernames, emails and a flag for admin privileges
		
		if ($_COOKIE['sessionid']) {
			$result=mysql_fetch_assoc(
				mysql_query(
					"SELECT userid, username, email, admin_flag FROM users WHERE userid=".
					"(SELECT userid FROM sessions WHERE sessionid='".mysql_real_escape_string($_COOKIE['session_id'], $qa_db_connection)."')",
					$qa_db_connection
				)
			);
			
			if (is_array($result))
				return array(
					'userid' => $result['userid'],
					'publicusername' => $result['username'],
					'email' => $result['email'],
					'level' => $result['admin_flag'] ? QA_USER_LEVEL_ADMIN : QA_USER_LEVEL_BASIC
				);
		}
		
		return null;
	*/
		
	}

	
	function qa_get_user_email($qa_db_connection, $userid)
/*
	==========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER QA CREATES ITS DATABASE
	==========================================================================

	qa_get_user_email($qa_db_connection, $userid)
	
	Return the email address for user $userid, or null if you don't know it.
	
	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* Your database is shared with the QA site
		* Your database has a users table that contains emails
		
		$result=mysql_fetch_assoc(
			mysql_query(
				"SELECT email FROM users WHERE userid='".mysql_real_escape_string($userid, $qa_db_connection)."'",
				$qa_db_connection
			)
		);
		
		if (is_array($result))
			return $result['email'];
		
		return null;
	*/

	}
	

	function qa_get_userids_from_public($qa_db_connection, $publicusernames)
/*
	==========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER QA CREATES ITS DATABASE
	==========================================================================

	qa_get_userids_from_public($qa_db_connection, $publicusernames)
	
	You should take the array of public usernames in $publicusernames, and return an array which
	maps those usernames to internal user ids. For each element of this array, the username you
	were given should be in the key, with the corresponding user id in the value.
	
	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers that are also shown publicly

		$publictouserid=array();
		
		foreach ($publicusernames as $publicusername)
			$publictouserid[$publicusername]=$publicusername;
		
		return $publictouserid;
	*/

	/*
		Example 2 - suitable if:
		
		* You use numerical user identifiers
		* Your database is shared with the QA site
		* Your database has a users table that contains usernames
		
		$publictouserid=array();
			
		if (count($publicusernames)) {
			$escapedusernames=array();
			foreach ($publicusernames as $publicusername)
				$escapedusernames[]="'".mysql_real_escape_string($publicusername, $qa_db_connection)."'";
			
			$results=mysql_query(
				'SELECT username, userid FROM users WHERE username IN ('.implode(',', $escapedusernames).')',
				$qa_db_connection
			);
	
			while ($result=mysql_fetch_assoc($results))
				$publictouserid[$result['username']]=$result['userid'];
		}
		
		return $publictouserid;
	*/

	}


	function qa_get_public_from_userids($qa_db_connection, $userids)
/*
	==========================================================================
	YOU MUST MODIFY THIS FUNCTION, BUT CAN DO SO AFTER QA CREATES ITS DATABASE
	==========================================================================

	qa_get_public_from_userids($qa_db_connection, $userids)
	
	This is exactly like qa_get_userids_from_public(), but works in the other direction.
	
	You should take the array of user identifiers in $userids, and return an array which maps
	those to public usernames. For each element of this array, the userid you were given should
	be in the key, with the corresponding username in the value.
	
	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
*/
	{

	//	Until you edit this function, always return null

		return null;

	/*
		Example 1 - suitable if:
		
		* You use textual user identifiers that are also shown publicly

		$useridtopublic=array();
		
		foreach ($userids as $userid)
			$useridtopublic[$userid]=$userid;
		
		return $useridtopublic;
	*/

	/*
		Example 2 - suitable if:
		
		* You use numerical user identifiers
		* Your database is shared with the QA site
		* Your database has a users table that contains usernames
		
		$useridtopublic=array();
		
		if (count($userids)) {
			$escapeduserids=array();
			foreach ($userids as $userid)
				$escapeduserids[]="'".mysql_real_escape_string($userid, $qa_db_connection)."'";
			
			$results=mysql_query(
				'SELECT username, userid FROM users WHERE userid IN ('.implode(',', $escapeduserids).')',
				$qa_db_connection
			);
	
			while ($result=mysql_fetch_assoc($results))
				$useridtopublic[$result['userid']]=$result['username'];
		}
		
		return $useridtopublic;
	*/

	}


	function qa_get_logged_in_user_html($qa_db_connection, $logged_in_user, $relative_url_prefix)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_get_logged_in_user_html($qa_db_connection, $logged_in_user, $relative_url_prefix)

	You should return HTML code which identifies the logged in user, to be displayed next to the
	logout link on the QA pages. This HTML will only be shown to the logged in user themselves.

	$logged_in_user is the array that you returned from qa_get_logged_in_user(). Hopefully this
	contains enough information to generate the HTML without another database query, but if not,
	$qa_db_connection is an open connection to the QA database.

	$relative_url_prefix is a relative URL to the root of the QA site, which may be useful if
	you want to include a link that uses relative URLs. If the QA site is in a subdirectory of
	your site, $relative_url_prefix.'../' refers to your site root (see example 1).

	If you don't know what to display for a user, you can leave the default below. This will
	show the public username, linked to the QA profile page for the user.
*/
	{
	
	//	By default, show the public username linked to the QA profile page for the user

		$publicusername=$logged_in_user['publicusername'];
		
		return '<A HREF="'.htmlspecialchars($relative_url_prefix.'user/'.urlencode($publicusername)).
			'" CLASS="qa-user-link">'.htmlspecialchars($publicusername).'</A>';

	/*
		Example 1 - suitable if:
		
		* Your QA site:        http://www.mysite.com/qa/
		* Your user pages:     http://www.mysite.com/user/[username]
	
		$publicusername=$logged_in_user['publicusername'];
		
		return '<A HREF="'.htmlspecialchars($relative_url_prefix.'../user/'.urlencode($publicusername)).
			'" CLASS="qa-user-link">'.htmlspecialchars($publicusername).'</A>';
	*/

	/*
		Example 2 - suitable if:
		
		* Your QA site:        http://qa.mysite.com/
		* Your user pages:     http://www.mysite.com/[username]/
		* 16x16 user photos:   http://www.mysite.com/[username]/photo-small.jpeg
	
		$publicusername=$logged_in_user['publicusername'];
		
		return '<A HREF="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/" CLASS="qa-user-link">'.
			'<IMG SRC="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/photo-small.jpeg" '.
			'STYLE="width:16px; height:16px; border:none; margin-right:4px;">'.htmlspecialchars($publicusername).'</A>';
	*/

	}


	function qa_get_users_html($qa_db_connection, $userids, $should_include_link, $relative_url_prefix)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_get_users_html($qa_db_connection, $userids, $should_include_link, $relative_url_prefix)

	You should return an array of HTML to display for each user in $userids. For each element of
	this array, the userid should be in the key, with the corresponding HTML in the value.
	
	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries. If you
	access this database or any other, try to use a single query instead of one per user.
	
	If $should_include_link is true, the HTML may include links to user profile pages.
	If $should_include_link is false, links should not be included in the HTML.
	
	$relative_url_prefix is a relative URL to the root of the QA site, which may be useful if
	you want to include links that uses relative URLs. If the QA site is in a subdirectory of
	your site, $relative_url_prefix.'../' refers to your site root (see example 1).
	
	If you don't know what to display for a user, you can leave the default below. This will
	show the public username, linked to the QA profile page for each user.
*/
	{

	//	By default, show the public username linked to the QA profile page for each user

		$useridtopublic=qa_get_public_from_userids($qa_db_connection, $userids);
		
		$usershtml=array();

		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]=htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<A HREF="'.htmlspecialchars($relative_url_prefix.'user/'.urlencode($publicusername)).
					'" CLASS="qa-user-link">'.$usershtml[$userid].'</A>';
		}
			
		return $usershtml;

	/*
		Example 1 - suitable if:
		
		* Your QA site:        http://www.mysite.com/qa/
		* Your user pages:     http://www.mysite.com/user/[username]
	
		$useridtopublic=qa_get_public_from_userids($qa_db_connection, $userids);
		
		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]=htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<A HREF="'.htmlspecialchars($relative_url_prefix.'../user/'.urlencode($publicusername)).
					'" CLASS="qa-user-link">'.$usershtml[$userid].'</A>';
		}
			
		return $usershtml;
	*/

	/*
		Example 2 - suitable if:
		
		* Your QA site:        http://qa.mysite.com/
		* Your user pages:     http://www.mysite.com/[username]/
		* User photos (16x16): http://www.mysite.com/[username]/photo-small.jpeg
	
		$useridtopublic=qa_get_public_from_userids($qa_db_connection, $userids);
		
		foreach ($userids as $userid) {
			$publicusername=$useridtopublic[$userid];
			
			$usershtml[$userid]='<IMG SRC="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).'/photo-small.jpeg" '.
				'STYLE="width:16px; height:16px; border:0; margin-right:4px;">'.htmlspecialchars($publicusername);
			
			if ($should_include_link)
				$usershtml[$userid]='<A HREF="http://www.mysite.com/'.htmlspecialchars(urlencode($publicusername)).
					'/" CLASS="qa-user-link">'.$usershtml[$userid].'</A>';
		}
			
		return $usershtml;
	*/

	}


	function qa_user_report_action($qa_db_connection, $userid, $action, $questionid, $answerid, $commentid)
/*
	==========================================================================
	     YOU MAY MODIFY THIS FUNCTION, BUT THE DEFAULT BELOW WILL WORK OK
	==========================================================================

	qa_user_report_action($qa_db_connection, $userid, $action, $questionid, $answerid, $commentid)

	Informs you about an action by user $userid that modified the database, such as posting,
	voting, etc... If you wish, you may use this to log user activity or monitor for abuse.
	
	$qa_db_connection is an open connection to the QA database. If your database is shared with
	QA, you can use this with PHP's MySQL functions such as mysql_query() to run queries.
	
	$action is one of:
	q_post, q_edit, q_hide, q_reshow, q_delete, q_claim, q_vote_up, q_vote_down, q_vote_nil
	a_post, a_edit, a_hide, a_reshow, a_delete, a_claim, a_vote_up, a_vote_down, a_vote_nil, a_select, a_unselect, a_to_c
	c_post, c_edit, c_hide, c_reshow, c_delete, c_claim
	
	$questionid and/or $answerid and/or $commentid contain the ID of the relevant question or answer
	or comment affected, or null if this information is not appropriate for $action.
	
	FYI, you can get the IP address of the user from $_SERVER['REMOTE_ADDR'].
*/
	{
		// do nothing by default
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/