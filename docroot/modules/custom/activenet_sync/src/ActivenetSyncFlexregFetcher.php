<?php

namespace Drupal\activenet_sync;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\ProxyClass\Lock\DatabaseLockBackend;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\datawarehouse_client\DatawarehouseClient;

/**
 * ActivenetSyncFlexregFetcher class.
 */
class ActivenetSyncFlexregFetcher implements ActivenetSyncFetcherInterface {

  const DW_ITEMS_PER_PAGE = 100;

  /**
   * ActivenetSyncFlexregWrapper definition.
   *
   * @var ActivenetSyncFlexregWrapper
   */
  protected $wrapper;

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $config;

  /**
   * The logger channel.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * The Datawarehouse response array.
   *
   * @var DatawarehouseClient
   */
  protected $datawarehouseClient;

  /**
   * The Datawarehouse query offset.
   *
   * @var int
   */
  protected $offset;

  /**
   * Site Mode.
   *
   * @var bool
   */
  protected $isProduction;

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
   * ActivenetSyncFlexregFetcher constructor.
   *
   * @param ActivenetSyncFlexregWrapper $wrapper
   *   Wrapper.
   * @param ConfigFactory $config
   *   Config factory.
   * @param LoggerChannelInterface $logger
   *   Drupal Logger.
   * @param DatawarehouseClient $dw_client
   *   Datawarehouse Client.
   * @param DatabaseLockBackend $lock
   *   Lock.
   * @param ActivenetSyncRepository $repository
   *   Class and Session Entity Repository.
   */
  public function __construct(
    ActivenetSyncFlexregWrapper $wrapper,
    ConfigFactory $config,
    LoggerChannelInterface $logger,
    DatawarehouseClient $dw_client,
    DatabaseLockBackend $lock,
    ActivenetSyncRepository $repository) {

    $this->wrapper = $wrapper;
    $this->config = $config;
    $this->logger = $logger;
    $this->datawarehouseClient = $dw_client;
    $this->lock = $lock;
    $this->repository = $repository;
    $settings = $this->config->get('activenet_sync.settings');
    $this->isProduction = (bool) $settings->get('is_production');
    $this->offset = ($settings->get('dw_offset')) ? (int) $settings->get('dw_offset') : 1;
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
      // Use existing data (for testing).
      $results[] = json_decode($custom_data['data'], TRUE);
      $this->wrapper->setSourceData($results);
      return;
    }
    if (!$this->isProduction && $this->offset > self::DW_ITEMS_PER_PAGE) {
      $this->wrapper->setSourceData([]);
    }
    else {
      $offset = $this->offset;
      $limit = $offset + self::DW_ITEMS_PER_PAGE - 1;
      if (!empty($custom_data) && $custom_data['type'] == 'id') {
        // Load by id.
        $result = $this->getDwBaseData(1, 2, $custom_data['data']);
      }
      else {
        $result = $this->getDwBaseData($offset, $limit);
      }

      if ($this->isProduction) {
        // Save offset for next iteration.
        $this->config->getEditable('activenet_sync.settings')->set('dw_offset', $limit++)->save();
      }
      if (count($result) < self::DW_ITEMS_PER_PAGE) {
        // Reset offset if items less than DW_ITEMS_PER_PAGE.
        $this->config->getEditable('activenet_sync.settings')->set('dw_offset', 1)->save();
      }

      $program_ids = [];
      $ids_for_touch = [];
      foreach ($result as $key => $item) {
        $program_ids[$key] = $item['program_id'];
        // Add assetGuid to list of touched assets.
        $ids_for_touch[] = $item['ps_id'];
      }
      $this->repository->touchSyncCaches($ids_for_touch, 'flexreg');
      // Get amounts for for specified program id's.
      $amounts = $this->getProgramFeeAmounts(array_unique($program_ids));
      $this->addAmountsToBaseData($result, $amounts);
      // Fix encoding and html special chars.
      array_walk_recursive($result, function (&$item, $key) {
        $item = htmlspecialchars_decode(utf8_encode($item));
      });
      $this->wrapper->setSourceData($result);
    }
  }

  /**
   * Get base datawarehouse data.
   *
   * @param int $offset
   *   MsSql Query offset.
   * @param int $limit
   *   Last row number.
   * @param int $id
   *   DCPROGRAMSESSION_ID.
   *
   * @return array
   *   DW data
   */
  public function getDwBaseData($offset, $limit, $id = NULL) {
    if ($id) {
      // Load by ID.
      $condition = "ps.DCPROGRAMSESSION_ID=$id";
    }
    else {
      $condition = "programs.HIDEONINTERNET=0 AND programs.STATUS=0 AND departments.DEPARTMENT_NAME!='YMCA Staff Training'";
    }
    $query = "
      WITH sessions AS (
        SELECT
          programs.DCPROGRAM_ID program_id,
          sessions.DCSESSION_ID session_id,
          ps.DCPROGRAMSESSION_ID ps_id,
          programs.DCPROGRAMNAME program_name,
          programs.DESCRIPTION program_description,
          programs.GENDER program_gender,
          programs.AGESMAX agesmax,
          programs.AGESMIN agesmin,
          programs.KEYBOARD_ENTRY_DATE standard_reg,
          programs.KEYBOARD_ENTRY_END_DATE standard_reg_end,
          programs.INTERNET_DATE online_reg,
          programs.INTERNET_END_DATE online_reg_end,
          sessions.DCSESSIONNAME session_name,
          sessions.DESCRIPTION session_description,
          sessions.BEGINNINGDATE start_date,
          sessions.ENDINGDATE end_date,
          sessions.BEGINNINGTIME start_time,
          sessions.ENDINGTIME end_time,
          sessions.WEEKDAYS weekdays,
          category.CATEGORYNAME category_name,
          sub_category.SUBCATEGORYNAME sub_category_name,
          departments.DEPARTMENT_NAME department_name,
          sites.SITENAME location,
          centers.CENTERNAME physical_location,
          row_number() OVER(ORDER BY ps.DCPROGRAMSESSION_ID ASC) AS row_num
        FROM DCSESSIONS sessions
          LEFT JOIN DCPROGRAMSESSIONS ps
            ON sessions.DCSESSION_ID = ps.DCSESSION_ID
          LEFT JOIN DCPROGRAMS programs
            ON programs.DCPROGRAM_ID = ps.DCPROGRAM_ID
          LEFT JOIN RG_CATEGORY category
            ON category.RG_CATEGORY_ID = programs.RG_CATEGORY_ID
          LEFT JOIN RG_SUB_CATEGORY sub_category
            ON sub_category.RG_SUB_CATEGORY_ID = programs.RG_SUB_CATEGORY_ID
          LEFT JOIN ACTIVITY_DEPARTMENTS departments
            ON departments.ACTIVITY_DEPARTMENT_ID = programs.DC_DEPARTMENT_ID
          LEFT JOIN SITES sites
            ON sites.SITE_ID = programs.SITE_ID
          LEFT JOIN FACILITIES facilities
            ON facilities.FACILITY_ID = sessions.FACILITY_ID
          LEFT JOIN CENTERS centers
            ON centers.CENTER_ID = facilities.CENTER_ID
        WHERE $condition
      )
      SELECT * FROM sessions WHERE row_num BETWEEN $offset AND $limit;
    ";
    return $this->datawarehouseClient->call($query);
  }

  /**
   * Get Program Fee Amounts.
   *
   * @param array $program_ids
   *   List of program id's.
   *
   * @return array
   *   DW Fee Amounts
   */
  public function getProgramFeeAmounts(array $program_ids) {
    if (!$program_ids) {
      return [];
    }
    $query_in_values = "'" . implode("', '", $program_ids) . "'";
    // Get costs.
    $query = "
      SELECT pf.DCPROGRAM_ID, pf.FEEAMOUNT, pf.CHARGE_NAME
      FROM DCPROGRAMFEES pf
      WHERE pf.DCPROGRAM_ID IN ($query_in_values) AND pf.CHARGE_TYPE=0 AND pf.ONE_TIME_FEE!=-1;
    ";
    $result['costs'] = $this->datawarehouseClient->call($query);
    // Registration fee.
    $query = "
      SELECT pf.DCPROGRAM_ID, pf.FEEAMOUNT, pf.CHARGE_NAME
      FROM DCPROGRAMFEES pf
      WHERE pf.DCPROGRAM_ID IN ($query_in_values) AND pf.CHARGE_TYPE=0 AND pf.ONE_TIME_FEE=-1;
    ";
    $result['registration_fee'] = $this->datawarehouseClient->call($query);
    return $result;
  }

  /**
   * Insert Program Fee Amounts in result data.
   *
   * @param array $data
   *   Base data.
   * @param array $amounts
   *   DW Fee Amounts.
   */
  public function addAmountsToBaseData(array &$data, array $amounts) {
    foreach ($data as $key => $value) {
      $program_id = $value['program_id'];
      // Get amounts for the specified program_id.
      $costs_filtered = array_filter($amounts['costs'], function ($element) use ($program_id) {
        return isset($element['DCPROGRAM_ID']) && $element['DCPROGRAM_ID'] == $program_id;
      });
      $registration_fees_filtered = array_filter($amounts['registration_fee'], function ($element) use ($program_id) {
        return isset($element['DCPROGRAM_ID']) && $element['DCPROGRAM_ID'] == $program_id;
      });
      $data[$key]['costs'] = $costs_filtered;
      $data[$key]['fee_amounts'] = $registration_fees_filtered;
    }
  }

}
