<?php
/*
	Snow Theme for Question2Answer Package
	Copyright (C) 2014 Q2A Market <http://www.q2amarket.com>

	File: qa-theme.php
	Version: Snow 1.4
	Description: Q2A theme class

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
 */

/**
 * Snow theme extends
 *
 * Extends the core theme class <code>qa_html_theme_base</code>
 *
 * @package qa_html_theme_base
 * @subpackage qa_html_theme
 * @category Theme
 * @since Snow 1.0
 * @version 1.4
 * @author Q2A Market <http://www.q2amarket.com>
 * @copyright (c) 2014, Q2A Market
 * @license http://www.gnu.org/copyleft/gpl.html
 */
class qa_html_theme extends qa_html_theme_base
{
	/**
	 * @since Snow 1.4
	 * @param type $template
	 * @param type $content
	 * @param type $rooturl
	 * @param type $request
	 */
	public function __construct($template, $content, $rooturl, $request, $textdir='ltr')
	{
		parent::__construct($template, $content, $rooturl, $request, $textdir);

		// theme subdirectories
		$this->js_dir = 'js/';
		$this->img_url = 'images/';
		$this->icon_url = $this->img_url . 'icons/';

		/**
		 * Below condition only loads the require class if Q2A set
		 * the Snow theme as site theme.
		 * If you change the theme name from `Snow` to anything, make sure to
		 * change in below condition
		 */
		if (qa_opt('site_theme') === 'SnowFlat') {
			require_once('inc/qam-snow-theme.php');
		}
	}

	/**
	 * Adding aditional meta for responsive design
	 *
	 * @since Snow 1.4
	 * @global type $qam_snow
	 */
	public function head_metas()
	{
		$this->output('<meta name="viewport" content="width=device-width, initial-scale=1">');
		qa_html_theme_base::head_metas();
	}

	/**
	 * Adding theme stylesheets
	 *
	 * @since Snow 1.4
	 */
	public function head_css()
	{
		// add RTL CSS file
		if ($this->isRTL)
			$this->content['css_src'][] = $this->rooturl . 'qa-styles-rtl.css?' . QA_VERSION;

		// add Ubuntu font CSS file
		$this->content['css_src'][] = 'http://fonts.googleapis.com/css?family=Ubuntu:400,700,400italic,700italic';

		qa_html_theme_base::head_css();

		// output some dynamic CSS inline
		$this->output($this->head_inline_css());
	}

	/**
	 * Adding theme javascripts
	 *
	 * @since Snow 1.4
	 */
	public function head_script()
	{
		$jsUrl = $this->rooturl . $this->js_dir . 'snow-core.js?' . QA_VERSION;
		$this->content['script'][] = '<script src="' . $jsUrl . '"></script>';

		qa_html_theme_base::head_script();
	}

	/**
	 * Adding point count for logged in user
	 *
	 * @since Snow 1.4
	 * @global array $qam_snow
	 */
	public function logged_in()
	{
		global $qam_snow;
		qa_html_theme_base::logged_in();

		$this->output($qam_snow->headers['user_points']);
	}

	/**
	 * Adding sidebar for mobile device
	 *
	 * @since Snow 1.4
	 */
	public function body()
	{
		if (qa_is_mobile_probably()) {

			$this->output('<div id="qam-sidepanel-toggle"><i class="icon-left-open-big"></i></div>');
			$this->output('<div id="qam-sidepanel-mobile">');
			qa_html_theme_base::sidepanel();
			$this->output('</div>');
		}
		qa_html_theme_base::body();
	}

	/**
	 * Adding body class dynamically
	 *
	 * override to add class on admin/approve-users page
	 *
	 * @since Snow 1.4
	 * @return string body class
	 */
	public function body_tags()
	{
		global $qam_snow;

		$class = 'qa-template-' . qa_html($this->template);

		if (isset($this->content['categoryids'])) {
			foreach ($this->content['categoryids'] as $categoryid)
			{
				$class .= ' qa-category-' . qa_html($categoryid);
			}
		}

		// add class if admin/appovoe-users page
		if (($this->template === 'admin') && (qa_request_part(1) === 'approve')) {
			$class .= ' qam-approve-users';
		}

		$class .= ' qam-body-' . $qam_snow->fixed_topbar;

		$this->output('class="' . $class . ' qa-body-js-off"');
	}

