=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.4 beta 1

- First beta of major feature update, 25th May 2011.

**Major New Features**

- Added hierarchical sub-categories up to 4 levels deep.
- Added uploading of images and other content within WYSIWYG editor.
- Added list of hot questions, with customizable weightings for hotness calculation.
- Added basic private messaging between users.
- Added points-based permissions, i.e. actions that require a user to have a certain number of points.
- Added optional counting and display of question views.
- Added flagging of posts, including admin notifications and automatic hiding.

**Other New Features**

- Added listing of questions by most votes, most answers and most views.
- Added widget plugin which provides a count of questions, answers, comments and users.
- Added category descriptions which are shown on 'Browse Categories' page.
- Added button on IP page to hide all previous posts from a blocked IP address.
- Added button on user page to hide all previous posts from a blocked user.
- Added ability to customize URL fragments for standard Q2A pages ($QA_CONST_PATH_MAP in qa-config.php).
- Added option to open all user-entered links in a new window.
- Added option to only separate tags by commas, allowing tags to contain spaces.
- Added option not to allow users with posts to change their usernames.
- Added option to require login and/or email confirmation to view questions.
- Added option to control whether users are sent notifications by default.
- Added option to set the length of SEO-friendly question URLs.
- Added option to temporarily suspend user registrations, to help deal with spam.
- Added separate option for the length of the 'All Activity' page.
- Added user- and IP-based limits of the number of file uploads per hour.
- Added list of each user's extra privileges on their profile page.
- Improved language checker output (qa-check-lang.php) including generation of PHP for missing phrases.

**Other Changes**

- Asking a question now takes place in a single page, using Ajax for updating.
- Ranking of search results now takes question hotness into consideration.
- Large objects (BLOBs) stored in the database now record who created them, and when.
- Posts are no longer marked as edited if they were saved but their title and content were not changed.
- Ask box widget passes through the appropriate category from the page it was shown on.
- Unanswered questions can not currently be browsed by category (to save on database indexes).
- IP page now shows whether a post from a particular IP address has been hidden.

**Bug Fixes**

- Fixed security issue where other PHP code running on a shared server could spoof a Q2A login via PHP sessions.
- Fixed warnings output by PHP due to calls to date() before a timezone was set.
- Fixed inaccurate message shown when voting is only allowed on the question page.
- Fixed display of incorrect user points for recent edits on the 'All Activity' page.
- Fixed bug which always showed 10 items on user pages independent of the admin setting.
- Fixed divide-by-zero error on user pages if admin has set no points for voting.
- Fixed possible overflow of user-entered content outside the main content section.
- Fixed overflow of meta information on edited comments into the button area.
- Fixed bug (introduced in 1.4 developer preview) requiring custom themes to have a qa-theme.php file.

**Source Code Changes**

- Transitioned from jxs to the popular jQuery Javascript library, which plugins can now assume is present.
- Added qa-app-posts.php for easy manipulation of questions, answers and comments in PHP.
- Added several utility functions to suspend notifications, indexing, event reporting and updating of counts.
- Updated CKEditor to version 3.5.4.
- Q2A fatal errors are now included in the server's error log via PHP's error_log() function.
- Added CSS classes qa-nav-main-hot, qa-nav-cat..., qa-...-flags, qa-view-count... and qa-browse-cat...



.. _Question2Answer: http://www.question2answer.org/
