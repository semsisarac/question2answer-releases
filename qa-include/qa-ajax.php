<?php

/*
	Question2Answer 1.2 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-ajax.php
	Version: 1.2
	Date: 2010-07-20 09:24:45 GMT
	Description: Front line of response to Ajax requests, routing as appropriate


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

//	Output this header as early as possible

	header('Content-Type: text/plain');

//	Ensure no PHP errors are shown in the Ajax response

	@ini_set('display_errors', 0);

//	Load the QA base file which sets up a bunch of crucial functions

	require 'qa-base.php';

//	Get general Ajax parameters from the POST payload

	$qa_root_url_relative=qa_post_text('qa_root');
	$qa_request=qa_post_text('qa_request');
	$qa_operation=qa_post_text('qa_operation');

//	Perform the appropriate Ajax operation

	switch ($qa_operation) {
		case 'vote':
			require QA_INCLUDE_DIR.'qa-ajax-vote.php';
			break;
			
		case 'recalc':
			require QA_INCLUDE_DIR.'qa-ajax-recalc.php';
			break;
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/