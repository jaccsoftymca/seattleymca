# Activenet cron jobs

1) Run activenet and flexreg importers every minute:

`* * * * * /usr/local/bin/drush ev 'ymca_sync_run("activenet_sync.activenet.syncer", "proceed");' --root=/var/www/docroot && /usr/local/bin/drush ev 'ymca_sync_run("activenet_sync.flexreg.syncer", "proceed");' --$`

2) Run activenet_sync_run_queue_worker every minute (This will recreate sessions instances for imported content):

`* * * * * /usr/local/bin/drush ev 'activenet_sync_run_queue_worker();' --root=/var/www/docroot`

3) It will run every day at midnight (This will add all sync cache entities to queue for checking on existing in ActiveNet)

`0 0 * * * /usr/local/bin/drush ev 'activenet_sync_create_check_existing_queue();' --root=/var/www/docroot`

4) Run every 10 minutes (it will delete from site sync_cache entities and sessions not existing in ActiveNet):

`*/10 * * * * /usr/local/bin/drush ev 'activenet_sync_run_sessions_cleaner_queue_worker();' --root=/var/www/docroot`
