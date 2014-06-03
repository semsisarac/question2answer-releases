=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.4 developer preview

- Preview of new plugin functionality coming in version 1.4, plus other small changes, 4th April 2011.

**New Features**

- Added support for event modules in plugins, which are notified about important Q2A events.
- Added support for widget modules in plugins, which show extra pieces of content on Q2A's pages.
- Added support for layers in plugins, which allow modification of the HTML for any part of a page.
- Added support for option_default() method in modules, allowing them to provide default option values.
- Added layer plugin which shows the content of questions when the user mouses over their title.
- Added widget plugin which provides a basic ask box that can be shown on many pages.
- Added widget plugin which provides basic support for Google Adsense ads.
- Added widget plugin which provides a tag cloud showing most popular tags.
- Added event plugin which logs important Q2A events to a database table and/or log files.
- Allow multiple Question2Answer sites to share a single user base, via new settings in qa-config.php.

**Other Changes**

- Index individual words within tags, so searches can now match part of multi-word tags.
- Welcome emails now also sent to users who log in via an external identity provider such as Facebook.

**Bug Fixes**

- Allow multiple Chinese, Japanese and Korean (CJK) ideographs within tags or words in question URLs.
- Fixed layout of custom pages for admin users when no side panel is shown.
- Prevent Facebook plugin showing login button if Q2A logout didn't complete.

**Source Code Changes**

- Split qa-page-home.php into separate files for each type of question list, to make it simpler.
- Updated to latest versions of Services_JSON (1.0.3) and CKEditor (3.5.2).
- Added widget-related CSS classes, which third-party themes should implement.



.. _Question2Answer: http://www.question2answer.org/
