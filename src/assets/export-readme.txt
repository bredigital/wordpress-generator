WordPress uses hard links to get around, which unfortunately makes changing URLs an absolute pain. However,
the following is a 10-step process that can replace a majority of the URLs, without corrupting theme data.
There are no guarantees it will work, so allocate some time to investigate your migrated site to ensure all
is well with the site.

If all else fails, Duplicator (tell it to export only your site ID database tables).

The 10-step process of migrating your brand new site:
01. Create a database, and find a place to host the site (IIS container preferable).
02. Extract the archive into the container.
03. Import the database file within the archive into the database you created.
04. In PHPMyAdmin, find the database you created/restored.
05. In the _options table, change siteurl and home to your new URL.
06. Go to wp-admin on your new site (should now be accessible due to step 5).
07. Install this plugin - https://wordpress.org/plugins/velvet-blues-update-urls/
08. Enter the URL it had on the sandbox, and the new URL that you created.
	(e.g. URL 'http://wordpress.sandbox/52' becomes 'http://newsite.com').
09. Check to see your site still works.
10. Crack open a Dr Pepper and admire your hard work.

Sources, and more details:
	https://codex.wordpress.org/Moving_WordPress