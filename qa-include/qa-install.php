<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-install.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: User interface for installing, upgrading and fixing the database


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}

	require_once QA_INCLUDE_DIR.'qa-db.php';
	require_once QA_INCLUDE_DIR.'qa-db-install.php';
	

	if (!function_exists('qa_install_db_fail_handler')) {

		function qa_install_db_fail_handler($type, $errno=null, $error=null, $query=null)
	/*
		Handler function for database failures during the installation process
	*/
		{
			global $pass_failure_from_install;
			
			$pass_failure_type=$type;
			$pass_failure_errno=$errno;
			$pass_failure_error=$error;
			$pass_failure_query=$query;
			$pass_failure_from_install=true;
			
			require QA_INCLUDE_DIR.'qa-install.php';
			
			exit;
		}
		
	}
	

	global $qa_db; // this file could be included from inside qa_db_fail_handler() function, so ensure we can access this

	header('Content-type: text/html; charset=utf-8');

	$success='';
	$error='';
	$suggest='';
	$buttons=array();
	$fields=array();
	$fielderrors=array();

	if (isset($pass_failure_type)) { // this page was requested due to query failure, via the fail handler
		switch ($pass_failure_type) {
			case 'connect':
				$error.='Could not establish database connection. Please check the username, password and hostname in the config file, and if necessary set up the appropriate MySQL user and privileges.';
				break;
			
			case 'select':
				$error.='Could not switch to the Question2Answer database. Please check the database name in the config file, and if necessary create the database in MySQL and grant appropriate user privileges.';
				break;
				
			case 'query':
				global $pass_failure_from_install;
				
				if (@$pass_failure_from_install)
					$error.="Question2Answer was unable to perform the installation query below. Please check the user in the config file has CREATE and ALTER permissions:\n\n";
				else
					$error.="Question2Answer query failed:\n\n";
					
				$error.=$pass_failure_query."\n\nError ".$pass_failure_errno.": ".$pass_failure_error."\n\n";
				break;
		}

	} else { // this page was requested by user GET/POST, so handle any incoming clicks on buttons
		qa_base_db_connect('qa_install_db_fail_handler');
		
		if (qa_clicked('create')) {
			qa_db_install_tables($qa_db);
			
			if (QA_EXTERNAL_USERS)
				$success.='Your Question2Answer database has been created for external user identity management. Please read the documentation to complete integration.';
			else
				$success.='Your Question2Answer database has been created.';
		}
		
		if (qa_clicked('upgrade')) {
			qa_db_upgrade_tables($qa_db);
			$success.='Your Question2Answer database has been updated.';
		}

		if (qa_clicked('repair')) {
			qa_db_install_tables($qa_db);
			$success.='The Question2Answer database tables have been repaired.';
		}

		if (qa_clicked('super')) {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
			require_once QA_INCLUDE_DIR.'qa-app-users-edit.php';
	
			$inemail=qa_post_text('email');
			$inpassword=qa_post_text('password');
			$inhandle=qa_post_text('handle');
			
			$fielderrors=array_merge(
				qa_handle_email_validate($qa_db, $inhandle, $inemail),
				qa_password_validate($inpassword)
			);
			
			if (empty($fielderrors)) {
				require_once QA_INCLUDE_DIR.'qa-app-users.php';
				
				$userid=qa_create_new_user($qa_db, $inemail, $inpassword, $inhandle, QA_USER_LEVEL_SUPER);
				qa_set_logged_in_user($qa_db, $userid, $inhandle);
				
				qa_set_option($qa_db, 'feedback_email', $inemail);
				
				$success.="Congratulations - Your Question2Answer site is ready to go!\n\nYou are logged in as the super administrator and can start changing settings.\n\nThank you for installing Question2Answer.";
			}
		}
	}
	
	if (isset($qa_db) && !@$pass_failure_from_install) {
		$check=qa_db_check_tables($qa_db); // see where the database is at
		
		switch ($check) {
			case 'none':
				if (@$pass_failure_errno==1146) // don't show error if we're in installation process
					$error='';
					
				$error.='Welcome to Question2Answer. It\'s time to set up your database!';

				if (QA_EXTERNAL_USERS) {
					$error.="\n\nWhen you click below, your Question2Answer site will be set up to integrate with your existing user database and management. Users will be referenced with database column type ".qa_get_mysql_user_column_type().". Please consult the documentation for more information.";
					$buttons=array('create' => 'Create Database');
				} else {
					$error.="\n\nWhen you click below, your Question2Answer database will be set up to manage user identities and logins internally.\n\nIf you want to offer a single sign-on for an existing user base or website, please consult the documentation before proceeding.";
					$buttons=array('create' => 'Create Database including User Management');
				}
				break;
				
			case 'old-version':
				if (!@$pass_failure_from_install)
					$error=''; // don't show error if we need to upgrade
					
				$error.='Your Question2Answer database needs to be upgraded for this version of the software.'; // don't show error before this
				$buttons=array('upgrade' => 'Upgrade Database');
				break;
				
			case 'table-missing':
				$error.='One or more tables are missing from your Question2Answer database.';
				$buttons=array('repair' => 'Repair Database');
				break;
				
			case 'column-missing':
				$error.='One or more Question2Answer database tables are missing a column.';
				$buttons=array('repair' => 'Repair Database');
				break;
				
			default:
				require_once QA_INCLUDE_DIR.'qa-db-admin.php';

				if ( (!QA_EXTERNAL_USERS) && (qa_db_count_users($qa_db)==0) ) {
					$error.="There are currently no users in the Question2Answer database.\n\nPlease enter your details below to create the super administrator:";
					$fields=array('handle' => 'Username:', 'password' => 'Password:', 'email' => 'Email address:');
					$buttons=array('super' => 'Create Super Administrator');
				}
				break;
		}
	}
	
	if (empty($error)) {
		if (empty($success))
			$success='Your Question2Answer database has been checked with no problems.';
		
		$suggest='<A HREF="'.qa_path_html('admin', null, null, QA_URL_FORMAT_SAFEST).'">Go to admin center</A>';
	}

