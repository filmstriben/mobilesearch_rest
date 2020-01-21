MobileSearch RESTful API
=====================

[![GitHub tag](https://img.shields.io/github/tag/filmstriben/mobilesearch_rest.svg?style=flat-square)](https://github.com/filmstriben/mobilesearch_rest)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/badges/build.png?b=master)](https://scrutinizer-ci.com/g/filmstriben/mobilesearch_rest/build-status/master)

Documentation
-------------

[Read the Documentation](http://rest.filmstriben.dk/web/)

Requirements
------------
1. Apache/nginx web-server;
2. PHP 5.4+
3. php5-mongo extension
4. composer
5. MongoDB server (see below)

_Note:_ The mongo odm library - doctrine/mongodb - would expect _php-mongo_ extension
 of version at least _1.6.7_. Recent releases of this extension require
 MongoDB version 3. To fix this, either replace the _php-mongo_ extension
 to version **1.4.4** (e.g. via pecl) or use Mongo database of version 3.

Installation
------------
1. Clone the repository.
2. ``cd PATH_TO_CLONED_REPO``;
3. Run ``composer install``.
4. Run ``php app/console cache:clear --env=prod``.
5. Optionally, run tests. See Tests section;
5. Setup a virtual host to point to repository root;
6. Service available @ `http://SERVICE_URL/web/` (this URL should be used as communication endpoint).

Configuration
------------
1. Create and make sure `./web/storage` path is write-able by web-server;
2. Adjust mongodb settings in `app/config.yml`.

Tests
------------
A test suite is bundled with the application. Run the tests every time after
deploying the sources. It is safe to run the test suite in production, since
testing occurs on an isolated database. The name of the test database is suffixed
with __test_.
To run the tests, navigate to application installation directory and type:

``./bin/phpunit -c app/``

Sample output that tests passed, would lack error messages:
```
PHPUnit 5.7.27 by Sebastian Bergmann and contributors.

....................................                              36 / 36 (100%)

Time: 11.94 seconds, Memory: 66.00MB

OK (36 tests, 1449 assertions)
```

First time run
------------
Using a mongodb admin tool (e.g. Rockmongo) or mongo shell, create the required
database (as in `config.yml`, by default it's `fs`).
Create `Agency` collection and fill it with required agency credentials, e.g.:
```
{
   "agencyId": "100000",
   "key": "3fa",
   "name": "Dummy",
   "children": []
}
```

License
-------
This bundle is under the GNU GPL license.
