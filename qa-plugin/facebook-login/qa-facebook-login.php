<?php

/*
	Question2Answer 1.3-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-plugin/facebook-login/qa-facebook-login.php
	Version: 1.3-beta-2
	Date: 2010-11-11 10:26:02 GMT
	Description:


	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	More about this license: http://www.question2answer.org/license.php
*/


	class qa_facebook_login {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		function check_cookie()
		{
			// Based on sample code: http://developers.facebook.com/docs/guides/web
			
			$testfacebook=false;
			
			foreach ($_COOKIE as $key => $value)
				if (substr($key, 0, 4)=='fbs_')
					$testfacebook=true;
					
			if (!$testfacebook) // to save making a database query for qa_opt() if there's no point
				return;
			
			$app_id=qa_opt('facebook_app_id');
			$app_secret=qa_opt('facebook_app_secret');
			
			if (!(strlen($app_id) && strlen($app_secret)))
				return;
			
			if (isset($_COOKIE['fbs_'.$app_id])) {
				$args = array();
				parse_str(trim($_COOKIE['fbs_'.$app_id], '\\"'), $args);
				ksort($args);

				$payload = '';
				foreach ($args as $key => $value)
					if ($key != 'sig')
						$payload.=$key.'='.$value;
						
				if (md5($payload.qa_opt('facebook_app_secret'))==$args['sig']) {
					$url='https://graph.facebook.com/me?access_token='.$args['access_token'].'&fields=picture';
					$rawuser=''; //file_get_contents($url);
					
					if ((!strlen($rawuser)) && function_exists('curl_exec')) { // try curl as a backup (if allow_url_fopen not set)
						$curl=curl_init($url);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
						$rawuser=curl_exec($curl);
						curl_close($curl);
					}
					
					if (strlen($rawuser)) {
						require_once $this->directory.'JSON.php';
						
						$json=new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
						$user=$json->decode($rawuser);
						
						if (is_array($user))
							qa_log_in_external_user('facebook', $args['uid'], array(
								'email' => @$user['email'],
								'handle' => @$user['name'],
								'confirmed' => @$user['verified'],
								'name' => @$user['name'],
								'location' => @$user['location']['name'],
								'website' => @$user['website'],
								'about' => @$user['about'],
								'avatar' => strlen(@$user['picture']) ? file_get_contents($user['picture']) : null,
							));

					}
				}
			}
		}
		
		function match_source($source)
		{
			return $source=='facebook';
		}
		
		function login_html($tourl, $context)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;

?>		
<div id="fb-root"></div>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
	FB.init({appId: <?php echo qa_js($app_id)?>, status: true, cookie: true, xfbml: true});
	FB.Event.subscribe('auth.sessionChange', function(response) {
		window.location=<?php echo qa_js($tourl)?>;
	});
</script>
<fb:login-button perms="email,user_about_me,user_website"></fb:login-button>
<?php

		}
		
		function logout_html($tourl)
		{
			$app_id=qa_opt('facebook_app_id');

			if (!strlen($app_id))
				return;

?>		
<span id="fb-root"></span>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
	FB.init({appId: <?php echo qa_js($app_id)?>, status: true, cookie: true, xfbml: true});
	FB.Event.subscribe('auth.sessionChange', function(response) {
		window.location=<?php echo qa_js($tourl)?>;
	});
</script>
<fb:login-button autologoutlink="true"></fb:login-button>
<?php

		}
		
		function admin_form()
		{
			$saved=false;
			
			if (qa_clicked('facebook_save_button')) {
				qa_opt('facebook_app_id', qa_post_text('facebook_app_id_field'));
				qa_opt('facebook_app_secret', qa_post_text('facebook_app_secret_field'));
				$saved=true;
			}
			
			$ready=strlen(qa_opt('facebook_app_id')) && strlen(qa_opt('facebook_app_secret'));
			
			return array(
				'ok' => $saved ? 'Facebook application details saved' : null,
				
				'fields' => array(
					array(
						'label' => 'Your Facebook App ID:',
						'value' => qa_html(qa_opt('facebook_app_id')),
						'tags' => 'NAME="facebook_app_id_field"',
					),

					array(
						'label' => 'Your Facebook App Secret:',
						'value' => qa_html(qa_opt('facebook_app_secret')),
						'tags' => 'NAME="facebook_app_secret_field"',
						'error' => $ready ? null : 'To use Facebook Login, please <A HREF="http://developers.facebook.com/setup/" TARGET="_blank">set up a Facebook application</A>.',
					),
				),
				
				'buttons' => array(
					array(
						'label' => 'Save Changes',
						'tags' => 'NAME="facebook_save_button"',
					),
				),
			);
		}
		
	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/