	/**
	 * login form
	 *
	 * @since Snow 1.4
	 * @global array $qam_snow
	 */
	public function nav_user_search()
	{
		// outputs login form if user not logged in
		global $qam_snow;

		$this->output('<div class="qam-account-items-wrapper">');

		$this->output($qam_snow->headers['user_account']);

		$this->output('<div class="qam-account-items clearfix">');

		if (!qa_is_logged_in()) {
			$login = @$this->content['navigation']['user']['login'];

			if (isset($login) && !QA_FINAL_EXTERNAL_USERS) {
				$this->output(
						'<!--[Begin: login form]-->',
						'<form id="qa-loginform" action="' . $login['url'] . '" method="post">',
						'<input type="text" id="qa-userid" name="emailhandle" placeholder="' . trim(qa_lang_html('users/email_handle_label'), ':') . '" />',
						'<input type="password" id="qa-password" name="password" placeholder="' . trim(qa_lang_html('users/password_label'), ':') . '" />',
						'<div id="qa-rememberbox"><input type="checkbox" name="remember" id="qa-rememberme" value="1" />',
						'<label for="qa-rememberme" id="qa-remember">' . qa_lang_html('users/remember') . '</label></div>',
						'<input type="hidden" name="code" value="' . qa_html(qa_get_form_security_code('login')) . '" />',
						'<input type="submit" value="' . $login['label'] . '" id="qa-login" name="dologin" />',
						'</form>',
						'<!--[End: login form]-->'
				);

				unset($this->content['navigation']['user']['login']); // removes regular navigation link to log in page
			}
		}

		qa_html_theme_base::nav('user');
		$this->output('</div> <!-- END qam-account-items -->');
		$this->output('</div> <!-- END qam-account-items-wrapper -->');
	}

	/**
	 * modifying markup for topbar
	 *
	 * @since Snow 1.4
	 */
	public function nav_main_sub()
	{
		$this->output('<div class="qam-main-nav-wrapper clearfix">');
		$this->output('<div class="sb-toggle-left qam-menu-toggle"><i class="icon-th-list"></i></div>');
		$this->logo();
		$this->nav('main');
		$this->nav_user_search();
		$this->output('</div> <!-- END qam-main-nav-wrapper -->');
		$this->nav('sub');
	}

	/**
	 * The method has been overridden just to remove the '-' from the note
	 * for the category page (notes). I know it is not good idea to override
	 * this just for '-' it. But I did
	 * intentionally to avoid such issue during the updates.
	 *
	 * @since Snow 1.4
	 * @param type $navlink
	 * @param type $class
	 */
	public function nav_link($navlink, $class)
	{
		if (isset($navlink['url'])) {
			$this->output(
					'<a href="' . $navlink['url'] . '" class="qa-' . $class . '-link' .
					(@$navlink['selected'] ? (' qa-' . $class . '-selected') : '') .
					(@$navlink['favorited'] ? (' qa-' . $class . '-favorited') : '') .
					'"' . (strlen(@$navlink['popup']) ? (' title="' . $navlink['popup'] . '"') : '') .
					(isset($navlink['target']) ? (' target="' . $navlink['target'] . '"') : '') . '>' . $navlink['label'] .
					'</a>'
			);
		} else {
			$this->output(
					'<span class="qa-' . $class . '-nolink' . (@$navlink['selected'] ? (' qa-' . $class . '-selected') : '') .
					(@$navlink['favorited'] ? (' qa-' . $class . '-favorited') : '') . '"' .
					(strlen(@$navlink['popup']) ? (' title="' . $navlink['popup'] . '"') : '') .
					'>' . $navlink['label'] . '</span>'
			);
		}

		if (strlen(@$navlink['note'])) {

			$qam_note_class = '';
			if (strpos($navlink['note'], '> -') !== false) {
				$qam_note_class = !empty($navlink['note']) ? ' qam-cat-note' : NULL;
			}

			// search and replace within the string
			$search = array(' - <', '> - ');
			$replace = array(' <', '> ');
			$output = $this->output('<span class="qa-' . $class . '-note ' . $qam_note_class . '">' . str_replace($search, $replace, $navlink['note']) . '</span>');
		}
	}

