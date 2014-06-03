=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.5.4

- Minor bug fixes, 29th November 2012.

**Bug Fixes**

- Prevented multiple accounts with the same email address when logging in via an external identity provider.
- Updated Facebook plugin for change in location of user avatar URLs in Facebook Graph API response.
- Fixed functionality of 'Remember' checkbox in login form at the top of the Snow theme.
- Fixed reference to background image in Candy theme CSS file.
- Fixed partial log out when multiple Q2A sites share users and logins.
- Fixed rare PHP notice when viewing an IP address page.
- Added verification of content of uploaded images in WYSIWYG editor.

**Other Changes**

- Added Ajax-style loading spinners for non-Ajax operations which perform heavy writes to the database.
- Reset the created time of moderated posts to the moment when the posts are approved.
- Updated Snow theme with several visual improvements and tweaks.
- Updated to htmLawed 1.1.14 and CKEditor 3.6.5.



.. _Question2Answer: http://www.question2answer.org/