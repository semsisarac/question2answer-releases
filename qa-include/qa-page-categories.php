<?php

/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-categories.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Controller for popular tags page


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

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';


//	Get popular tags
	
	$categories=qa_db_select_with_pending($qa_db,
		qa_db_categories_selectspec()
	);
	
	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('main/all_categories');
	
	$qa_content['ranking']=array('items' => array(), 'rows' => count($categories));
	
	if (count($categories)) {
		foreach ($categories as $category)
			$qa_content['ranking']['items'][]=array(
				'label' => qa_category_html($category),
				'count' => number_format($category['qcount']),
			);
			
	} else {
		$qa_content['title']=qa_lang_html('main/no_categories_found');
		$qa_content['suggest_next']=qa_html_suggest_qs_tags(qa_using_tags($qa_db));
	}


/*
	Omit PHP closing tag to help avoid accidental output
*/