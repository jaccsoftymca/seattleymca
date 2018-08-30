#!/bin/sh
#
# Cloud Hook: drush-cache-clear and import config
#
# Run drush cache-clear all in the target environment. This script works as
# any Cloud hook.


# Map the script inputs to convenient names.
site=$1
target_env=$2
drush_alias=$site'.'$target_env

echo "Update database"
drush @$drush_alias updb -y

echo "Import configuration"
drush @$drush_alias en config_split -y
drush @$drush_alias cim vcs -y
drush @$drush_alias config-split-import prod -y
drush @$drush_alias cim vcs -y

echo "Clear cache"
drush @$drush_alias cr
