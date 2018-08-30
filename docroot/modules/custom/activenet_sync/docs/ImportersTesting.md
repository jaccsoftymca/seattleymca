# Importers Testing

For testing import you can use this page - /admin/config/system/activenet-sync-single-item-import

It provide item importing from activenet and flexreg by ID or from JSON.

If you want to import item by ID use next values:
- activenet: use ActiveNet:assetGuid
- flexreg: dbo.DCPROGRAMSESSIONS.DCPROGRAMSESSION_ID

If you want to import item from JSON please use example json or raw data from cache entity.

Also you can get values from already imported content - /admin/structure/sync_cache

Click "Edit" and any imported item. For testing you can use "Raw data" and "External ID" fields values. Imported content will update only if values of fields "Raw data" or "Raw data hash" will be changed.