#!/bin/sh
set -eu

php /var/www/html/scripts/setup.php
exec apache2-foreground

