=========================
Question2Answer Releases
=========================
Question2Answer_ (Q2A) is a popular open source Q&A platform for PHP/MySQL.

--------------
Release Notes
--------------
Version 1.6.3

- More minor bug fixes and interface tweaks, 19th January 2014.

\

- Combined sub-navigation for user profile and account/favorites pages.
- Allowed any wall post (beyond the first page) to be deleted.
- Excluded ( ) [ ] brackets when automatically detecting URLs.
- Allowed changes of category to be performed as a silent edit.
- Fixed recalculation of user points when using external user integration with textual userids.
- Stopped sending passwords out in user registration emails.
- Filtered out <FORM> tags from being displayed in the content of posts.
- Excluded irrelevant tables when calculating database size for admin/stats page.
- Excluded closed questions from listings on the 'Unanswered' page.
- Increment the counters which limit rate of posting when posts are queued, not approved.
- Send the direct link for a comment (instead of its parent) in notification emails regarding comments.
- Applied word censoring to the contents of wall posts sent out in email notifications.
- Applied word censoring to the titles of items in search results.
- Applied word censoring when showing tags in the tag cloud widget.
- Fixed WordPress integration (and sample external user integration code) to work with any Q2A URL structure.
- Prevented blocked users from changing their user profile.
- Fixed PHP warning on several different pages if all extra user fields are removed.
- Added an indication if a user is blocked on their account page.
- Added user email confirmation status to the admin page for moderating user registrations.
- Show maximum rate for wall posts and private messages in admin panel in all appropriate cases.
- Allowed custom pages to be saved and viewed directly in a single click.
- Ensured that no category can be specified when asking a question, if admin permits this.
- Fixed PHP warning on ask page if tags are not being used on a Q2A site.
- Fixed bug in 'queued' parameter passed to filter modules when determining whether a new post has to be moderated.
- Only show category links on Unanswered page if QA_ALLOW_UNINDEXED_QUERIES is set to true.
- Fixed several minor issues in the Snow theme.
- Improved behavior of WYSIWYG editor in non-Javascript or Android browsers.



.. _Question2Answer: http://www.question2answer.org/