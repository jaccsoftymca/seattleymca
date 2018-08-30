# ActiveNet Sync


#### Settings

By default the Syncer is not in production mode. In order to run it in production
you have to enable production mode.

To enable production mode:

  `drush cset activenet_sync.settings is_production 1`

To set activeNet page:

  `drush cset activenet_sync.settings activenet_page 1`

To set datawarehouse page:
  
  `drush cset activenet_sync.settings dw_offset 1`
 
 Also you can change this values in admin UI - /admin/config/system/activenet-sync-settings
 
 
**Activenet Client configuration:** /admin/config/system/activenet-client-settings

**Datawarehouse Client configuration:** /admin/config/system/datawarehouse-client-settings

---

#### Syncers


The module have 2 syncers: activenet, flexreg.

Set **active syncers** in /admin/config/system/ymca-sync

To run the process use the next code:

  * With PHP:
   ```php
  ymca_sync_run('activenet_sync.flexreg.syncer', 'proceed');
  ymca_sync_run('activenet_sync.activenet.syncer', 'proceed');
   ```

  * With Drush:

`drush ev 'ymca_sync_run("activenet_sync.activenet.syncer", "proceed");'`
 
 * From admin UI - /admin/config/system/activenet-sync-importers-run

---

#### Sync cache reset
 ```php
 $cache_manager = \Drupal::service('sync_cache.manager');
 $cache_manager->resetCache();
  ```
 
---

#### Sync pager
Devel:
 ```php
\Drupal::configFactory()->getEditable('activenet_sync.settings')->set('activenet_page', '1')->save(TRUE);
\Drupal::configFactory()->getEditable('activenet_sync.settings')->set('dw_offset', '1')->save(TRUE);
 ```
 Drush:
 ```
 drush config-set activenet_sync.settings activenet_page 1 -y
 drush config-set activenet_sync.settings dw_offset 1 -y
 ```
 
---