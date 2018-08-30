# Flexreg Importer

#### Import steps:

1) **Fetcher.** 
- Get data from Datawarehouse (query condition - `programs.HIDEONINTERNET=0 AND programs.STATUS=0 AND departments.DEPARTMENT_NAME!='YMCA Staff Training'`).
- Get Program Fee Amounts for loaded data.

2) **Proxy.** 
- Create typed data from raw data.
- Check existing cache entity by external ID (ps_id - DCPROGRAMSESSION_ID). 
- Create/update sync cache entity for each asset. 
- Skip updating if asset data has not been modified. 
- Set status pending_import/pending_update

3) **Pusher.**
- Check existing class by program_name (field title).
- Check existing session by session_id (field external_id)
- Create/update sessions and classes.
- Validate asset, put errors to “Sync errors”
- set in sync cache entity status (Failed, Imported or Detached)
- Add created class/session to “activenet_sync_proceed_imported_nodes” queue (later it will recreate session instances for sessions )


#### Import Statuses:

| **Status**        | **Description**           |
| ------------- |:-------------:|
| Pending import    |  Proxy step: was created new sync cache entity |
| Pending update    |  Proxy step: existing sync cache entity was updated in datawarehouse |
| Pending delete    |  - |
| Failed    |  class or session not created |
| Imported    |  class and session imported successfully |
| Detached    | During the nodes creation we get next errors: Empty values from DataWarehouse: department_name, category_name, sub_category_name / Activity not found / Empty location name / Location not found|