<?php

namespace Drupal\activenet_sync;

use Drupal\activenet_client\ActivenetClient;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datawarehouse_client\DatawarehouseClient;

/**
 * ActivenetSyncActivenetFetcher class.
 */
class ActivenetSyncActivenetFetcher implements ActivenetSyncFetcherInterface {

  const ACTIVENET_ITEMS_PER_PAGE = 100;

  /**
   * ActivenetSyncActivenetWrapper definition.
   *
   * @var ActivenetSyncActivenetWrapper
   */
  protected $wrapper;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;


  /**
   * Site Mode.
   *
   * @var bool
   */
  protected $isProduction;

  /**
   * Active net page.
   *
   * @var int
   */
  protected $activenetPage;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Activenet response object.
   *
   * @var ActivenetClient
   */
  protected $activenetClient;

  /**
   * The Datawarehouse response array.
   *
   * @var DatawarehouseClient
   */
  protected $datawarehouseClient;

  /**
   * Database Lock.
   *
   * @var DatabaseLockBackend
   */
  protected $lock;

  /**
   * ActivenetSync Repository.
   *
   * @var \Drupal\activenet_sync\ActivenetSyncRepository
   */
  protected $repository;

  /**
   * ActivenetSyncActivenetFetcher constructor.
   *
   * @param ActivenetSyncActivenetWrapper $wrapper
   *   Wrapper.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param ActivenetClient $activenet_client
   *   Activenet Client.
   * @param DatawarehouseClient $dw_client
   *   Datawarehouse Client.
   * @param DatabaseLockBackend $lock
   *   Lock.
   * @param ActivenetSyncRepository $repository
   *   Class and Session Entity Repository.
   */
  public function __construct(
    ActivenetSyncActivenetWrapper $wrapper,
    ConfigFactory $config,
    LoggerChannelInterface $logger,
    ActivenetClient $activenet_client,
    DatawarehouseClient $dw_client,
    DatabaseLockBackend $lock,
    ActivenetSyncRepository $repository) {

    $this->wrapper = $wrapper;
    $this->config = $config;
    $this->logger = $logger;
    $this->activenetClient = $activenet_client;
    $this->datawarehouseClient = $dw_client;
    $this->lock = $lock;
    $this->repository = $repository;

    // Check the mode.
    $settings = $this->config->get('activenet_sync.settings');
    $this->isProduction = (bool) $settings->get('is_production');
    $this->activenetPage = (int) ($settings->get('activenet_page')) ? $settings->get('activenet_page') : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch(array $custom_data = NULL) {
    if (!$this->lock->acquire('activenet_sync', 500.0)) {
      // Skip import if activenet_sync locked.
      $this->wrapper->setSourceData([]);
      return;
    }

    global $_activenet_sync_disable_entity_hooks;
    $_activenet_sync_disable_entity_hooks = TRUE;

    if (!empty($custom_data) && $custom_data['type'] == 'json') {
      $results = [];
      // Use existing data (for testing).
      $custom_data_array = json_decode($custom_data['data'], TRUE);
      if ($this->validateAsset($custom_data_array) && $this->getDwActivityNumber($custom_data_array['assetTags'])) {
        $results[] = $custom_data_array;
        $this->deleteParentAssets($results);
      }
      $this->wrapper->setSourceData($results);
      return;
    }

    if (!$this->isProduction && $this->activenetPage > 1) {
      return;
    }
    $dw_asset_ids = [];
    $results = [];
    if (!empty($custom_data) && $custom_data['type'] == 'id') {
      // Load by id.
      $data = $this->activenetFetch($custom_data['data']);
    }
    else {
      $data = $this->activenetFetch(NULL);
    }

    if (!empty($data['results'])) {
      if ($this->isProduction) {
        if (count($data['results']) < self::ACTIVENET_ITEMS_PER_PAGE) {
          // Reset activenet_page on last page.
          $this->config->getEditable('activenet_sync.settings')->set('activenet_page', 1)->save();
        }
        else {
          // Set next activenet_page.
          $this->activenetPage++;
          $this->config->getEditable('activenet_sync.settings')->set('activenet_page', $this->activenetPage)->save();
        }
      }
      $ids_for_touch = [];
      foreach ($data['results'] as $key => $value) {
        if (isset($value['assetGuid'])) {
          // Add assetGuid to list of touched assets.
          $ids_for_touch[] = $value['assetGuid'];
        }
        if (!$this->validateAsset($value)) {
          // Remove not valid assets.
          unset($data['results'][$key]);
        }
        elseif (isset($value['assetGuid']) && !empty($value['assetGuid'])) {
          if ($asset_tag = $this->getDwActivityNumber($value['assetTags'])) {
            $dw_asset_ids[$key] = $asset_tag;
          }
          else {
            unset($data['results'][$key]);
          }
        }
      }

      $this->repository->touchSyncCaches($ids_for_touch, 'activenet');
      $dw_data = $this->datawarehouseFetch($dw_asset_ids);
      $results = $this->datawarehouseAddToActivenet($dw_data, $data['results'], $dw_asset_ids);
      $this->datawarehouseLocationsFetch($results, $dw_asset_ids);
      $this->datawarehouseRegistrationDatesFetch($results, $dw_asset_ids);
      $this->deleteParentAssets($results);
    }
    // Fix encoding and html special chars.
    array_walk_recursive($results, function (&$item, $key) {
      $item = htmlspecialchars_decode(utf8_encode($item));
    });

    $this->wrapper->setSourceData($results);
  }

  /**
   * Check if asset valid for import.
   *
   * @param array $asset
   *   Single Asset.
   *
   * @return bool
   *   Validation result.
   */
  private function validateAsset(array $asset) {
    $valid = FALSE;
    // Pull in only activities where assetComponents is null in the API.
    // Skip assets that contain "dcprogram_id".
    // Skip assets that have "registration-closed" salesStatus.
    if (empty($asset['assetComponents']) && $asset['salesStatus'] != 'registration-closed' && strpos($asset['assetLegacyData']['substitutionUrl'], 'dcprogram_id') === FALSE) {
      $valid = TRUE;
    }
    return $valid;
  }

  /**
   * Get Dw Activity Number.
   *
   * @param array $tags
   *   Asset tags.
   *
   * @return string|bool
   *   Valid asset tag Name.
   */
  private function getDwActivityNumber(array $tags) {
    foreach ($tags as $tag) {
      if ($tag['tag']['tagDescription'] == 'MISCELLANEOUS' && ctype_digit($tag['tag']['tagName'])) {
        return $tag['tag']['tagName'];
      }
    }
    return FALSE;
  }

  /**
   * Fetch data from activenet.
   */
  private function activenetFetch($id) {
    // Min end_date is yesterday.
    // Docs about end_date and date ranges.
    // http://developer.active.com/docs/read/v2_Activity_API_Search#ranges
    $end_date = date("Y-m-d", time() - 60 * 60 * 24) . '..';
    $params = [
      'current_page' => $this->activenetPage,
      'per_page' => self::ACTIVENET_ITEMS_PER_PAGE,
      'end_date' => $end_date,
    ];
    if ($id) {
      $params = [
        'current_page' => 1,
        'per_page' => 1,
        'assetGuid' => $id,
        'end_date' => $end_date,
      ];
    }
    return $this->activenetClient->call($params);
  }

  /**
   * Fetch data from DataWarehouse.
   *
   * @param array $dw_asset_ids
   *   GUID's from ActiveNet.
   *
   * @return array
   *   DataWarehouse data
   */
  private function datawarehouseFetch(array $dw_asset_ids) {
    $query_in_values = "'" . implode("', '", $dw_asset_ids) . "'";
    $query = "
      SELECT a.ACTIVITY_ID, a.ACTIVITYNUMBER, a.ACTIVITYNAME, a.ALLOW_WAIT_LISTING, a.NO_MEETING_DATES, a.ACTIVITYSTATUS, a.DESCRIPTION, a.IGNOREMAXIMUM, d.DEPARTMENT_NAME, c.CATEGORYNAME, s.SUBCATEGORYNAME, f.FACILITYNAME
      FROM ACTIVITIES a
      LEFT JOIN ACTIVITY_DEPARTMENTS d
      ON a.ACTIVITY_DEPARTMENT_ID = d.ACTIVITY_DEPARTMENT_ID
      LEFT JOIN RG_CATEGORY c
      ON a.RG_CATEGORY_ID = c.RG_CATEGORY_ID
      LEFT JOIN RG_SUB_CATEGORY s
      ON a.RG_SUB_CATEGORY_ID = s.RG_SUB_CATEGORY_ID
      LEFT JOIN FACILITIES f
      ON a.FACILITY_ID = f.FACILITY_ID
      WHERE ACTIVITYNUMBER IN ($query_in_values);
      ";
    return ($dw_asset_ids) ? $this->datawarehouseClient->call($query) : [];
  }

  /**
   * Fetch Parent Id's.
   *
   * @return array
   *   DataWarehouse data
   */
  private function fetchParentIds() {
    $parent_ids = [];
    if ($cache = \Drupal::cache()->get('activenet_parent_ids')) {
      $parent_ids = $cache->data;
    }
    else {
      $query = 'SELECT DISTINCT PARENTACTIVITY_ID FROM ACTIVITIES;';
      $result = $this->datawarehouseClient->call($query);
      foreach ($result as $key => $value) {
        $parent_ids[] = $value['PARENTACTIVITY_ID'];
      }
      \Drupal::cache()->set('activenet_parent_ids', $parent_ids, strtotime('+5 minutes'));
    }

    return $parent_ids;
  }

  /**
   * Insert data to activnet result.
   *
   * @param array $dw_data
   *   DataWarehouse data.
   * @param array $activenet_data
   *   ActiveNet data.
   * @param array $dw_asset_ids
   *   Mapping between tagName and key in $data['results'].
   *
   * @return array
   *   Result data (ActiveNet + DW)
   */
  private function datawarehouseAddToActivenet(array $dw_data, array &$activenet_data, array &$dw_asset_ids) {
    foreach ($dw_data as $value) {
      $activenet_data_key = array_search($value['ACTIVITYNUMBER'], $dw_asset_ids);
      if ($value['DEPARTMENT_NAME'] == 'YMCA Staff Training') {
        unset($activenet_data[$activenet_data_key]);
        unset($dw_asset_ids[$activenet_data_key]);
      }
      else {
        $activenet_data[$activenet_data_key]['dwData'] = $value;
      }
    }

    return $activenet_data;
  }

  /**
   * Fetch Locations data from DataWarehouse.
   *
   * @param array $activenet_data
   *   ActiveNet  data.
   * @param array $dw_asset_ids
   *   GUID's from ActiveNet.
   */
  private function datawarehouseLocationsFetch(array &$activenet_data, array $dw_asset_ids) {
    if (!empty($dw_asset_ids)) {
      $query_in_values = "'" . implode("', '", $dw_asset_ids) . "'";
      $query = "
        SELECT ACTIVITYNUMBER,
        CASE WHEN s.SITENAME = 'YMCA Camping & Outdoor Leadership' THEN
        CASE WHEN [CENTERNAME] = 'Downtown Seattle YMCA' THEN [CENTERNAME]
        ELSE LTRIM(REPLACE([CENTERNAME], 'YMCA', '')) END
        ELSE s.SITENAME END as Branch
        FROM [dbo].[ACTIVITIES] a left join dbo.SITES s on a.[SITE_ID] = s.[SITE_ID]
          left join [dbo].[FACILITIES] f on a.[FACILITY_ID] = f.[FACILITY_ID]
          left join [dbo].[CENTERS] c on f.[CENTER_ID] = c.[CENTER_ID]
        WHERE [ACTIVITYNUMBER] IN ($query_in_values)
      ";
      $dw_locations = $this->datawarehouseClient->call($query);

      foreach ($dw_locations as $location) {
        // Add location to dw_data.
        $activenet_data_key = array_search($location['ACTIVITYNUMBER'], $dw_asset_ids);
        $activenet_data[$activenet_data_key]['dwData']['location'] = $location['Branch'];
      }
    }
  }

  /**
   * Fetch Active Registration Dates data from DataWarehouse.
   *
   * We need to add separated function with query for
   * ACTIVITYREGISTRATIONWINDOWS table, similar example for locations:
   * https://github.com/propeoplemd/ymcaseattle/blob/master/docroot/modules/custom/activenet_sync/src/ActivenetSyncActivenetFetcher.php#L332
   * @note I assume this pointer should be datawarehouseLocationsFetch() but has moved.
   *
   * In query exclude any dates that are 1899-12-30
   * in $dw_asset_ids is array with ACTIVITYNUMBER
   * we need to add result of this query to every item in $activenet_data, put
   * it to ...['dwData']['sales_start_date'] and ...['dwData']['sales_end_date']
   * like here -
   * https://github.com/propeoplemd/ymcaseattle/blob/master/docroot/modules/custom/activenet_sync/src/ActivenetSyncActivenetFetcher.php#L351
   * Note that MEMBER_INTERNET_DATE and INTERNET_END_DATE can contain several
   * values, all this values must be in ...['dwData']['sales_start_date'] and
   * ...['dwData']['sales_end_date'] for every item.
   *
   * @param array $activenet_data
   *   ActiveNet  data.
   * @param array $dw_asset_ids
   *   GUID's from ActiveNet.
   */
  private function datawarehouseRegistrationDatesFetch(array &$activenet_data, array $dw_asset_ids) {
    if (!empty($dw_asset_ids)) {
      $activity_ids = [];
      $activity_ids_by_dw_asset_ids = [];
      foreach ($activenet_data as $value) {
        if (empty($value['dwData'])
          || empty($value['dwData']['ACTIVITYNUMBER'])
          || empty($value['dwData']['ACTIVITY_ID'])
          || !in_array($value['dwData']['ACTIVITYNUMBER'], $dw_asset_ids)) {
          continue;
        }

        $activity_ids[] = $value['dwData']['ACTIVITY_ID'];
        $activity_ids_by_dw_asset_ids[$value['dwData']['ACTIVITYNUMBER']][] = $value['dwData']['ACTIVITY_ID'];
      }
      $query_in_values = "'" . implode("', '", $activity_ids) . "'";
      $query = "
        SELECT ACTIVITY_ID, MEMBER_INTERNET_DATE, INTERNET_DATE, INTERNET_END_DATE
        FROM [dbo].[ACTIVITYREGISTRATIONWINDOWS]
        WHERE [ACTIVITY_ID] IN ($query_in_values)
        AND (
          [MEMBER_INTERNET_DATE] != 'Dec 30 1899 12:00:00:000AM'
          OR [INTERNET_DATE] != 'Dec 30 1899 12:00:00:000AM'
          OR [INTERNET_END_DATE] != 'Dec 30 1899 12:00:00:000AM'
        )
      ";
      $dw_registration = $this->datawarehouseClient->call($query);

      foreach ($dw_registration as &$registration) {
        // Placeholder for earliest start date.
        $registration['start_date'] = NULL;

        $reformat_or_unset = function ($key, &$registration) {
          if ($registration[$key] != 'Dec 30 1899 12:00:00:000AM' && $registration[$key] != '') {
            // Reformat time to datetime_iso8601.
            $registration[$key] = date('c', strtotime($registration[$key]));
          }
          else {
            // Drop if invalid value.
            unset($registration[$key]);
          }
        };

        // Reformat times to datetime_iso8601 or dropping invalid values.
        $reformat_or_unset('MEMBER_INTERNET_DATE', $registration);
        $reformat_or_unset('INTERNET_DATE', $registration);
        $reformat_or_unset('INTERNET_END_DATE', $registration);

        // Set earliest date to start_date.
        if (isset($registration['INTERNET_DATE']) || isset($registration['MEMBER_INTERNET_DATE'])) {
          if (!isset($registration['INTERNET_DATE']) && isset($registration['MEMBER_INTERNET_DATE'])) {
            $registration['start_date'] = $registration['MEMBER_INTERNET_DATE'];
          }
          elseif (isset($registration['INTERNET_DATE']) && !isset($registration['MEMBER_INTERNET_DATE'])) {
            $registration['start_date'] = $registration['INTERNET_DATE'];
          }
          else {
            $registration['start_date'] = $registration['INTERNET_DATE'] < $registration['MEMBER_INTERNET_DATE'] ? $registration['INTERNET_DATE'] : $registration['MEMBER_INTERNET_DATE'];
          }
          unset($registration['INTERNET_DATE']);
          unset($registration['MEMBER_INTERNET_DATE']);
        }
      }

      foreach ($dw_asset_ids as $dw_asset_id_key => $dw_asset_id) {
        $sales_start_date = NULL;
        $sales_end_date = NULL;
        foreach ($dw_registration as &$registration) {

          if (!in_array($registration['ACTIVITY_ID'], $activity_ids_by_dw_asset_ids[$dw_asset_id]) || (!isset($registration['start_date']) && !isset($registration['INTERNET_END_DATE']))) {
            continue;
          }

          // If start_date is set and later keep as start_date.
          if (isset($registration['start_date']) && ($sales_start_date == NULL || $registration['start_date'] < $sales_start_date)) {
            $sales_start_date = $registration['start_date'];
          }

          // If INTERNET_END_DATE is set and later keep as end_date.
          if (isset($registration['INTERNET_END_DATE']) && ($sales_end_date == NULL || $registration['INTERNET_END_DATE'] > $sales_end_date)) {
            $sales_end_date = $registration['INTERNET_END_DATE'];
          }
        }

        // Add sales dates to dw_data.
        if (isset($sales_start_date)) {
          $activenet_data[$dw_asset_id_key]['dwData']['sales_start_date'] = $sales_start_date;
        }
        if (isset($sales_end_date)) {
          $activenet_data[$dw_asset_id_key]['dwData']['sales_end_date'] = $sales_end_date;
        }
      }
    }
  }

  /**
   * Delete Parent Assets.
   *
   * @param array $data
   *   ActiveNet  data.
   */
  private function deleteParentAssets(array &$data) {
    $parent_ids = $this->fetchParentIds();
    foreach ($data as $key => $value) {
      if (isset($value['dwData']['ACTIVITY_ID']) && in_array($value['dwData']['ACTIVITY_ID'], $parent_ids)) {
        unset($data[$key]);
      }
    }
  }

}
