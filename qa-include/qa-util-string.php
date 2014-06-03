<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-string.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Some useful string-related stuff


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


//	Some static definitions

	define('QA_PREG_INDEX_WORD_SEPARATOR', '[\n\r\t\ \!\"\\\'\(\)\*\+\,\.\/\:\;\<\=\>\?\[\\\\\]\^\`\{\|\}\~]');
		// Notable exclusions here: $ & - _ # % @
		
	define('QA_PREG_BLOCK_WORD_SEPARATOR', '[\n\r\t\ \!\"\\\'\(\)\+\,\.\/\:\;\<\=\>\?\[\\\\\]\^\`\{\|\}\~\$\&\-\_\#\%\@]');
		// Asterisk (*) excluded here because it's used to match anything
	
	global $qa_utf8punctuation; // we could already be inside a function here
	
	$qa_utf8punctuation=array( // converts UTF-8 punctuation characters to spaces (or in some cases, hyphens)
		"\xC2\xA1" => ' ', // INVERTED EXCLAMATION MARK
		"\xC2\xA6" => ' ', // BROKEN BAR
		"\xC2\xAB" => ' ', // LEFT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\xC2\xB1" => ' ', // PLUS-MINUS SIGN
		"\xC2\xBB" => ' ', // RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK
		"\xC2\xBF" => ' ', // INVERTED QUESTION MARK
		"\xC3\x97" => ' ', // MULTIPLICATION SIGN
		"\xC3\xB7" => ' ', // DIVISION SIGN

		"\xE2\x80\x80" => ' ', // EN QUAD
		"\xE2\x80\x81" => ' ', // EM QUAD
		"\xE2\x80\x82" => ' ', // EN SPACE
		"\xE2\x80\x83" => ' ', // EM SPACE
		"\xE2\x80\x84" => ' ', // THREE-PER-EM SPACE
		"\xE2\x80\x85" => ' ', // FOUR-PER-EM SPACE
		"\xE2\x80\x86" => ' ', // SIX-PER-EM SPACE
		"\xE2\x80\x87" => ' ', // FIGURE SPACE
		"\xE2\x80\x88" => ' ', // PUNCTUATION SPACE
		"\xE2\x80\x89" => ' ', // THIN SPACE
		"\xE2\x80\x8A" => ' ', // HAIR SPACE
		"\xE2\x80\x8B" => ' ', // ZERO WIDTH SPACE
		"\xE2\x80\x8C" => ' ', // ZERO WIDTH NON-JOINER
		"\xE2\x80\x8E" => ' ', // LEFT-TO-RIGHT MARK
		"\xE2\x80\x8F" => ' ', // RIGHT-TO-LEFT MARK
		
		"\xE2\x80\x90" => '-', // HYPHEN
		"\xE2\x80\x91" => '-', // NON-BREAKING HYPHEN
		"\xE2\x80\x92" => '-', // FIGURE DASH
		"\xE2\x80\x93" => '-', // EN DASH
		"\xE2\x80\x94" => '-', // EM DASH
		"\xE2\x80\x95" => '-', // HORIZONTAL BAR

		"\xE2\x80\x96" => ' ', // DOUBLE VERTICAL LINE
		"\xE2\x80\x98" => ' ', // LEFT SINGLE QUOTATION MARK
		"\xE2\x80\x99" => ' ', // RIGHT SINGLE QUOTATION MARK
		"\xE2\x80\x9A" => ' ', // SINGLE LOW-9 QUOTATION MARK
		"\xE2\x80\x9B" => ' ', // SINGLE HIGH-REVERSED-9 QUOTATION MARK
		"\xE2\x80\x9C" => ' ', // LEFT DOUBLE QUOTATION MARK
		"\xE2\x80\x9D" => ' ', // RIGHT DOUBLE QUOTATION MARK
		"\xE2\x80\x9E" => ' ', // DOUBLE LOW-9 QUOTATION MARK
		"\xE2\x80\x9F" => ' ', // DOUBLE HIGH-REVERSED-9 QUOTATION MARK

		"\xE2\x80\xA2" => ' ', // BULLET
		"\xE2\x80\xA4" => ' ', // ONE DOT LEADER
		"\xE2\x80\xA5" => ' ', // TWO DOT LEADER
		"\xE2\x80\xA6" => ' ', // HORIZONTAL ELLIPSIS
		"\xE2\x80\xB9" => ' ', // SINGLE LEFT-POINTING ANGLE QUOTATION MARK
		"\xE2\x80\xBA" => ' ', // SINGLE RIGHT-POINTING ANGLE QUOTATION MARK
		"\xE2\x80\xBC" => ' ', // DOUBLE EXCLAMATION MARK
		"\xE2\x80\xBD" => ' ', // INTERROBANG
		"\xE2\x81\x87" => ' ', // DOUBLE QUESTION MARK
		"\xE2\x81\x88" => ' ', // QUESTION EXCLAMATION MARK
		"\xE2\x81\x89" => ' ', // EXCLAMATION QUESTION MARK
	);
	
	
	function qa_string_to_words($string, $tolowercase=true, $delimiters=false)
/*
	Return the input string converted into an array of words, changed $tolowercase (or not),
	and including or not the $delimiters after each word
*/
	{
		global $qa_utf8punctuation;
		
		$string=strtr($tolowercase ? qa_strtolower($string) : $string, $qa_utf8punctuation);
		
		return preg_split('/('.QA_PREG_INDEX_WORD_SEPARATOR.'+)/', $string, -1,
			PREG_SPLIT_NO_EMPTY | ($delimiters ? PREG_SPLIT_DELIM_CAPTURE : 0));
	}

	
	function qa_tags_to_tagstring($tags)
