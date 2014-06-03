<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-string.php
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

	function qa_string_to_words($string)
	{
		// Array to convert UTF-8 punctuation characters to spaces (or in some cases, hyphens)
		
		$utf8punctuation=array(
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
		
		$string=strtr(qa_strtolower($string), $utf8punctuation);
		
		// ASCII symbols - notable exclusions: $ & - _ # % @
		
		$matchstring='/[\n\r\t\ \!\"\\\'\(\)\*\+\,\.\/\:\;\<\=\>\?\[\\\\\]\^\`\{\|\}\~]+/';

		return preg_split($matchstring, $string, -1, PREG_SPLIT_NO_EMPTY);
	}
	
	function qa_tags_to_tagstring($tags)
	{
		return implode(',', $tags);
	}
	
	function qa_tagstring_to_tags($tagstring)
	{
		return empty($tagstring) ? array() : explode(',', $tagstring);
	}
	
	function qa_random_alphanum($length)
	{
		$string='';
		
		while (strlen($string)<$length)
			$string.=str_pad(base_convert(mt_rand(0, 46655), 10, 36), 3, '0', STR_PAD_LEFT);
			
		return substr($string, 0, $length);
	}
	
	function qa_email_validate($email)
	{
		return ereg("^[-!#$%&'*+/=?_`{|}~a-zA-Z0-9.^]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9.-]+$", $email) ? true : false;
	}
	
	function qa_strlen($string)
	{
		return function_exists('mb_strlen') ? mb_strlen($string, 'UTF-8') : strlen($string);
	}

	function qa_strtolower($string)
	{
		return function_exists('mb_strtolower') ? mb_strtolower($string, 'UTF-8') : strtolower($string);
	}
	
	function qa_has_multibyte()
	{
		return function_exists('mb_strlen') && function_exists('mb_strtolower');
	}

?>