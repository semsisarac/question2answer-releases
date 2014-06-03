=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.5.3

- New default theme plus minor fixes and enhancements, 26th September 2012.

**New Features**

- Added new Snow theme by Q2A Market, selected by default in new installations.
- Added loading spinners for all Ajax page actions awaiting a server response (except voting).
- Added support for OpenSearch, allowing Q2A sites to be searched directly from browsers and search engines.
- Added support for avatars with external user management, including when integrating with WordPress.
- Added support for automatic detection and linking of URLs within HTML formatted content.

**Bug Fixes**

- Fixed MySQL error when adding events under external user integration with textual user identifiers.
- Fixed incorrect highlighting of custom page links if their path begins with the path of the current page.
- Fixed some HTML validation errors by changing HTML tags and ordering.
- Fixed occasional PHP notice when viewing an IP address page.
- Prevented posts belonging to deleted users from being claimable by other users.
- Fixed dual use of the CSS class qa-nav-sub-selected.
- Fixed display of names containing a forward slash (/) in the category menu.
- Fixed PHP notice displayed if captchas are activated but no captcha module is available.
- Prevented the IP address of the approver of a post from being assigned to the post's author.
- Fixed auto-complete for tags containing ampersands.
- Fixed the number of tags available for auto-complete (now 1,000 by default).
- Fixed the 'Delete hidden posts' button leaving behind some hidden questions without dependents.
- Fixed MySQL error when indexing a post containing a very long word, if MySQL is in strict mode.
- Prevented caret jumping into tags field when asking a question in Internet Explorer.
- Fixed momentary expansion of the page header casued by the Facebook Login button.
- Fixed censoring of single-character Chinese, Japanese and Korean (CJK) ideographic words.
- Question pages which are paginated now include the page number in their canonical URLs.

**Other Changes**

- Added new CSS classes on question or answer lists where all voting is disabled.
- Added new CSS class to identify zero answer counts in question lists.
- Added new <SPAN> and CSS class to wrap post avatars and meta information.
- Updated to CKEditor 3.6.4 and jQuery 1.7.2.
- Prevented maximum login rate being set to zero, which could lock administrators out.
- Blocked IP addresses can no longer send messages through the feedback form.
- Restricted the qa-check-lang.php language checking script to administrators only.
- Added parameter to qa_sanitize_html() to distinguish between storage and display.



.. _Question2Answer: http://www.question2answer.org/