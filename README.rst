=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.3.1

- Source changes to help theme and plugin developers, plus a few bug fixes, 1st February 2011.

**Source Code Changes**

- Added CSS classes to <BODY> tag to allow per-category and per-template styling.
- Added extra CSS class to answers count box if a best answer has been selected.
- Pass the level (e.g. editor) of users to the theme layer for use by advanced themes.
- Allow page and editor modules to add CSS files or other HTML tags in the <HEAD> of a page.

**Bug Fixes**

- HTML sanitizer now allows Flash content (e.g. YouTube movies added via WYSIWYG editor).
- Users who log in via an external identity provider now have their last login time updated.
- Set the locale so that strtolower() doesn't break UTF-8 characters under Windows/IIS.



.. _Question2Answer: http://www.question2answer.org/
