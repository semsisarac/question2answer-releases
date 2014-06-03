=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.2 beta 1

- First beta of major feature update, 27th June 2010.

**New Features**

Functionality: 

- Added categories which can be used instead of, or along with, tags.
- Added RSS feeds for most question listing pages.
- Added deleting of hidden posts and admin button to delete all hidden.
- Added custom pages and links to be shown in navigation tabs.
- Added detailed permissions settings for most operations.
- Added new Expert and Moderator user levels.

Security: 

- Added optional email confirmation for registered users.
- Added display of IP addresses on anonymous posts.
- Added pages which show recent activity from an IP address.
- Added display of IP addresses of recent activity on user pages (for moderators or admins).
- Added ability to block users and/or IP addresses.
- Added bad word filter to censor out selected words.
- Added rate limit for login attempts per IP address.

Layout: 

- Added appropriate META description and keywords tags on question pages.
- Added option to set explicit META description tag for front page.
- Added option to show a custom home page instead of recent questions and answers.
- Added option to show custom HTML in the side panel, below the sidebar box.
- Added options to hide most top navigation tabs.
- Added activity page that combines recent questions, answers and comments.
- Added display of number of up and down votes cast on user pages.
- Added option to hide age of questions, answer and comments.

Miscellaneous: 

- Added separate points settings for users casting up and down votes.
- Added option for maximum number of characters in question title.
- Added option for maximum number of question tags allowed.
- Added option for whether to show the selected answer first.

**Other Changes**

- Most question titles link consistently to the top of question pages.
- Added separate 'answered' or 'commented' link where appropriate.
- Main admin tab navigates back to the last viewed admin page.
- Added character prefix before element IDs in HTML to pass formal HTML validation.
- Added 404 header and suggestion of what to do next on 'Page not found' pages.
- Hidden questions now shown as grayed out text instead of using diagonal 'hidden' background.
- Added hyphen separators in email notifications to make user content more prominent.
- Added setting in qa-config.php to switch off Gzip HTML compression.
- Added header to Ajax responses to work around servers that send unexpected prefixes.
- All database indexes and foreign keys now named explicitly, to make future upgrades easier.

**Bug Fixes**

- Clicking log in or register from a page with a URL parameter now keeps that parameter.
- After running an ALTER TABLE query, relock MySQL tables, since locks can be lost.
- Changed queries to work around LEFT JOIN bug in MySQL 5.1.46 and 5.1.47.



.. _Question2Answer: http://www.question2answer.org/
