<?php
namespace Ymca\Salesforcemcloud;

/**
 * Salesforce MCloud Service Manager.
 *
 * @package Ymca\Salesforcemcloud
 */
class Syncer {
  /**
   * Source of the data.
   *
   * @var \Ymca\Salesforcemcloud\Source
   */
  private $source;

  /**
   * Destination for the data.
   *
   * @var \Ymca\Salesforcemcloud\Destination
   */
  private $destination;

  /**
   * Fetcher.
   *
   * @var \Ymca\Salesforcemcloud\Fetcher
   */
  private $fetcher;

  /**
   * Pusher.
   *
   * @var \Ymca\Salesforcemcloud\Pusher
   */
  private $pusher;

  /**
   * Flag which indicates whe
   *
   * @var bool
   */
  private $debug = TRUE;

  /**
   * Syncer constructor.
   *
   * @param string $source_server
   *   Database server.
   * @param string $source_user
   *   Database user.
   * @param string $source_pass
   *   Database password.
   * @param string $source_db
   *   Database name.
   * @param string $clientid
   *   Salesforce MCloud client ID.
   * @param string $clientsecret
   *   Salesforce MCloud client Secret.
   */
  public function __construct($source_server, $source_user, $source_pass, $source_db, $clientid, $clientsecret)  {
    $this->source = new Source($source_server, $source_user, $source_pass, $source_db);
    $this->destination = new Destination($clientid, $clientsecret);
    $this->fetcher = new Fetcher();
    $this->pusher = new Pusher();
  }

  public function sync() {
    $results = $this->fetcher->fetch($this->source, $this->debug);
    $this->pusher->push($this->destination, $results, $this->debug);
    if ($this->debug) {
      //$output = $this->getOutput($results);
      //print $output;
    }
  }

  public function getOutput($results) {
    $output = '<table>';
    $output .=  '<tr>';
    $output .=  '<th>Subscriber Key</th>';
    $output .=  '<th>Customer FirstName</th>';
    $output .=  '<th>Customer LastName</th>';
    // TODO: questions about relation.
    // TODO: count of members/non-members.
    // TODO: latest membership?
    $output .=  '<th>Membership ID</th>';
    // TODO: some people doesn't have emails.
    $output .=  '<th>Email Address</th>';
    $output .=  '<th>Home Phone</th>';
    // TODO: One field or different fields?
    $output .=  '<th>Full address</th>';
    $output .=  '<th>Birthdate</th>';
    $output .=  '<th>Gender</th>';
    $output .=  '<th>Deceased</th>';
    $output .=  '<th>Opt Out of Email</th>';
    $output .=  '<th>Account Head of Household?</th>';
    $output .=  '<th>Primary on package? (Y/N)</th>';
    // TODO: questions about relation.
    $output .=  '<th>Membership Package Name</th>';
    // TODO: questions about relation.
    // TODO: One field or different fields?
    $output .=  '<th>Alt Keys (1, 2, 3, 4, 5)</th>';
    // TODO: discuss business login for that.
    // TODO: FFW: Add business logic to select last.
    $output .=  '<th>Membership package - Date of Purchase (most recent)</th>';
    $output .=  '</tr>';
    foreach ($results as $row) {
      $output .=  '<tr>';
      $output .=  '<td>' . $row['PersonID'] . '</th>';
      $output .=  '<td>' . $row['FirstName'] . '</th>';
      $output .=  '<td>' . $row['LastName'] . '</th>';
      $output .=  '<td>' . $row['NaturalMembershipID'] . '</th>';
      $output .=  '<td>' . $row['Email'] . '</th>';
      $output .=  '<td>' . $row['HomePhone'] . '</th>';
      $output .=  '<td>' . implode(', ', [$row['Address1'], $row['Address2'], $row['City'], $row['State'], $row['ZipCode']]) . '</th>';
      $output .=  '<td>' . $row['BirthDate'] . '</th>';
      $output .=  '<td>' . $row['Gender'] . '</th>';
      $output .=  '<td>' . $row['Deceased_flag'] . '</th>';
      $output .=  '<td>' . $row['Opt_Out_Email_flag'] . '</th>';
      $output .=  '<td>' . $row['HeadOfHousehold'] . '</th>';
      $output .=  '<td>' . (!empty($row['PrimaryMember_personID']) ? 'Yes' : 'No') . '</th>';
      $output .=  '<td>' . $row['PackageName'] . '</th>';
      $output .=  '<td>' . implode(', ', [$row['AlternateKey1'], $row['AlternateKey2'], $row['AlternateKey3'], $row['AlternateKey4'], $row['AlternateKey5']]) . '</th>';
      $output .=  '<td>' . $row['SoldDate'] . '</th>';
      $output .=  '</tr>';
    }
    $output .= '</table>';

    return $output;
  }
}
