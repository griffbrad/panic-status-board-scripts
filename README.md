Panic Status Board Scripts
==========================

Some simple scripts I use to integrate information into Panic's Status Board app on iPad.


Available Scripts
-----------------

* harvest-most-active-projects.php: Shows how many hours you've personally put into your most active projects in recent weeks.
* svn-commits.php: Show commit count for yourself and everyone else in your repo in recent weeks.


Usage
-----

0. Edit the sample config file to fit your environment.
0. Install the scripts on your server and test by hitting the URLs directly.  You should get a valid JSON response matching the format described in <http://www.panic.com/statusboard/docs/graph_tutorial.pdf>.
0. Add the URLs as sources for graphs in Status Board.


Requirements
------------

* PHP 5.3 or later
* APC (for caching Harvest API responses)


Screenshot
----------

![Status Board Screenshot](https://github.com/griffbrad/panic-status-board-scripts/raw/master/status-board-screenshot.png)
