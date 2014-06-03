=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.6.2

- More minor bug fixes on the 1.6 release, 31st July 2013.

\

- Linked 'asked' word on question pages to the question page itself.
- Removed navigation link to user wall if using external user integration (bug).
- Fixed PHP notices in deprecated qa_limits_remaining() function (bug).
- Worked around a bug in some versions of MySQL, which prevented avatars showing in some cases (bug).
- Fixed Apache rewriting issue that prevented viewing wall or activity tabs for usernames containing & or # characters (bug).
- Fixed PHP notice when editing a question if categories are not being used (bug).
- Stopped enforcing minimum number of tags if tags are not being used (bug).
- Fixed nonsensical hidden times showing for some posts on admin pages (bug).
- Fixed automatic recalculation of necessary database information in a few admin scenarios (bug).



.. _Question2Answer: http://www.question2answer.org/