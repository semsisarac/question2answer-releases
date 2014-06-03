<?php

/*
	Question2Answer 1.0 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-util-sort.php
	Version: 1.0
	Date: 2010-04-09 16:07:28 GMT
	Description: A useful general-purpose 'sort by' function


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

	if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
		header('Location: ../');
		exit;
	}


	function qa_sort_by(&$array, $by1, $by2=null)
/*
	Sort the $array of inner arrays by sub-element $by1 of each inner array, and optionally then by sub-element $by2
*/
	{
		global $qa_sort_by1, $qa_sort_by2;
		
		$qa_sort_by1=$by1;
		$qa_sort_by2=$by2;
		
		uasort($array, 'qa_sort_by_fn');
	}


	function qa_sort_by_fn($a, $b)
/*
	Function used in uasort to implement qa_sort_by()
*/
	{
		global $qa_sort_by1, $qa_sort_by2;
		
		$compare=qa_sort_cmp($a[$qa_sort_by1], $b[$qa_sort_by1]);

		if (($compare==0) && $qa_sort_by2)
			$compare=qa_sort_cmp($a[$qa_sort_by2], $b[$qa_sort_by2]);

		return $compare;
	}


	function qa_sort_cmp($a, $b)
/*
	General comparison function for two values, textual or numeric
*/
	{
		if (is_numeric($a) && is_numeric($b)) // straight subtraction won't work for floating bits
			return ($a==$b) ? 0 : (($a<$b) ? -1 : 1);

		else {
			require_once QA_INCLUDE_DIR.'qa-util-string.php';
		
			return strcasecmp($a, $b); // doesn't do UTF-8 right but it will do for now
		}
	}
	
?>