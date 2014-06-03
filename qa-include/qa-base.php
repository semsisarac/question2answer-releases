<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-base.php
	Version: 1.0-beta-2
	Date: 2010-03-08 13:08:01 GMT


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

//	Set the version to be used for internal reference and a suffix for .js and .css requests

	define('QA_VERSION', '1.0-beta-2');

//	Basic PHP configuation checks and unregister globals

	if ( ((float)phpversion()) < 4.3 )
		qa_fatal_error('This requires PHP 4.3 or later');

	set_magic_quotes_runtime(false);
	
	if (ini_get('register_globals')) {
		$checkarrays=array('_ENV', '_GET', '_POST', '_COOKIE', '_SERVER', '_FILES', '_REQUEST', '_SESSION');
		$keyprotect=array_flip(array_merge($checkarrays, array('GLOBALS')));
		
		foreach ($checkarrays as $checkarray)
			if ( isset(${$checkarray}) && is_array(${$checkarray}) )
				foreach (${$checkarray} as $checkkey => $checkvalue)
					if (isset($keyprotect[$checkkey]))
						qa_fatal_error('My superglobals are not for overriding');
					else
						unset($GLOBALS[$checkkey]);
	}

//	Define directories of important files in local disk space, load up configuration
	
	define('QA_EXTERNAL_DIR', QA_BASE_DIR.'qa-external/');
	define('QA_INCLUDE_DIR', QA_BASE_DIR.'qa-include/');
	define('QA_LANG_DIR', QA_BASE_DIR.'qa-lang/');
	define('QA_THEME_DIR', QA_BASE_DIR.'qa-theme/');

	if (!file_exists(QA_BASE_DIR.'qa-config.php'))
		qa_fatal_error('The config file could not be found. Please read the installation instructions.');
	
	require_once QA_BASE_DIR.'qa-config.php';
	
//	General HTML/JS functions

	function qa_html($string, $multiline=false)
	{
		$html=htmlspecialchars($string);
		
		if ($multiline) {
			$html=preg_replace('/\r\n?/', "\n", $html);
			$html=preg_replace('/(?<=\s) /', '&nbsp;', $html);
			$html=str_replace("\t", '&nbsp; &nbsp; ', $html);
			$html=nl2br($html);
		}
		
		return $html;
	}
	
	function qa_js($string)
	{
		if (is_numeric($string))
			return $string;
		else
			return "'".strtr($string, array(
				"'" => "\\'",
				"\n" => "\\n",
				"\r" => "\\n",
			))."'";
	}
	
	function qa_gpc_to_string($string)
	{
		return get_magic_quotes_gpc() ? stripslashes($string) : $string;
	}

	function qa_get($field)
	{
		return isset($_GET[$field]) ? qa_gpc_to_string($_GET[$field]) : null;
	}

	function qa_post_text($field)
	{
		return isset($_POST[$field]) ? preg_replace('/\r\n?/', "\n", trim(qa_gpc_to_string($_POST[$field]))) : null;
	}
	
	function qa_clicked($name)
	{
		return isset($_POST[$name]) || isset($_POST[$name.'_x']);
	}
	