?>
<HTML>
	<HEAD>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8">
		<STYLE type="text/css">
			body,input {font-size:16px; font-family:Verdana, Arial, Helvetica, sans-serif;}
			body {text-align:center; width:640px; margin:64px auto;}
			table {margin: 16px auto;}
		</STYLE>
	</HEAD>
	<BODY>
		<FORM METHOD="POST" ACTION="<?php echo qa_path_html('install', null, null, QA_URL_FORMAT_SAFEST)?>">
<?php
	if (strlen($success))
		echo '<P><FONT COLOR="#006600">'.nl2br(qa_html($success)).'</FONT></P>'; // green
		
	if (strlen($error))
		echo '<P><FONT COLOR="#990000">'.nl2br(qa_html($error)).'</FONT></P>'; // red
		
	if (strlen($suggest))
		echo '<P>'.$suggest.'</P>';


//	Very simple general form display logic (we don't use theme since it depends on tons of DB options)

	if (count($fields)) {
		echo '<TABLE>';
		
		foreach ($fields as $name => $prompt) {
			echo '<TR><TD>'.qa_html($prompt).'</TD><TD><INPUT TYPE="text" SIZE="24" NAME="'.qa_html($name).'" VALUE="'.qa_html(@${'in'.$name}).'"></TD>';
			if (isset($fielderrors[$name]))
				echo '<TD><FONT COLOR="#990000"><SMALL>'.qa_html($fielderrors[$name]).'</SMALL></FONT></TD>';
			echo '</TR>';
		}
		
		echo '</TABLE>';
	}
	
	foreach ($buttons as $name => $value)
		echo '<INPUT TYPE="submit" NAME="'.qa_html($name).'" VALUE="'.qa_html($value).'">';
?>
		</FORM>
	</BODY>
</HTML>
<?php
	
	qa_base_db_disconnect();


/*
	Omit PHP closing tag to help avoid accidental output
*/