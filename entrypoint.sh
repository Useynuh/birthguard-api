#!/bin/bash set -e php artisan migrate --force exec apache2-foreground This script does two things when your app starts: it creates the missing database tables (migrate --force)
