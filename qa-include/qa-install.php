<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-install.php
	Version: 1.0-beta-1
	Date: 2010-02-04 14:10:15 GMT


	This software is licensed for use in websites which are connected to the
	public world wide web and which offer unrestricted access worldwide. It
	may also be freely modified for use on such websites, so long as a
	link to http://www.question2answer.org/ is displayed on each page.


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

	require_once QA_INCLUDE_DIR.'qa-db.php';
	require_once QA_INCLUDE_DIR.'qa-db-install.php';
	
	global $qa_db;
	
	$success='';
	$error='';
	$buttons=array();
	$fields=array();
	$fielderrors=array();

	if (isset($pass_failure_type)) { // this page requested due to query failure
		switch ($pass_failure_type) {
			case 'connect':
				$error.='Could not establish database connection. Please check the username, password and hostname in the config file, and if necessary set up the appropriate MySQL user and privileges.';
				break;
			
			case 'select':
				$error.='Could not switch to the Question2Answer database. Please check the database name in the config file, and if necessary create the database in MySQL and grant appropriate user privileges.';
				break;
				
			case 'query':
				$error.="Question2Answer query failed:\n\n".$pass_failure_query."\n\nError ".$pass_failure_errno.":\n\n".$pass_failure_error."\n\n";
				break;
		}

	} else { // this page requested by user GET/POST
		if (qa_clicked('create')) {
			qa_db_install_tables($qa_db);
			
			if (QA_EXTERNAL_USERS)
				$success.='Your Question2Answer database has been created for external user identity management. Please read the documentation to complete integration.';
			else
				$success.='Your Question2Answer database has been created.';
		}

		if (qa_clicked('repair')) {
			qa_db_install_tables($qa_db);
			$success.='The Question2Answer database tables have been repaired.';
		}		

		if (qa_clicked('super')) {
			require_once QA_INCLUDE_DIR.'qa-db-users.php';
	
			$inemail=qa_post_text('email');
			$inpassword=qa_post_text('password');
			$inhandle=qa_post_text('handle');
			
			$fielderrors=array_merge(
				qa_handle_email_validate($qa_db, $inhandle, $inemail),
				qa_password_validate($inpassword)
			);
			
			if (empty($fielderrors)) {
				require_once QA_INCLUDE_DIR.'qa-app-users.php';
				
				$userid=qa_create_new_user($qa_db, $inemail, $inpassword, $inhandle);
				qa_db_user_set($qa_db, $userid, 'level', QA_USER_LEVEL_SUPER);
				qa_set_logged_in_user($qa_db, $userid);
				
				qa_set_option($qa_db, 'feedback_email', $inemail);
				
				$success.='Congratulations. Your Question2Answer site is ready to go, and you are logged in as the super administrator.';
			}
		}
	}
	
	if (isset($qa_db)) {
		$check=qa_db_check_tables($qa_db);
		
		switch ($check) {
			case 'none':
				if (@$pass_failure_errno==1146) // don't show error if we're in installation process
					$error='';
					
				$error.='Welcome to Question2Answer. Your database needs to be created.';

				if (QA_EXTERNAL_USERS) {
					$error.="\n\nWhen you click below, your Question2Answer site will be set up to integrate with your existing user database and management. Users will be referenced with database column type ".qa_get_mysql_user_column_type().". Please consult the documentation for more information.";
					$buttons=array('create' => 'Create Database');
				} else {
					$error.="\n\nWhen you click below, your Question2Answer site will be set up to manage user identities and logins internally.\n\nIf you want to offer a single sign-on for an existing user base or website, please consult the documentation before proceeding.";
					$buttons=array('create' => 'Create Database including User Management');
				}
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
					$error.='No users currently in the Question2Answer database. Please enter your details below to create the super administrator:';
					$fields=array('email' => 'Email address:', 'password' => 'Password:', 'handle' => 'Username:');
					$buttons=array('super' => 'Create Super Administrator');
				}
				break;
		}
	}
	
	header('Content-type: text/html; charset=utf-8');

?>
<HTML>
	<HEAD>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8">
	</HEAD>
	<BODY>
<?php
	if (strlen($success))
		echo '<FONT COLOR="#009900" SIZE="+1">'.nl2br(qa_html(@$success)).'</FONT><P>';
		
	if (strlen($error))
		echo '<FONT COLOR="#CC0000" SIZE="+1">'.nl2br(qa_html(@$error)).'</FONT><P>';

	else {
		require_once QA_INCLUDE_DIR.'qa-app-users.php';
		
		$qa_login_user=qa_get_logged_in_user($qa_db);
		
		if ($qa_login_user['level']>=QA_USER_LEVEL_ADMIN)
			echo '<A HREF="'.qa_path_html('admin', null, null, false).'">Go to admin page</A><P>';
		else
			echo '<A HREF="'.qa_path_html('', null, null, false).'">Go to home page</A><P>';
	}

?>
		<FORM METHOD="POST" ACTION="<?php echo qa_path_html('install', null, null, false)?>">

<?php
	foreach ($fields as $name => $prompt) {
		echo qa_html($prompt).' <INPUT TYPE="text" NAME="'.qa_html($name).'" VALUE="'.qa_html(@${'in'.$name}).'">';
		if (isset($fielderrors[$name]))
			echo ' <FONT COLOR="#990000">'.qa_html($fielderrors[$name]).'</FONT>';
		echo '<BR>';
	}
	
	echo '<P>';

	foreach ($buttons as $name => $value)
		echo '<INPUT TYPE="submit" NAME="'.qa_html($name).'" VALUE="'.qa_html($value).'">';
?>
		</FORM>
	</BODY>
</HTML>
<?php
	exit;
?>