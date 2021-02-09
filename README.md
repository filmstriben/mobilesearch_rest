MobileSearch RESTful API
=====================

[![GitHub tag](https://img.shields.io/github/tag/filmstriben/mobilesearch_rest.svg?style=flat-square)](https://github.com/filmstriben/mobilesearch_rest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/build.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/build-status/master)

Documentation
-------------

[Read the Documentation](http://rest.filmstriben.dk/)

Requirements
------------
1. Apache/nginx web-server;
2. PHP 7.4;
3. `php-mongodb` extension;
4. composer;
5. MongoDB 4 server.

Installation
------------
1. Clone the repository.
2. ``cd PATH_TO_CLONED_REPO``;
3. Run ``composer install``;
3. Run ``composer dump-env prod``;
4. Edit the `.env.local.php` for correct mongo settings;
4. Run ``php bin/console cache:clear``.
5. ~~Optionally, run tests. See Tests section;~~
5. Setup a virtual host to point to repository `public` directory;
6. Service available @ `http://SERVICE_URL/` (this URL should be used as communication endpoint).

__TBC__

License
-------
This bundle is under the GNU GPL license.
