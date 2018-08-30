<?php
include 'vendor/autoload.php';

// SSH Tunnel: ssh -L 60412:YGSDWDev4FFW.cloudapp.net:60412 svnuser@wearepropeople.md
// Prod server: $server = "YGSDWDev4FFW.cloudapp.net:60412";
$creds = [
  //'dw_server' => 'YGSDWDev4FFW.cloudapp.net:60412',
  'dw_server' => '127.0.0.1:60412',
  'dw_user' => 'alexander.schedrov',
  'dw_pass' => 'c@v37V@ghHwR',
  'dw_db' => 'Hoshi',
  'sf_clientid' => '0ok0fv5zan09yxz2x0230qdk',
  'sf_clientsecret' => 'zomxYGW3FE9K94tUHq8imGhL',
];

$syncer = new \Ymca\Salesforcemcloud\Syncer(
  $creds['dw_server'],
  $creds['dw_user'],
  $creds['dw_pass'],
  $creds['dw_db'],
  $creds['sf_clientid'],
  $creds['sf_clientsecret']);

$syncer->sync();

/**
 * @TODOs:
 * - Mapping
 * - Processor
 * - Logging
 * - CLI output
 * - Pager/batch
 * - Push updates
 */