=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.3 beta 1

- First beta of major feature update, 4th November 2010, now licensed under GPL.

**New Features**

- Added plugin architecture with four initial plugin types: login, page, editor, viewer.
- Added WYSIWYG text editor plugin based on CKEditor.
- Added user avatars, either uploaded directly or from Gravatar.
- Added support for Facebook single sign-on via a login plugin.
- Added custom fields on user profile pages.
- Added custom user titles based on points.
- Added XML Sitemap for search engines via a page plugin.
- Added usernames of authors to all email notifications.
- Added permalinks to answers and comments on question pages.
- Added 'Edit this page' button on custom pages for administrators.
- Added maintenance mode to allow site to be temporarily taken down.
- Added links to canonical URLs in <HEAD> of question pages (for SEO).
- Added RSS auto-discovery in <HEAD> of all appropriate listing pages.
- Added version information to 'Stats' page in 'Admin' panel.

**Other Changes**

- The 'All Activity' page now includes recently edited posts.
- All POST requests are redirected to prevent browser warnings when refreshing or clicking back.
- Apostrophes within words are now ignored when indexing, searching or building search-friendly URLs.
- User pages now show combined recent activity instead of separate questions and answers.
- IP address pages now show more detailed activity, including recent edits.

**Bug Fixes**

- Clicking 'I wrote this' updates the last editing user if appropriate.
- 'Page not found' error no longer shown for tag 0 (zero).
- Hidden follow-on questions are now shown to admins, and must now be deleted before their parent answer.
- Fixed mislabelling of anonymous posts as 'by me' in some versions of PHP.

**Source Code Changes**

- relevant for those who have modified the Q2A core
- The first parameter (usually $db) for the database connection has been removed from all functions.
- To make a direct query to the Q2A database, use qa_db_connection() to retrieve the connection.
- All options are now retrieved for every page request so qa_options_set_pending() is no longer necessary.
- To retrieve an option, use qa_opt('OPTION_NAME') instead of qa_get_option($db, 'OPTION_NAME').
- Page content is supplied by return from page files, rather than being set in the global $qa_content.
- Many parameters for qa_post_html_fields() and related functions were combined in the $options array parameter.
- Most Q/A/C validation, creation, modification and indexing functions have additional parameters.



.. _Question2Answer: http://www.question2answer.org/