	/**
	 * Rearranges the layout:
	 * - Swaps the <tt>main()</tt> and <tt>sidepanel()</tt> functions
	 * - Moves the header and footer functions outside qa-body-wrapper
	 * - Keeps top/high and low/bottom widgets separated
	 *
	 * @since Snow 1.4
	 */
	public function body_content()
	{
		$this->body_prefix();
		$this->notices();

		$this->widgets('full', 'top');
		$this->header();

		$this->output('<div class="qa-body-wrapper">', '');
		$this->widgets('full', 'high');

		$this->output('<div class="qa-main-wrapper">', '');
		$this->main();
		$this->sidepanel();
		$this->output('</div> <!-- END main-wrapper -->');

		$this->widgets('full', 'low');
		$this->output('</div> <!-- END body-wrapper -->');

		$this->footer();

		$this->body_suffix();
	}

	/**
	 * Header in full width top bar
	 *
	 * @since Snow 1.4
	 */
	public function header()
	{
		global $qam_snow;

		$this->output('<div id="qam-topbar" class="clearfix ' . $qam_snow->fixed_topbar . '">');
		$this->nav_main_sub();
		$this->output('</div><!-- END qam-topbar -->');

		$this->output($qam_snow->headers['ask_button']);
		$this->qam_search('the-top', 'the-top-search');
	}

	/**
	 * Footer in full width bottom bar
	 *
	 * @since Snow 1.4
	 */
	public function footer()
	{
		// to replace standard Q2A footer
		global $qam_snow;

		$this->output($qam_snow->footer_custom_content);
		$this->output('<div class="qam-footer-box">');

		$this->output('<div class="qam-footer-row">');
		$this->widgets('full', 'bottom');
		$this->output('</div> <!-- END qam-footer-row -->');

		qa_html_theme_base::footer();
		$this->output('</div> <!-- END qam-footer-box -->', '');
	}

	/**
	 * Overridden to customize layout and styling
	 *
	 * @since Snow 1.4
	 */
	public function sidepanel()
	{
		// removes sidebar for user profile pages
		if (($this->template != 'user') && !qa_is_mobile_probably()) {
			$this->output('<div class="qa-sidepanel">');
			$this->qam_search();
			$this->widgets('side', 'top');
			$this->sidebar();
			$this->widgets('side', 'high');
			$this->nav('cat', 1);
			$this->widgets('side', 'low');
			$this->output_raw(@$this->content['sidepanel']);
			$this->feed();
			$this->widgets('side', 'bottom');
			$this->output('</div>', '');
		}
	}

	/**
	 * To provide various color option
	 *
	 * @since Snow 1.4
	 * @global array $qam_snow
	 */
	public function sidebar()
	{
		global $qam_snow;

		$sidebar = @$this->content['sidebar'];

		if (!empty($sidebar)) {
			$this->output('<div class="qa-sidebar wet-asphalt ' . $qam_snow->welcome_widget_color . '">');
			$this->output_raw($sidebar);
			$this->output('</div>', '');
		}
	}

	/**
	 * To add close icon
	 *
	 * @since Snow 1.4
	 * @param array $q_item
	 */
	public function q_item_title($q_item)
	{
		$this->output(
				'<div class="qa-q-item-title">',
				// add closed note in title
				empty($q_item['closed']) ? '' : '<img src="' . $this->rooturl . $this->icon_url . '/closed-q-list.png" class="qam-q-list-close-icon" alt="question-closed" title="' . qa_lang('main/closed') . '" />', '<a href="' . $q_item['url'] . '">' . $q_item['title'] . '</a>', '</div>'
		);
	}

