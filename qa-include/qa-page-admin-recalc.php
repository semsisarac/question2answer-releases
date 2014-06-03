<?php

/*
	Question2Answer 1.2-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-recalc.php
	Version: 1.2-beta-1
	Date: 2010-06-27 11:15:58 GMT
	Description: Handles admin-triggered recalculations if JavaScript disabled


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

	require_once QA_INCLUDE_DIR.'qa-app-admin.php';
	require_once QA_INCLUDE_DIR.'qa-app-recalc.php';

	
//	Check we have administrative privileges

	if (!qa_admin_check_privileges())
		return;

	
//	Find out the operation

	$allowstates=array(
		'dorecountposts',
		'doreindexposts',
		'dorecalcpoints',
		'dorecalccategories',
		'dodeletehidden',
	);
	
	foreach ($allowstates as $allowstate)
		if (qa_post_text($allowstate) || qa_get($allowstate))
			$state=$allowstate;
			
	if (isset($state)) {
?>

<HTML>
	<HEAD>
		<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=utf-8">
	</HEAD>
	<BODY>
		<TT>

<?php

		while ($state) {
			set_time_limit(60);
			
			$stoptime=time()+2; // run in lumps of two seconds...
			
			while ( qa_recalc_perform_step($qa_db, &$state) && (time()<$stoptime) )
				;
			
			echo qa_html(qa_recalc_get_message($state)).str_repeat('    ', 1024)."<BR>\n";

			flush();
			sleep(1); // ... then rest for one
		}

?>
		</TT>
		
		<A HREF="<?php echo qa_path_html('admin/stats')?>"><?php echo qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/stats_title')?></A>
	</BODY>
</HTML>

<?php
		exit;
	
	} else {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		qa_content_prepare();

		$qa_content['title']=qa_lang_html('admin/admin_title');
		$qa_content['error']=qa_lang_html('main/page_not_found');
	}
			

/*
	Omit PHP closing tag to help avoid accidental output
*/