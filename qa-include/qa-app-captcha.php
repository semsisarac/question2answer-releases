<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-app-captcha.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Wrapper functions and utilities for reCAPTCHA


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


	function qa_captcha_pending()
/*
	Queue any option requests needed by other captcha functions
*/
	{
		qa_options_set_pending(array('recaptcha_public_key', 'recaptcha_private_key', 'site_language'));
	}
	

	function qa_captcha_possible($db)
/*
	Return true if it will be possible to present a captcha to the user, false otherwise
*/
	{
		$options=qa_get_options($db, array('recaptcha_public_key', 'recaptcha_private_key'));
		
		return function_exists('fsockopen') && strlen(trim($options['recaptcha_public_key'])) && strlen(trim($options['recaptcha_private_key']));
	}
	

	function qa_captcha_error($db)
/*
	Return string of error to display in admin interface if captchas not possible, null otherwise
*/
	{
		if (qa_captcha_possible($db))
			return null;
		
		elseif (!function_exists('fsockopen'))
			return qa_lang_html('admin/recaptcha_fsockopen');
		
		else {
			require_once QA_INCLUDE_DIR.'qa-recaptchalib.php';
	
			$url=recaptcha_get_signup_url(@$_SERVER['HTTP_HOST'], qa_get_option($db, 'site_title'));
			
			return strtr(
				qa_lang_html('admin/recaptcha_get_keys'),
				
				array(
					'^1' => '<A HREF="'.qa_html($url).'">',
					'^2' => '</A>',
				)
			);
		}
	}

	
	function qa_captcha_html($db, $error)
/*
	Return the html to display for a captcha, pass $error as returned by earlier qa_captcha_validate()
*/
	{
		require_once QA_INCLUDE_DIR.'qa-recaptchalib.php';

		return recaptcha_get_html(qa_get_option($db, 'recaptcha_public_key'), $error, qa_is_https_probably());
	}

	
	function qa_captcha_validate($db, $form, &$errors)
/*
	Check if captcha correct based on fields submitted in $form and set $errors['captcha'] accordingly
*/
	{
		if (qa_captcha_possible($db)) {
			require_once QA_INCLUDE_DIR.'qa-recaptchalib.php';
			
			if ( (!empty($form['recaptcha_challenge_field'])) && (!empty($form['recaptcha_response_field'])) ) {
				$answer=recaptcha_check_answer(
					qa_get_option($db, 'recaptcha_private_key'),
					@$_SERVER['REMOTE_ADDR'],
					@$form['recaptcha_challenge_field'],
					@$form['recaptcha_response_field']
				);
				
				if (!$answer->is_valid)
					$errors['captcha']=@$answer->error;

			} else
				$errors['captcha']=true; // empty error but still set it
		}
	}

	
	function qa_set_up_captcha_field($db, &$qa_content, &$fields, $errors, $note=null)
/*
	Prepare $qa_content for showing a captcha, adding the element to $fields,
	given previous $errors, and a $note to display
*/
	{
		if (qa_captcha_possible($db)) {
			$fields['captcha']=array(
				'type' => 'custom',
				'label' => qa_lang_html('misc/captcha_label'),
				'html' => qa_captcha_html($db, @$errors['captcha']),
				'error' => isset($errors['captcha']) ? qa_lang_html('misc/captcha_error') : null,
				'note' => $note,
			);
			
			$language=qa_get_option($db, 'site_language');
			if (strpos('|en|nl|fr|de|pt|ru|es|tr|', '|'.$language.'|')===false) // supported as of 3/2010
				$language='en';
			
			$qa_content['script_lines'][]=array(
				"var RecaptchaOptions = {",
				"\ttheme:'white',",
				"\tlang:".qa_js($language),
				"}",
			);
		}
	}
	

/*
	Omit PHP closing tag to help avoid accidental output
*/