#!/bin/bash

# Run composer update
composer update --no-dev --optimize-autoloader

# Start Apache
apache2-foreground
