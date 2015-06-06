#!/bin/sh
. $(dirname $0)/env.sh
$PHP $FOLDER/index.php cron/init
