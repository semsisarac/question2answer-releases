<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-check-lang.php
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


A helpful tool for development to see which language phrases are missing or not used
*/

	$includefiles=glob(dirname(__FILE__).'/qa-*.php');
	
	$definite=array();
	$possible=array();
	$defined=array();
	$backmap=array();
	
	foreach ($includefiles as $includefile) {
		$contents=file_get_contents($includefile);
		
		preg_match_all('/qa_lang[a-z_]*\s*\(\s*[\'\"]([a-z]+)\/([0-9a-z_]+)[\'\"]/', $contents, $matches, PREG_SET_ORDER);
		
		foreach ($matches as $matchparts)
			@$definite[$matchparts[1]][$matchparts[2]]++;
			
		if (preg_match('/qa-lang-([a-z]+)\.php$/', $includefile, $matches)) { // it's a lang file
			$prefix=$matches[1];
		
			$phrases=@include $includefile;
			
			foreach ($phrases as $key => $value) {
				$defined[$prefix][$key]++;
				$backmap[$value][]=$prefix.'/'.$key;
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
			if (!@$definite[$key][$value]) {
				if (@$possible[$value]) 
					echo htmlspecialchars($key.'/'.$value.' defined and possibly not used').'<BR>';
				else
					echo '<FONT COLOR="red">'.htmlspecialchars($key.'/'.$value.' defined and apparently not used').'</FONT><BR>';
			}
	
	foreach ($backmap as $phrase => $where)
		if (count($where)>1)
			echo '<FONT COLOR="blue">'.htmlspecialchars('"'.$phrase.'" multiply defined as '.implode(' and ', $where)).'</FONT><BR>';
	
?>