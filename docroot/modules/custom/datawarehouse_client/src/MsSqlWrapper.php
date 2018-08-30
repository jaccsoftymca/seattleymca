<?php

namespace Drupal\datawarehouse_client;

/**
 * Wrapper for MsSql queries.
 *
 * Note: mssql_connect deleted in php7.
 */
class MsSqlWrapper {
  /**
   * Connection to the datawarehouse.
   *
   * @var resource
   */
  private $connection;

  /**
   * MsSql query results.
   *
   * @var array
   */
  private $results;

  /**
   * Source constructor.
   *
   * @param string $server
   *   Database server.
   * @param string $user
   *   Database user.
   * @param string $pass
   *   Database password.
   * @param string $db
   *   Database name.
   *
   * @throws \Exception
   */
  public function __construct($server, $user, $pass, $db) {
    try {
      // @codingStandardsIgnoreStart
      $this->connection = mssql_connect($server, $user, $pass);
      mssql_select_db($db, $this->connection);
      // @codingStandardsIgnoreEnd
    }
    catch (\Exception $e) {
      throw new \Exception('Failed to connect to source.');
    }
  }

  /**
   * Runt query.
   *
   * @param string $query
   *   Ms SQL query.
   */
  public function query($query) {
    // @codingStandardsIgnoreStart
    $this->results = mssql_query($query, $this->connection);
    // @codingStandardsIgnoreEnd
  }

  /**
   * Extract results.
   *
   * @return array
   *   Results.
   */
  public function extract() {
    $data = [];
    while ($row = mssql_fetch_assoc($this->results)) {
      $data[] = $row;
    }

    return $data;
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    // @codingStandardsIgnoreStart
    if ($this->results) {
      mssql_free_result($this->results);
    }
    // @codingStandardsIgnoreEnd
  }

}
