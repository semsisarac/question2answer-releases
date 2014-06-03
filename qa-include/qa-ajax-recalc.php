<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax-recalc.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Server-side response to Ajax recalculation requests


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

	require_once QA_INCLUDE_DIR.'qa-app-users.php';
	require_once QA_INCLUDE_DIR.'qa-app-recalc.php';
	

	function qa_ajax_recalc_db_fail_handler()
	{
		echo "QA_AJAX_RESPONSE\n0\n\nA database error occurred.";
		exit;
	}

	qa_base_db_connect('qa_ajax_recalc_db_fail_handler');
	
	if (qa_get_logged_in_level($qa_db)>=QA_USER_LEVEL_ADMIN) {
		$state=qa_post_text('state');
		$stoptime=time()+3;
		
		while ( qa_recalc_perform_step($qa_db, $state) && (time()<$stoptime) )
			;
			
		$message=qa_recalc_get_message($state);
	
	} else {
		$state='';
		$message=qa_lang('admin/no_privileges');
	}
	
	qa_base_db_disconnect();
	
	echo "QA_AJAX_RESPONSE\n1\n".$state."\n".qa_html($message);


/*
	Omit PHP closing tag to help avoid accidental output
*/