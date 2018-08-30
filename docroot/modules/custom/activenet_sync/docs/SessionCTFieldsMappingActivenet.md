# Session CT field mapping for ActiveNet

|**Field name on site**|**ActiveNet**|
| ------------- |-------------|
| title | ActiveNet:assetName (without 'Â®'symbols) | 
| field_session_mbr_price | ActiveNet:assetPrices:priceAmt where priceType = 'Member.' If 0 used value from priceType = 'Standard charge.'  | 
| field_session_nmbr_price | ActiveNet:assetPrices:priceAmt where priceType = 'Non-member.' If 0 used value from priceType = 'Standard charge.'  | 
| field_session_class | Entity reference to class (use value returned from generateClass())  | 
| field_session_location | dbo.SITES.SITENAME for JOIN used dbo.ACTIVITIES.ACTIVITYNUMBER | 
| field_prerequisite | Set TRUE if ActiveNet:assetDescriptions:0:description contains 'Prerequisites' text. By default - FALSE | 
| field_external_id | ActiveNet:assetGuid | 
| field_session_gender | ActiveNet:regReqGenderCd ('c' => 'coed', 'm' => 'male', 'f' => 'female')| 
| field_session_max_age | ActiveNet:regReqMaxAge | 
| field_session_min_age | ActiveNet:regReqMinAge | 
| field_spots_available | ActiveNet:assetQuantity:availableCnt (If dbo.ACTIVITIES.IGNOREMAXIMUM is -1 reset value to 0) | 
| field_spots_allowed | ActiveNet:assetQuantity:capacityNb (If dbo.ACTIVITIES.IGNOREMAXIMUM is -1 reset value to 0) | 
| field_session_online | ActiveNet:assetLegacyData:onlineRegistration (return TRUE if onlineRegistration is 'true' or '1'. Return FALSE if expired dbo.ACTIVITYREGISTRATIONWINDOWS.INTERNET_END_DATE.) | 
| field_session_reg_link | "https://apm.activecommunities.com/seattleymca/Activity_Search/$asset_name_formatted/$activity_id", where $asset_name_formatted is ActiveNet:assetName in url format, $activity_id - digits from ActiveNet:assetLegacyData:substitutionUrl | 
| field_sales_status | dbo.ACTIVITIES.ACTIVITYSTATUS (If status <> 0 the session’s Sales Status is Closed.)| 
| field_session_ticket | TRUE if ActiveNet:assetName contains '*' symbol | 
| field_physical_location_text | dbo.FACILITIES.FACILITYNAME (JOIN by dbo.ACTIVITIES.FACILITY_ID)| 
| field_allow_waitlist | dbo.ACTIVITIES.ALLOW_WAIT_LISTING  | 
| field_session_time | Paragraph | 
| field_session_time:field_session_time_actual | FALSE if dbo.ACTIVITIES.NO_MEETING_DATES = '-1' | 
| field_session_time:field_session_time_date | value = ActiveNet:activityRecurrences:activityStartDate + ActiveNet:activityRecurrences:startTime ; end_value =ActiveNet:activityRecurrences:activityEndDate + ActiveNet:activityRecurrences:endTime  | 
| field_session_time:field_session_time_days | ActiveNet:activityRecurrences:days | 
| field_session_time:field_session_time_frequency | ActiveNet:activityRecurrences:frequency:frequencyName | 
| field_session_exclusions | value = ActiveNet:activityRecurrences:activityExclusions:exclusionStartDate;  end_value = ActiveNet:activityRecurrences:activityExclusions:exclusionEndDate | 
| field_sales_date | value = earliest related dbo.ACTIVITYREGISTRATIONWINDOWS.MEMBER_INTERNET_DATE or dbo.ACTIVITYREGISTRATIONWINDOWS.INTERNET_DATE; end_value = latest related dbo.ACTIVITYREGISTRATIONWINDOWS.INTERNET_END_DATE | 


ActiveNet:* - json data from ActiveNet (for example - [ActiveNet first asset](http://api.amp.active.com/v2/search?api_key=a293e4zcrk4spwfyw8fxnh9r&organization.organizationGuid=36f3a71e-0df6-4b3a-bc50-001f7e1d546b&current_page=1&per_page=1))

dbo.* - data from Datawarehouse mssql database