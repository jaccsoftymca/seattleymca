# Session CT field mapping for Flexreg

|**Field name on site**|**Flexreg**|
| ------------- |-------------|
| title | dbo.DCSESSIONS.DCSESSIONNAME | 
| field_session_class | Entity reference to class (use value returned from generateClass())  |
| field_session_location | dbo.SITES.SITENAME for JOIN used dbo.DCPROGRAMS.SITE_ID |
| field_external_id | dbo.DCPROGRAMSESSIONS.DCPROGRAMSESSION_ID:dbo.DCPROGRAMSESSIONS.DCSESSION_ID|
| field_session_gender | dbo.DCPROGRAMS.GENDER (0 => 'coed', 1 => 'male', 2 => 'female')|
| field_session_max_age | dbo.DCPROGRAMS.AGESMAX |
| field_session_min_age | dbo.DCPROGRAMS.AGESMIN |
| field_session_online | constant value: "TRUE" | 
| field_session_reg_link | "https://apm.activecommunities.com/seattleymca/ActiveNet_Home?FileName=onlineDCProgramDetail.sdi&dcprogram_id=$id&online=true", where $id is dbo.DCPROGRAMS.DCPROGRAM_ID | 
| field_sales_status | constant value: "open" |  
| field_session_time | Paragraph | 
| field_session_time:field_session_time_actual | constant value: "TRUE" | 
| field_session_time:field_session_time_date | value = dbo.DCSESSIONS.BEGINNINGDATE + dbo.DCSESSIONS.BEGINNINGTIME; end_value = dbo.DCSESSIONS.ENDINGDATE + dbo.DCSESSIONS.ENDINGTIME  | 
| field_session_time:field_session_time_frequency | dbo.DCSESSIONS.WEEKDAYS (Example - 0100000, active day only monday) | 


ActiveNet:* - json data from ActiveNet (for example - [ActiveNet first asset](http://api.amp.active.com/v2/search?api_key=a293e4zcrk4spwfyw8fxnh9r&organization.organizationGuid=36f3a71e-0df6-4b3a-bc50-001f7e1d546b&current_page=1&per_page=1))

dbo.* - data from Datawarehouse mssql database