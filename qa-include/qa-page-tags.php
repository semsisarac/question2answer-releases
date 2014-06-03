<?php

/*
	Question2Answer 1.0-beta-1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-page-tags.php
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

*/

	require_once QA_INCLUDE_DIR.'qa-db-selects.php';
	require_once QA_INCLUDE_DIR.'qa-app-format.php';

//	Get popular tags
	
	qa_options_set_pending(array('page_size_tags', 'columns_tags'));
	
	list($populartags, $tagcount)=qa_db_select_with_pending($qa_db,
		qa_db_popular_tags_selectspec($qa_start),
		qa_db_options_cache_selectspec('cache_tagcount')
	);
	
	$pagesize=qa_get_option($qa_db, 'page_size_tags');
	
//	Prepare content for theme

	qa_content_prepare();

	$qa_content['title']=qa_lang_html('main/popular_tags');
	
	$qa_content['ranking']=array('items' => array(), 'rows' => ceil($pagesize/qa_get_option($qa_db, 'columns_tags')));
	
	if (count($populartags)) {
		$output=0;
		foreach ($populartags as $word => $count) {
			$qa_content['ranking']['items'][]=array(
				'label' => qa_tag_html($word),
				'count' => number_format($count),
			);
			
			if ((++$output)>=$pagesize)
				break;
		}

	} else
		$qa_content['title']=qa_lang_html('main/no_tags_found');
	
	$qa_content['page_links']=qa_html_page_links($qa_request, $qa_start, $pagesize, $tagcount, qa_get_option($qa_db, 'pages_prev_next'));

	if (empty($qa_content['page_links']))
		$qa_content['suggest_next']=qa_html_suggest_ask();
?>