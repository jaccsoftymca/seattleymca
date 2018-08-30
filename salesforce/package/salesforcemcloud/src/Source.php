<?php
namespace Ymca\Salesforcemcloud;

/**
 * Data source.
 *
 * @package Ymca\Salesforcemcloud
 */
class Source {
  /**
   * Connection to the datawarehouse.
   *
   * @var resource
   */
  private $connection;

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
   */
  public function __construct($server, $user, $pass, $db)  {
    try {
      $this->connection = mssql_connect($server, $user, $pass);
      mssql_select_db($db, $this->connection);
    }
    catch (\Exception $e) {
      throw new Exception('Failed to connect to source.');
    }
  }

  /**
   * Runt query.
   *
   * @param string $query
   */
  public function query($query) {
    $this->results = mssql_query($query, $this->connection);
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
    mssql_free_result($this->results);
  }
}