	/**
	 * To add RSS feeds icon and closed icon for closed questions
	 *
	 * @since Snow 1.4
	 */
	public function title()
	{
		$q_view = @$this->content['q_view'];

		// RSS feed link in title
		$feed = @$this->content['feed'];
		if (!empty($feed)) {
			$this->output('<a href="' . $feed['url'] . '" title="' . @$feed['label'] . '"><i class="icon-rss qam-title-rss"></i></a>');
		}

		// link title where appropriate
		$url = isset($q_view['url']) ? $q_view['url'] : false;

		// add closed image
		$closed = (!empty($q_view['closed']) ?
						'<img src="' . $this->rooturl . $this->icon_url . '/closed-q-view.png" class="qam-q-view-close-icon" alt="question-closed" width="24" height="24" title="' . qa_lang('main/closed') . '" />' : NULL );

		if (isset($this->content['title'])) {
			$this->output(
					$closed, $url ? '<a href="' . $url . '">' : '', $this->content['title'], $url ? '</a>' : ''
			);
		}
	}

	/**
	 * To add view counter
	 *
	 * @since Snow 1.4
	 * @param array $q_item
	 */
	public function q_item_stats($q_item)
	{ // add view count to question list
		$this->output('<div class="qa-q-item-stats">');

		$this->voting($q_item);
		$this->a_count($q_item);
		qa_html_theme_base::view_count($q_item);

		$this->output('</div>');
	}

	/**
	 * Prevent display view counter on usual place
	 *
	 * @since Snow 1.4
	 * @param type $q_item
	 */
	public function view_count($q_item)
	{ // Prevent display view counter on usual place
	}

	/**
	 * To add view counter
	 *
	 * @since Snow 1.4
	 * @param type $q_view
	 */
	public function q_view_stats($q_view)
	{
		$this->output('<div class="qa-q-view-stats">');

		$this->voting($q_view);
		$this->a_count($q_view);
		qa_html_theme_base::view_count($q_view);

		$this->output('</div>');
	}

	/**
	 * To modify user whometa, move to top
	 *
	 * @since Snow 1.4
	 * @param type $q_view
	 */
	public function q_view_main($q_view)
	{
		$this->output('<div class="qa-q-view-main">');

		if (isset($q_view['main_form_tags']))
			$this->output('<form ' . $q_view['main_form_tags'] . '>'); // form for buttons on question

		$this->post_avatar_meta($q_view, 'qa-q-view');
		$this->q_view_content($q_view);
		$this->q_view_extra($q_view);
		$this->q_view_follows($q_view);
		$this->q_view_closed($q_view);
		$this->post_tags($q_view, 'qa-q-view');

		$this->q_view_buttons($q_view);
		$this->c_list(@$q_view['c_list'], 'qa-q-view');

		if (isset($q_view['main_form_tags'])) {
			$this->form_hidden_elements(@$q_view['buttons_form_hidden']);
			$this->output('</form>');
		}

		$this->c_form(@$q_view['c_form']);

		$this->output('</div> <!-- END qa-q-view-main -->');
	}

	/**
	 * To move user whometa to top in answer
	 *
	 * @since Snow 1.4
	 * @param type $a_item
	 */
	public function a_item_main($a_item)
	{
		$this->output('<div class="qa-a-item-main">');

		$this->post_avatar_meta($a_item, 'qa-a-item');

		if (isset($a_item['main_form_tags']))
			$this->output('<form ' . $a_item['main_form_tags'] . '>'); // form for buttons on answer

		if ($a_item['hidden'])
			$this->output('<div class="qa-a-item-hidden">');
		elseif ($a_item['selected'])
			$this->output('<div class="qa-a-item-selected">');

		$this->a_selection($a_item);
		$this->error(@$a_item['error']);
		$this->a_item_content($a_item);

		if ($a_item['hidden'] || $a_item['selected'])
			$this->output('</div>');

		$this->a_item_buttons($a_item);

		$this->c_list(@$a_item['c_list'], 'qa-a-item');

		if (isset($a_item['main_form_tags'])) {
			$this->form_hidden_elements(@$a_item['buttons_form_hidden']);
			$this->output('</form>');
		}

		$this->c_form(@$a_item['c_form']);

		$this->output('</div> <!-- END qa-a-item-main -->');
	}

