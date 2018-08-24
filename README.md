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
1. PHP 5.4+
2. php5-mongo extension
2. composer
4. mongo database

Installation
------------
1. Clone the repository.
2. ``cd PATH_TO_CLONED_REPO``;
2. Run ``composer install``.
3. Run ``php app/console cache:clear --env=prod``.
4. Setup a virtual host to point to repository root;
5. Service available @ `http://SERVICE_URL/web/` (this URL should be used as communication endpoint).

Configuration
------------
1. Create and make sure `./web/storage` path is write-able by web-server;
2. Adjust mongodb settings in `app/config.yml`.

First time run
------------
Using a mongodb admin tool (e.g. Rockmongo) or mongo cmd, create the required database (as in `config.yml`, by default it's `fs`).
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
