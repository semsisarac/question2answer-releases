<?php
	
/*
	Question2Answer 1.2.1 (c) 2010, Gideon Greenspan

	http://www.question2answer.org/

	
	File: qa-include/qa-lang-admin.php
	Version: 1.2.1
	Date: 2010-07-29 03:54:35 GMT
	Description: Language phrases for admin center


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

	return array(
		'add_category_button' => 'Add Category',
		'add_link_button' => 'Add Link',
		'add_page_button' => 'Add Page',
		'admin_title' => 'Administration Center',
		'after_footer' => 'After links in footer',
		'after_main_menu' => 'After tabs at top',
		'after_x_tab' => 'After "^" tab',
		'after_x' => 'After "^"',
		'before_main_menu' => 'Before tabs at top',
		'block_ips_note' => 'Use a hyphen for ranges or * to match any number. Examples: 192.168.0.4 , 192.168.0.0-192.168.0.31 , 192.168.0.*',
		'block_words_note' => 'Use a * to match any letters. Examples: doh (will only match exact word doh) , doh* (will match doh or dohno) , do*h (will match doh, dooh, dough).',
		'categories_not_shown' => 'Some questions have categories which will not be displayed.',
		'categories_title' => 'Categories',
		'categories' => 'Categories',
		'category_already_used' => 'This is already being used by a category',
		'category_default_slug' => 'category-^',
		'categories_introduction' => 'To get started with categories, click the \'Add Category\' button.',
		'category_name_first' => 'Name of first category:',
		'category_name' => 'Category name:',
		'category_position' => 'Position in categories:',
		'category_slug' => 'Category slug (URL fragment):',
		'click_category_edit' => 'Click a category name to edit:',
		'click_name_edit' => 'Click a custom page or link to edit:',
		'database_cleanup' => 'Database clean-up operations',
		'delete_category_reassign' => 'Delete this category and reassign its questions to:',
		'delete_category' => 'Delete this category',
		'delete_hidden_complete' => 'All hidden posts without dependents have been deleted',
		'delete_hidden_note' => ' - all hidden questions, answer and comments without dependents',
		'delete_hidden' => 'Delete hidden posts',
		'delete_link' => 'Delete this link',
		'delete_page' => 'Delete this page',
		'delete_stop' => 'Stop deleting',
		'emails_title' => 'Emails',
		'feed_link_example' => 'Example feed',
		'feed_link' => 'Feed',
		'feeds_title' => 'RSS feeds',
		'first' => 'First',
		'from_anon' => 'From anonymous:',
		'from_users' => 'From users:',
		'general_title' => 'General',
		'hidden_answers_deleted' => 'Deleted ^1 of ^2 hidden answers without dependents...',
		'hidden_comments_deleted' => 'Deleted ^1 of ^2 hidden comments...',
		'hidden_questions_deleted' => 'Deleted ^1 of ^2 hidden questions without dependents...',
		'hidden_title' => 'Hidden',
		'layout_title' => 'Layout',
		'link_name' => 'Text of link:',
		'link_new_window' => 'Open link in a new window',
		'link_url' => 'URL of link (absolute or relative to QA root):',
		'maximum_x' => ' (max ^)',
		'nav_links_explanation' => 'Show the following navigation links on every page:',
		'nav_qa_is_home' => 'Q&A (links to home page)',
		'neat_urls_note' => ' (requires ^1htaccess^2 file)',
		'no_classification' => 'No classification',
		'no_hidden_found' => 'No hidden questions, answers or comments found',
		'no_link' => 'No link',
		'no_multibyte' => 'The installed version of PHP was compiled without multibyte string support. Searching will be less effective for non-Roman characters.',
		'no_privileges' => 'Only administrators may access this page.',
		'not_logged_in' => 'Please ^1log in^2 as the adminstrator to access this page.',
		'opposite_main_menu' => 'Far end of tabs at top',
		'options_reset' => 'Options reset',
		'options_saved' => 'Options saved',
		'page_already_used' => 'This is already being used by a page',
		'page_content_html' => 'Content to display in page (HTML allowed):',
		'page_default_slug' => 'page-^',
		'page_heading' => 'Heading to display at top of page:',
		'page_link_position' => 'Position:',
		'page_name' => 'Name of page (also used for tab or link):',
		'page_slug' => 'Page slug (URL fragment):',
		'pages_explanation' => 'Click the \'Add Page\' button to add custom content to your QA site, or \'Add Link\' to link to any other web page.',
		'pages_title' => 'Pages',
		'permissions_title' => 'Permissions',
		'points_defaults_shown' => 'Defaults shown below but NOT YET APPLIED:',
		'points_title' => 'Points',
		'points' => 'points',
		'posting_title' => 'Posting',
		'recalc_categories_complete' => 'All categories were successfully recalculated.',
		'recalc_categories_note' => ' - for post categories and category counts',
		'recalc_categories_recounting' => 'Recalculating category question totals...',
		'recalc_categories_updated' => 'Recalculated for ^1 of ^2 answers and comments...',
		'recalc_categories' => 'Recalculate categories',
		'recalc_points_complete' => 'All user points were successfully recalculated.',
		'recalc_points_note' => ' - for user ranking and points displays',
		'recalc_points_recalced' => 'Recalculated for ^1 of ^2 users...',
		'recalc_points_usercount' => 'Estimating total number of users...',
		'recalc_points' => 'Recalculate user points',
		'recalc_posts_count' => 'Getting total number of questions, answers and comments...',
		'recalc_stop' => 'Stop recalculating',
		'recaptcha_fsockopen' => 'To use reCAPTCHA, the fsockopen() PHP function must be enabled on your server. Please check with your system administrator.',
		'recaptcha_get_keys' => 'To use reCAPTCHA, you must ^1sign up^2 to get these keys.',
		'recent_hidden_title' => 'Questions with recent hidden content',
		'recount_posts_complete' => 'All questions and answers were successfully recounted',
		'recount_posts_note' => ' - the number of answers and votes for each post',
		'recount_posts_recounted' => 'Recounted for ^1 of ^2 questions and answers...',
		'recount_posts_stop' => 'Stop recounting',
		'recount_posts' => 'Recount questions and answers',
		'reindex_posts_complete' => 'All questions, answers and comments were successfully reindexed.',
		'reindex_posts_note' => ' - for searching and related question suggestions',
		'reindex_posts_reindexed' => 'Reindexed ^1 of ^2 questions, answers and comments...',
		'reindex_posts_stop' => 'Stop reindexing',
		'reindex_posts_wordcounted' => 'Recounted ^1 of ^2 words...',
		'reindex_posts' => 'Reindex questions, answers and comments',
		'reset_options_button' => 'Reset to Defaults',
		'save_options_button' => 'Save Options',
		'save_recalc_button' => 'Save and Recalculate',
		'show_defaults_button' => 'Show Defaults',
		'slug_bad_chars' => 'The slug may not contain these characters: ^',
		'slug_reserved' => 'This slug is reserved for use by the engine',
		'spam_title' => 'Spam',
		'stats_title' => 'Stats',
		'stop_recalc_warning' => 'A database clean-up operation is running. If you close this page now, the operation will be interrupted.',
		'tags_and_categories' => 'Tags and Categories',
		'tags_not_shown' => 'Some questions have tags which will not be displayed.',
		'tags' => 'Tags',
		'total_as' => 'Total answers:',
		'total_cs' => 'Total comments:',
		'total_qs' => 'Total questions:',
		'upgrade_db' => 'Your Question2Answer database needs to be ^1upgraded^2 for this version.',
		'url_format_note' => 'Options with the ^ label are working for your site\'s configuration. For best search engine optimization (SEO), use the first ^ option available.',
		'users_active' => 'Active users:',
		'users_posted' => 'Users who posted:',
		'users_registered' => 'Registered users:',
		'users_voted' => 'Users who voted:',
		'viewing_title' => 'Viewing',
	);
	

/*
	Omit PHP closing tag to help avoid accidental output
*/