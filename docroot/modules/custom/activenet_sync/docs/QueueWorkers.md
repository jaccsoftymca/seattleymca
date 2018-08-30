# Queue Workers

#### 1) Activenet Sync Queue Worker
Imported Sessions/class nodes additional calculation.
- Recreate Session Instances
- Calculate field_age for related class.

#### 2) Activenet Sync Sessions Cleaner Worker
Deleted from API sessions cleaner.
- Check asset existing in ActiveNet or DataWarehouse
- Delete from site session and sync cache entity if not exist in API
- **TODO:** add classes deleting if this class not have related sessions.