	/**
	 * To move user whometa to top in comment
	 *
	 * @since Snow 1.4
	 * @param type $c_item
	 */
	public function c_item_main($c_item)
	{
		$this->post_avatar_meta($c_item, 'qa-c-item');

		$this->error(@$c_item['error']);

		if (isset($c_item['expand_tags']))
			$this->c_item_expand($c_item);
		elseif (isset($c_item['url']))
			$this->c_item_link($c_item);
		else
			$this->c_item_content($c_item);

		$this->output('<div class="qa-c-item-footer">');
		$this->c_item_buttons($c_item);
		$this->output('</div>');
	}

	/**
	 * Q2A Market attribution.
	 * I'd really appreciate you displaying this link on your Q2A site. Thank you - Jatin
	 *
	 * @since Snow 1.4
	 * @global array $qam_snow
	 */
	public function attribution()
	{
		// floated right
		$this->output(
			'<div class="qa-attribution">',
			'Snow Theme by <a href="http://www.q2amarket.com">Q2A Market</a>',
			'</div>'
		);

		qa_html_theme_base::attribution();
	}

	/**
	 * To add search-box wrapper with extra class for color scheme
	 *
	 * @since Snow 1.4
	 * @version 1.0
	 */
	public function qam_search($addon_class = FALSE, $ids = FALSE)
	{
		$default_color = 'turquoise';

		$id = (($ids) ? ' id="' . $ids . '"' : NULL);

		$this->output('<div class="qam-search ' . $default_color . ' ' . $addon_class . '" ' . $id . ' >');
		qa_html_theme_base::search();
		$this->output('</div>');
	}


	/**
	 * Dynamic <code>CSS</code> based on options and other interaction with Q2A.
	 *
	 * @since Snow 1.4
	 * @version 1.0
	 * @return string The CSS code
	 */
	private function head_inline_css()
	{
		$css = '<style>';

		$css .= ( (!qa_is_logged_in() ) ? '.qa-nav-user{margin:0 !important;}' : NULL );
		if (qa_request_part(1) !== qa_get_logged_in_handle()) {
			$css .= '@media (max-width: 979px){';
			$css .= 'body.qa-template-user.fixed, body[class^="qa-template-user-"].fixed, body[class*="qa-template-user-"].fixed{';
			$css .= 'padding-top: 118px !important;';
			$css .= '}';
			$css .= '}';
			$css .= '@media (max-width: 979px){body.qa-template-users.fixed{
				padding-top: 95px !important; }
			}
			@media (min-width: 980px){body.qa-template-users.fixed{
				padding-top: 105px !important;}
			}';
		}
		$css .= '</style>';

		return $css;
	}

	/**
	 * Question2Answer system icons info bar
	 *
	 * @since Snow 1.4
	 * @return string Info icons HTML
	 */
	private function icons_info()
	{
		$icons = array(
			'answer',
			'comment',
			'hide',
			'reshow',
			'close',
			'reopen',
			'flag',
			'unflag',
			'edit',
			'delete',
			'approve',
			'reject',
			'reply',
		);

		$icons_info = '<div class="qam-icons-info">';

		foreach ($icons as $icon) {
			$label = ucwords(qa_lang_html('question/' . $icon . '_button'));
			$icons_info .= '<div class="qam-icon-item"><span class="' . $icon . '"></span> ' . $label . '</div>';
		}
		$icons_info .= '</div> <!-- END qam-icons-info -->';

		return $icons_info;
	}


}