//	Language support

	function qa_lang_base($identifier)
	{
		global $qa_db;
		
		require_once QA_INCLUDE_DIR.'qa-app-options.php';
		
		$languagecode=qa_get_option($qa_db, 'site_language');
		
		list($group, $label)=explode('/', $identifier, 2);
		
		if (strlen($languagecode)) {
			global $qa_lang_custom;
		
			if (!isset($qa_lang_custom[$group])) {
				$directory=QA_LANG_DIR.$languagecode.'/';
				
				if (!file_exists($directory))
					qa_fatal_error('Language directory '.$languagecode.' not installed');
				
				$phrases=@include $directory.'qa-lang-'.$group.'.php';
				
				$qa_lang_custom[$group]=is_array($phrases) ? $phrases : array();
			}
			
			if (isset($qa_lang_custom[$group][$label]))
				return $qa_lang_custom[$group][$label];
		}
		
		global $qa_lang_default;
		
		if (!isset($qa_lang_default[$group]))
			$qa_lang_default[$group]=include_once QA_INCLUDE_DIR.'qa-lang-'.$group.'.php';
		
		if (isset($qa_lang_default[$group][$label]))
			return $qa_lang_default[$group][$label];
			
		return '['.$identifier.']'; // last resort!
	}

	if (QA_EXTERNAL_LANG) {

		require QA_EXTERNAL_DIR.'qa-external-lang.php';

	} else {

		function qa_lang($identifier)
		{
			return qa_lang_base($identifier);
		}

	}
	
	function qa_lang_sub($identifier, $textparam, $symbol='^')
	{
		return str_replace($symbol, $textparam, qa_lang($identifier));
	}
	
	function qa_lang_html($identifier)
	{
		return qa_html(qa_lang($identifier));
	}
	
	function qa_lang_sub_html($identifier, $htmlparam, $symbol='^')
	{
		return str_replace($symbol, $htmlparam, qa_lang_html($identifier));
	}
	
	function qa_lang_sub_split_html($identifier, $htmlparam, $symbol='^')
	{
		$html=qa_lang_html($identifier);

		$symbolpos=strpos($html, $symbol);
		if (!is_numeric($symbolpos))
			qa_fatal_error('Missing '.$symbol.' in language string '.$identifier);
			
		return array(
			'prefix' => substr($html, 0, $symbolpos),
			'data' => $htmlparam,
			'suffix' => substr($html, $symbolpos+1),
		);
	}
	
//	Path generation

	function qa_path($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
	{
		global $qa_db, $qa_root_url_relative;
		
		if (!isset($neaturls))
			$neaturls=qa_get_option($qa_db, 'neat_urls');
		
		if (!isset($rooturl))
			$rooturl=$qa_root_url_relative;
			
		$paramsextra='';
		if (isset($params))
			foreach ($params as $key => $value)
				$paramsextra.=(strlen($paramsextra) ? '&' : '?').urlencode($key).'='.urlencode($value);
		
		return $rooturl
			.( (empty($rooturl) || (substr($rooturl, -1)=='/') ) ? '' : '/')
			.( ($neaturls || empty($request)) ? $request : ('index.php/'.$request) )
			.$paramsextra
			.( empty($anchor) ? '' : '#'.urlencode($anchor) );
	}
	
	function qa_q_request($postid, $title)
	{
		require_once QA_INCLUDE_DIR.'qa-util-string.php';
	
	//	SEO: keep to a length of just over 50 characters, not including hyphens
	//	To do this, remove shorter words, which are generally less meaningful
	
		$words=qa_string_to_words($title);

		$wordlength=array();
		foreach ($words as $index => $word)
			$wordlength[$index]=qa_strlen($word);

		$remaining=50;
		
		if (array_sum($wordlength)>$remaining) {
			arsort($wordlength, SORT_NUMERIC);
			
			foreach ($wordlength as $index => $length) {
				if ($remaining>0)
					$remaining-=$length;
				else
					$wordlength[$index]=false;
			}
			
			foreach ($words as $index => $word)
				if (!$wordlength[$index])
					unset($words[$index]);
		}
		
		return (int)$postid.'/'.urlencode(implode('-', $words));
	}
	
	function qa_root_html()
	{
		global $qa_root_url_relative;
		
		return qa_html($qa_root_url_relative);
	}

	function qa_path_html($request, $params=null, $rooturl=null, $neaturls=null, $anchor=null)
	{
		return qa_html(qa_path($request, $params, $rooturl, $neaturls, $anchor));
	}
	
	function qa_redirect($request, $params=null, $rooturl=null, $neaturls=null)
	{
		header('Location: '.qa_path($request, $params, $rooturl, $neaturls));
		exit;
	}

//	Database connection

	function qa_base_db_connect($failhandler)
	{
		global $qa_db;
		
		require_once QA_INCLUDE_DIR.'qa-db.php';
	
		$qa_db=qa_db_connect($failhandler);
	}
	
	function qa_base_db_disconnect()
	{
		global $qa_db;
		
		qa_db_disconnect($qa_db);
	}

//	Error handling

	function qa_fatal_error($message)
	{
		echo '<FONT COLOR="red">'.qa_html($message).'</FONT>';
		exit;
	}
	
?>