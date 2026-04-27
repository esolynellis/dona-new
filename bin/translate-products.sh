#!/bin/bash
set -e
cd /www/wwwroot/dona-new
php artisan products:translate-mn --newest --limit=8000 --batch=30
