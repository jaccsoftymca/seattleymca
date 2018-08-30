# Activenet Importer

#### Import steps:

1) **Fetcher.** Get data from ActiveNet and Datawarehouse.
Filter not valid assets:
- Pull in only activities where assetComponents is null in the API.
- Skip assets that contain "dcprogram_id".
- Skip assets that have "registration-closed" salesStatus.

2) **Proxy.** 
- Create typed data from raw data.
- Check existing cache entity by external ID (assetGuid). 
- Create/update sync cache entity for each asset. 
- Skip updating if asset data has not been modified. 
- Set status pending_import/pending_update

3) **Pusher.**
- Check existing class by AssetName (field title).
- Check existing session by assetGuid (field external_id)
- Create/update sessions and classes.
- Validate asset, put errors to “Sync errors”
- set in sync cache entity status (Failed, Imported or Detached)
- Add created class/session to “activenet_sync_proceed_imported_nodes” queue (later it will recreate session instances for sessions )


#### Import Statuses:

| **Status**        | **Description**           |
| ------------- |:-------------:|
| Pending import    |  Proxy step: was created new sync cache entity |
| Pending update    |  Proxy step: existing sync cache entity was updated in activenet |
| Pending delete    |  - |
| Failed    |  class or session not created |
| Imported    |  class and session imported successfully |
| Detached    | During the nodes creation we get next errors: Empty location name / Location not found / Empty values from DataWarehouse: DEPARTMENT_NAME, CATEGORYNAME, SUBCATEGORYNAME / Activity not found|