#!/bin/sh
set -eu

cd /home/site/wwwroot
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
cp azure/nginx.conf /etc/nginx/sites-available/default
nginx -t
service nginx reload
