=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.3.2

- Small improvements and bug fixes plus more changes for theme and plugin developers, 14th March 2011.

**Bug Fixes**

- Fixed Facebook Login plugin which was retrieving partial user details after change in Facebook API.
- Worked around MySQL bug which caused truncation of long usernames containing non-ASCII characters.

**Other Changes**

- Identify Chinese, Japanese and Korean (CJK) ideographs as separate words for indexing and searching.
- Allow automatic login ('Remember me') to the same account from multiple browsers.
- Searching by username now works with multi-word usernames, if the search query contains nothing else.

**Source Code Changes**

- All HTML is output from within the theme class, to allow full control by custom themes.
- External PHP code no longer needs to define QA_BASE_DIR or call qa_base_db_connect().
- An error failure handler is no longer required to connect to the database (default automatically used).



.. _Question2Answer: http://www.question2answer.org/
