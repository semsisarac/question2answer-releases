=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.4 beta 2

- Minor fixes and improvements on the first beta, 2nd June 2011.

**New Features**

- Added simple out-of-the-box integration with WordPress 3.x sites.
- Added option to remove accents from Roman letters in question URLs.

**Other Changes**

- Also allow mapping of the home page via $QA_CONST_PATH_MAP in qa-config.php.
- Changed order of elements in <HEAD> to encourage CSS files to load first.
- Pre-check there is enough memory to deal with uploaded images to prevent silent errors.
- Q2A database errors are now included in the server's error log via PHP's error_log() function.
- Redirect from URLs prefixed /qa to the home page if the pages are identical.

**Bug Fixes**

- Fixed database error when asking a question with no category via the 'Ask a Question' widget.
- Fixed error shown in browser if question form is submitted while a jQuery Ajax request is in transit.
- Fixed XML error in sitemap caused by blank lines at the end of qa-wysiwyg-upload.php.
- Fixed display of newlines in confirmation that a private message has been sent.
- Fixed error when upgrading the database if two Q2A sites are sharing the same user database.
- Fixed bug in hierarchical category selector when no sub-category is selected.



.. _Question2Answer: http://www.question2answer.org/
