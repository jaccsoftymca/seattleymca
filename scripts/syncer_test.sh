#!/bin/sh
/usr/local/bin/drush8 @ymcaseattle.test ev 'ymca_sync_run("activenet_sync.activenet.syncer", "proceed");' || true && /usr/local/bin/drush8 @ymcaseattle.test ev 'ymca_sync_run("activenet_sync.flexreg.syncer", "proceed");' || true
