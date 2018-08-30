<?php
namespace Ymca\Salesforcemcloud;

/**
 * Data pusher.
 *
 * @package Ymca\Salesforcemcloud
 */
class Pusher {
  /**
   * Pusher constructor.
   */
  public function __construct()  {
    try {
      // TODO: add implementation.
    }
    catch (\Exception $e) {
      throw new \Exception('Failed to run pusher.');
    }
  }

  public function push($connection, $results, $debug = FALSE) {
    foreach ($results as $subscriber) {
      // @TODO: send debug into processor.
      $this->pushSubscriber($connection, $subscriber, $debug);
    }

    $retSub = new \ET_Subscriber();
    $retSub->authStub = $connection->getClient();
    $retSub->filter = array('Property' => 'SubscriberKey','SimpleOperator' => 'equals','Value' => 'test_berhan232323@gmail.com');
    $retSub->props = array("SubscriberKey", "EmailAddress", "Status", "10107");
    $getResult = $retSub->get();
    print_r('Get Status: '.($getResult->status ? 'true' : 'false')."\n");
    print 'Code: '.$getResult->code."\n";
    print 'Message: '.$getResult->message."\n";
    print_r('More Results: '.($getResult->moreResults ? 'true' : 'false')."\n");
    print 'Results Length: '. count($getResult->results)."\n";
    print 'Results: '."\n";
    print_r($getResult->results);
    print "\n---------------\n";
  }

  public function pushSubscriber($connection, $subscriber, $debug = FALSE) {
    // @TODO: Create mapping.
    $email = $subscriber['Email'];
    if ($debug) {
      $email = 'test_' . $email;
    }
    $subCreate = new \ET_Subscriber();
    $subCreate->authStub = $connection->getClient();
    $subCreate->props = array(
      'SubscriberKey' => $email,
      'EmailAddress' => $email,
      'FirstName' => $subscriber['FirstName'],
      'LastName' => $subscriber['LastName'],
      'Cell Phone' => $subscriber['CellPhone'],
      'Gender' => $subscriber['Gender'],
    );
    $response = $subCreate->post();
    $this->addSubscriberToList($connection, $email, $debug);

    /*if (!$response->status) {
      throw new \Exception('Push of client has been failed.');
    }*/
  }

  public function addSubscriberToList($connection, $subscriber_key, $debug) {
    $response = $connection
      ->getClient()
      ->AddSubscriberToList($subscriber_key, array(132), $subscriber_key);
  }
}
