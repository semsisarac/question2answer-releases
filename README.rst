=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.5 beta 2

- Mainly bug fixes from the first beta, 4th January 2012.

**New Features**

- Added optional minimum required PHP version to plugin metadata.
- Added event module event to indicate when an IP address is blocked or unblocked.
- Added functions in qa-app-users.php to map between userids and handles, for plugin developers. 

**Bug Fixes**

- Added support for widgets on 'My Favorites' and 'My Updates' page.
- Prevented server configuration errors if Apache does not have mod_rewrite.
- If a filter module modifies the content of a post, send updated plain text to later filter modules.
- Hid some admin options that were not relevant due to the settings of other options.
- Included custom page content when reindexing a site, for third party search modules.
- Added call to unindex_post() in search modules when reindexing a site.
- Fixed problem creating tables while upgrading the database in MySQL 5.5 or later.
- Fixed reference to QA_DEBUG_PERFORMANCE before it was defined in qa-index.php.
- Fixed display of the default avatar on anonymous posts.
- Fixed error when using Facebook Login plugin on PHP 4.x - plugin now explicitly requires PHP 5.
- Fixed undefined function error from qa_get_logged_in_points() when integrating with external users.
- Worked around MySQL bug which could cause custom pages to be unavailable to users who are not logged in.
- Added qa-nav-\*-selected CSS class to custom links when appropriate.
- Allowed usernames to be interpreted case-insensitively when integrating with external users.
- Fixed display of usernames on questions in full-width related questions widget.
- Fixed Javascript bug when using Facebook Login plugin in Internet Explorer 6.
- Fixed problem where refresh was required to log in or log out with Facebook in some browsers.
- Fixed some layout and graphics issues in Internet Explorer 6.

**Other Changes**

- Allow moderation of posts by users with few points, even if anonymous posts aren't moderated.
- Allow captcha to be shown for posts by unconfirmed users, even if anonymous posts don't have a captcha.
- Added $categoryid parameter to index_post() function in search modules.
- Added support for new move_post() function in search modules for changing a post's category.
- Added $request parameter to index_page() function in search modules.
- The Javascript returned by update_script() in editor modules is now always called before post submission.



.. _Question2Answer: http://www.question2answer.org/