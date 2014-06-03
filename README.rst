=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.3 beta 2

- Addressed some minor issues and requests from the first beta, 11th November 2010.

**New Features**

- Added option to show avatars in question lists.
- Added option to show default avatar for users who have none.
- Added field showing latest available Q2A version in 'Stats' page in 'Admin' panel.

**Other Changes**

- Improved layout and auto focusing of WYSIWYG editor on some browsers.
- Added HTML integration points on the login and register pages for login plugins.
- Use MySQL's time rather than PHP's to calculate time deltas, in case the two are different.

**Bug Fixes**

- Stopped showing errors for items with hidden parents in the recent activity list on user pages.
- Show an explanatory error message if an image upload failed instead of a PHP error.
- Try using curl to access the Facebook API if file_get_contents for URLs has been disabled.
- When restoring a user's session if the PHP session is lost, restore the login source as well.



.. _Question2Answer: http://www.question2answer.org/
