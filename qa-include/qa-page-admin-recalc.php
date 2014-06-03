<?php

/*
	Question2Answer 1.0-beta-2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-admin-recalc.php
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

<?

		while ($state) {
			set_time_limit(60);
			
			$stoptime=time()+2;
			
			while ( qa_recalc_perform_step($qa_db, &$state) && (time()<$stoptime) )
				;
			
			echo qa_html(qa_recalc_get_message($state)).str_repeat('    ', 1024)."<BR>\n";

			flush();
			sleep(1);
		}

?>
		</TT>
		
		<A HREF="<?=qa_path_html('admin/stats')?>"><?=qa_lang_html('admin/admin_title').' - '.qa_lang_html('admin/stats_title')?></A>
	</BODY>
</HTML>

<?
		exit;
	
	} else {
		require_once QA_INCLUDE_DIR.'qa-app-format.php';
		
		qa_content_prepare();

		$qa_content['title']=qa_lang_html('admin/admin_title');
		$qa_content['error']=qa_lang_html('main/page_not_found');
	}
			
?>