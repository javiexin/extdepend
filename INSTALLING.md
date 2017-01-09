# Extension Dependency Management

## Installing

This extension will require 3.1.11, that will hopefully include all required core changes.

These are the changes required and/or recommended, and current status:

Improvements on extension management:

* [[ticket/14849] Add core.acp_extensions_run_action](https://github.com/phpbb/phpbb/pull/4578) -  
Pending, under review, modifies a previous event, that does not cover our case - Critical
* [[ticket/14918] Simplify access to extension version metadata information](https://github.com/phpbb/phpbb/pull/4580) - Merged
* [[ticket/14919] Do not directly use globals in acp_extensions](https://github.com/phpbb/phpbb/pull/4581) - Pending, reviewed - Cosmetic/cleanup
* [[ticket/14938] Inconsistency in ext_mgr all_available vs is_available](https://github.com/phpbb/phpbb/pull/4592) - Pending, reviewed - Important
* [[ticket/14940] Add ACP template event acp_ext_details_end](https://github.com/phpbb/phpbb/pull/4593) - Merged

Fixes and additions to template interface:

* [[ticket/14943] Fix template loop access by index](https://github.com/phpbb/phpbb/pull/4597) - Merged
* [[ticket/14943] Fix template loop access by index](https://github.com/phpbb/phpbb/pull/4605) - Pending, small fix over the above
* [[ticket/14944] Add search for template loop indexes by key](https://github.com/phpbb/phpbb/pull/4598) - Pending, reviewed, tests are missing
* [[ticket/14950] Add possibility to delete a template block](https://github.com/phpbb/phpbb/pull/4612) - Pending

Only critical pending are **14938** and **14849**, the others are good to have, but would not block the extension (there are alternatives to implement these).

The changes to the template system are required, but ONLY for manipulating the extension list itself.  So, it would be possible to rewrite it as well,
much more code, but as a temporary solution...

To facilitate testing, I have included the patched versions of the relevant files under the
*phpbb* folder.  Just copy it to the root of your 3.1.10 forum.  For 3.2, you will have to
patch the code yourself.  I would, of course, recommend that you review the changes before
copying them
