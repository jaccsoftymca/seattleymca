# Class CT field mapping

|**Field name on site**|**ActiveNet**|**Flexreg**|
| ------------- |-------------|-------------|
| field_type | constant value: "activity" | constant value: "flexreg" |
| title | ActiveNet:assetName (without 'Â®'symbols) | dbo.DCPROGRAMS.DCPROGRAMNAME |
| field_class_description | dbo.ACTIVITIES.DESCRIPTION | dbo.DCPROGRAMS.DESCRIPTION |
| field_external_id | ActiveNet:assetTags:tag:tagName where tagDescription = 'MISCELLANEOUS' | dbo.DCPROGRAMS.DCPROGRAM_ID |
| field_class_activity | Search activity on site by this fields: dbo.ACTIVITY_DEPARTMENTS.DEPARTMENT_NAME (field_activenet_detailed_categor), dbo.RG_CATEGORY.CATEGORYNAME (field_activenet_category), dbo.RG_SUB_CATEGORY.SUBCATEGORYNAME (field_activenet_age_category)| Search activity on site by this fields: dbo.ACTIVITY_DEPARTMENTS.DEPARTMENT_NAME (field_activenet_detailed_categor), dbo.RG_CATEGORY.CATEGORYNAME (field_activenet_category), dbo.RG_SUB_CATEGORY.SUBCATEGORYNAME (field_activenet_age_category) |
| field_url | - | "https://apm.activecommunities.com/seattleymca/ActiveNet_Home?FileName=onlineDCProgramDetail.sdi&dcprogram_id=$id&online=true", where $id is dbo.DCPROGRAMS.DCPROGRAM_ID |
| field_price | - | min($price) + min($fee); $price = dbo.DCPROGRAMFEES.FEEAMOUNT (where DCPROGRAMFEES.CHARGE_TYPE=0 AND DCPROGRAMFEES.ONE_TIME_FEE!=-1); $fee = dbo.DCPROGRAMFEES.FEEAMOUNT (where DCPROGRAMFEES.CHARGE_TYPE=0 AND DCPROGRAMFEES.ONE_TIME_FEE=-1)|


ActiveNet:* - json data from ActiveNet (for example - [ActiveNet first asset](http://api.amp.active.com/v2/search?api_key=a293e4zcrk4spwfyw8fxnh9r&organization.organizationGuid=36f3a71e-0df6-4b3a-bc50-001f7e1d546b&current_page=1&per_page=1))

dbo.* - data from Datawarehouse mssql database