/*
	Convert an array of tags into a string for storage in the database
*/
	{
		return implode(',', $tags);
	}

	
	function qa_tagstring_to_tags($tagstring)
/*
	Convert a tag string as stored in the database into an array of tags
*/
	{
		return empty($tagstring) ? array() : explode(',', $tagstring);
	}
	

	function qa_shorten_string_line($string, $length)
/*
	Return no more than $length characters from $string after converting it to a single line, by
	removing words from the middle (and especially towards the end)
*/
	{
		$string=strtr($string, "\r\n\t", '   ');
		
		if (qa_strlen($string)>$length) {
			$remaining=$length-5;
			
			$words=qa_string_to_words($string, false, true);
			$countwords=count($words);
			
			$prefix='';
			$suffix='';
			
			for ($addword=0; $addword<$countwords; $addword++) {
				$tosuffix=(($addword%3)==1); // order: prefix, suffix, prefix, prefix, suffix, prefix, ...
				
				if ($tosuffix)
					$word=array_pop($words);
				else
					$word=array_shift($words);
				
				if (qa_strlen($word)>$remaining)
					break;
				
				if ($tosuffix)
					$suffix=$word.$suffix;
				else
					$prefix.=$word;
				
				$remaining-=qa_strlen($word);
			}
			
			$string=$prefix.' ... '.$suffix;
		}
		
		return $string;
	}

	
	function qa_block_words_explode($wordstring)
/*
	Return an array of the words within $wordstring, each of which can contain asterisks
*/
	{
		return preg_split('/'.QA_PREG_BLOCK_WORD_SEPARATOR.'+/', $wordstring, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	
	function qa_block_words_to_preg($wordsstring)
/*
	Return a regular expression corresponding to the block words $wordstring
*/
	{
		$blockwords=qa_block_words_explode($wordsstring);
		$patterns=array();
		
		foreach ($blockwords as $blockword) // * in rule maps to [^ ]* in regular expression
			$patterns[]=str_replace('\\*', '[^ ]*', preg_quote(qa_strtolower($blockword)));
		
		return implode('|', $patterns);
	}

	
	function qa_block_words_match_all($string, $wordspreg)
/*
	Return an array of matches of any element of $blockwords (which can contain asterisks) in $string,
	offset => length
*/
	{
		global $qa_utf8punctuation, $qa_utf8punctuation_keeplength;
		
		if (strlen($wordspreg)) {
			// replace all word separators with spaces of same length
			
			if (!is_array($qa_utf8punctuation_keeplength)) {
				$qa_utf8punctuation_keeplength=array();
				foreach ($qa_utf8punctuation as $key => $value)
					$qa_utf8punctuation_keeplength[$key]=str_repeat(' ', strlen($key));
			}
			
			$string=strtr(qa_strtolower($string), $qa_utf8punctuation_keeplength);
				// assumes UTF-8 case conversion in qa_strtolower does not change byte length
			$string=preg_replace('/'.QA_PREG_BLOCK_WORD_SEPARATOR.'/', ' ', $string);
			
			preg_match_all('/(?<= )('.$wordspreg.') /', ' '.$string.' ', $pregmatches, PREG_OFFSET_CAPTURE);
			
			$outmatches=array();
			foreach ($pregmatches[1] as $pregmatch)
				$outmatches[$pregmatch[1]-1]=strlen($pregmatch[0]);
				
			return $outmatches;
		}
		
		return array();
	}
	
	
	function qa_block_words_replace($string, $wordspreg)
/*
	Return $string with asterisks replacing any words matching the blocked words regular expression $wordspreg
*/
	{
		if (strlen($wordspreg)) {
			$matches=qa_block_words_match_all($string, $wordspreg);
			krsort($matches, SORT_NUMERIC);
			
			foreach ($matches as $start => $length) // get length again below to deal with multi-byte characters
				$string=substr_replace($string, str_repeat('*', qa_strlen(substr($string, $start, $length))), $start, $length);
		}
			
		return $string;
	}
	
	
	function qa_random_alphanum($length)
/*
	Return a random alphanumeric string (base 36) of $length
*/
	{
		$string='';
		
		while (strlen($string)<$length)
			$string.=str_pad(base_convert(mt_rand(0, 46655), 10, 36), 3, '0', STR_PAD_LEFT);
			
		return substr($string, 0, $length);
	}

	
	function qa_email_validate($email)
/*
	Return true or false to indicate whether $email is a valid email (this is pretty flexible compared to most real emails out there)
*/
	{
		return preg_match("/^[\-\!\#\$\%\&\'\*\+\/\=\?\_\`\{\|\}\~a-zA-Z0-9\.\^]+\@[a-zA-Z0-9\-]+\.[a-zA-Z0-9\.\-]+$/", $email) ? true : false;
	}
	

	function qa_strlen($string)
/*
	Return the number of characters in $string, preferably using PHP's multibyte string functions
*/
	{
		return function_exists('mb_strlen') ? mb_strlen($string, 'UTF-8') : strlen($string);
	}


	function qa_strtolower($string)
/*
	Return a lower case version of $string, preferably using PHP's multibyte string functions
*/
	{
		return function_exists('mb_strtolower') ? mb_strtolower($string, 'UTF-8') : strtolower($string);
	}
	

	function qa_has_multibyte()
/*
	Return whether this version of PHP has been compiled with multibyte string support
*/
	{
		return function_exists('mb_strlen') && function_exists('mb_strtolower');
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/