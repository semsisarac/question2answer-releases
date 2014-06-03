<?php

/*
	Question2Answer 1.3.3 (c) 2011, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-check-lang.php
	Version: 1.3.3
	Date: 2011-03-16 12:46:02 GMT
	Description: Development tool to see which language phrases are missing or unused


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

	define('QA_BASE_DIR', dirname(dirname(empty($_SERVER['SCRIPT_FILENAME']) ? __FILE__ : $_SERVER['SCRIPT_FILENAME'])).'/');

	require 'qa-base.php';
	
	header('Content-type: text/html; charset=utf-8');
?>
<HTML>
	<HEAD>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8"/>
		<TITLE>Question2Answer Language Check</TITLE>
	</HEAD>
	<BODY>
<?php

	function get_phrase_substitutions($phrase)
	{
		$substitutions=array();

		if (preg_match_all('/\^(([0-9]+)|([a-z_]+)|)/', $phrase, $matches))
			foreach ($matches[0] as $match)
				@$substitutions[$match]++;
				
		return $substitutions;
	}
	
	echo '<H2>Checking US English files in qa-include:</H2>';
	
	$includefiles=glob(QA_INCLUDE_DIR.'qa-*.php');
	
	$definite=array();
	$probable=array();
	$possible=array();
	$defined=array();
	$english=array();
	$backmap=array();
	$substitutions=array();
	
	foreach ($includefiles as $includefile) {
		$contents=file_get_contents($includefile);
		
		preg_match_all('/qa_lang[a-z_]*\s*\(\s*[\'\"]([a-z]+)\/([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $matchparts)
			@$definite[$matchparts[1]][$matchparts[2]]++;
			
		preg_match_all('/[\'\"]([a-z]+)\/([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);

		foreach ($matches as $matchparts)
			@$probable[$matchparts[1]][$matchparts[2]]++;

		if (preg_match('/qa-lang-([a-z]+)\.php$/', $includefile, $matches)) { // it's a lang file
			$prefix=$matches[1];
		
			$phrases=@include $includefile;
			
			foreach ($phrases as $key => $value) {
				@$defined[$prefix][$key]++;
				$english[$prefix][$key]=$value;
				$backmap[$value][]=$prefix.'/'.$key;
				$substitutions[$prefix][$key]=get_phrase_substitutions($value);
			}

		} else { // it's a different file
			preg_match_all('/[\'\"\/]([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);
			
			foreach ($matches as $matchparts)
				@$possible[$matchparts[1]]++;
		}
	}
	
	foreach ($definite as $key => $valuecount)
		foreach ($valuecount as $value => $count)
			if (!@$defined[$key][$value])
				echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' used by '.$count.' but not defined').'</FONT><BR>';
				
	foreach ($defined as $key => $valuecount)
		foreach ($valuecount as $value => $count)
			if ( (!@$definite[$key][$value]) && (!@$probable[$key][$value]) ) {
				if (@$possible[$value]) {
					if ($key!='options')
						echo htmlspecialchars($key.'/'.$value.' defined and possibly not used').'<BR>';
				} else
					echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' defined and apparently not used').'</FONT><BR>';
			}
	
	foreach ($backmap as $phrase => $where)
		if (count($where)>1)
			echo '<FONT COLOR="blue">'.htmlspecialchars('"'.$phrase.'" multiply defined as '.implode(' and ', $where)).'</FONT><BR>';
	
	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	
	$languages=qa_admin_language_options();
	unset($languages['']);
	
	foreach ($languages as $code => $language) {
		echo '<H2>Checking '.$language.' files in qa-lang/'.$code.':</H2>';
		
		$langdefined=array();
		$langdifferent=array();
		$langsubstitutions=array();
		$langincludefiles=glob(QA_LANG_DIR.$code.'/qa-*.php');
		
		foreach ($langincludefiles as $langincludefile)
			if (preg_match('/qa-lang-([a-z]+)\.php$/', $langincludefile, $matches)) { // it's a lang file
				$prefix=$matches[1];
				$phrases=@include $langincludefile;
				
				foreach ($phrases as $key => $value) {
					@$langdefined[$prefix][$key]++;
					$langdifferent[$prefix][$key]=($value!=$english[$prefix][$key]);
					$langsubstitutions[$prefix][$key]=get_phrase_substitutions($value);
				}
			}
			
		foreach ($langdefined as $key => $valuecount)
			foreach ($valuecount as $value => $count) {
				if (!@$defined[$key][$value])
					echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' defined but not in US English files').'</FONT><BR>';
				
				elseif (!$langdifferent[$key][$value])
					echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' is identical to US English files').'</FONT><BR>';
				
				else
					foreach ($substitutions[$key][$value] as $substitution => $subcount)
						if (!@$langsubstitutions[$key][$value][$substitution])
							echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' omitted the substitution '.$substitution).'</FONT><BR>';
						elseif ($subcount > @$langsubstitutions[$key][$value][$substitution])
							echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' has fewer of the substitution '.$substitution).'</FONT><BR>';
			}
					
		foreach ($defined as $key => $valuecount)
			if (@$langdefined[$key]) {
				if (count($langdefined[$key]) < (count($valuecount)/2)) { // only a few phrases defined
					echo '<FONT COLOR="red">'.htmlspecialchars('Most '.$key.'/* values undefined so will use US English defaults').'</FONT><BR>';

				} else
					foreach ($valuecount as $value => $count)
						if (!@$langdefined[$key][$value])
							echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' undefined so will use US English defaults').'</FONT><BR>';
			} else
				echo '<FONT COLOR="red">'.htmlspecialchars('All '.$key.'/* values undefined so will use US English defaults').'</FONT><BR>';
	}
	
	echo '<H2>Finished scanning for problems!</H2>';

?>

	</BODY>
</HTML>