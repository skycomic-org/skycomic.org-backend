## System Requirement
+ apache 2.2
+ node.js & npm
+ php 5.4 up
+ php extensions:
	+ curl
	+ mysqli
	+ GD
	+ mcrypt
+ mariadb / mysql
+ cronjob
+ proxy (optional) ([proxy.js](https://github.com/ensky/Proxy.js/) or squid-like proxy server)

## Setup
```
> git clone --recursive https://github.com/skycomic-org/skycomic.org.git 
```
+ apache config
	+ set up a virtual host to the project folder (e.g. http://comic.example.com)
+ PHP Config
	+ > cp index.default.php index.php # and make some changes
	+ > cd application/config
	+ copy \*.default.php to \*.php and make some changes
+ Permission
	+ make sure your WWW has write permission of application/cache and application/logs
+ database
    + import private/install.sql to your database (e.g. skycomic)
    + modify application/config/database.php
+ proxy
	+ add your proxy setting in the *proxys* table in your database
+ init database
    + run private/script/init.sh to fetch the comic index and chapters
+ cronjobs
	+ add cronjobs corresponded to private/script/cron-\*
+ build frontend
```
cd frontend
npm install
npm run bower install
npm run gulp
```

## Setup via Docker (for development only, just few steps)
See [dockerfile-skycomic](https://github.com/skycomic-org/dockerfile-skycomic)
