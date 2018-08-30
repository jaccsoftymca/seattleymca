<?php

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site ymcaseattle, environment dev.
$aliases['dev'] = array(
  'root' => '/var/www/html/ymcaseattle.dev/docroot',
  'ac-site' => 'ymcaseattle',
  'ac-env' => 'dev',
  'ac-realm' => 'prod',
  'uri' => 'ymcaseattledev.prod.acquia-sites.com',
  'remote-host' => 'staging-18535.prod.hosting.acquia.com',
  'remote-user' => 'ymcaseattle.dev',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['dev.livedev'] = array(
  'parent' => '@ymcaseattle.dev',
  'root' => '/mnt/gfs/ymcaseattle.dev/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site ymcaseattle, environment prod.
$aliases['prod'] = array(
  'root' => '/var/www/html/ymcaseattle.prod/docroot',
  'ac-site' => 'ymcaseattle',
  'ac-env' => 'prod',
  'ac-realm' => 'prod',
  'uri' => 'ymcaseattle.prod.acquia-sites.com',
  'remote-host' => 'ymcaseattle.ssh.prod.acquia-sites.com',
  'remote-user' => 'ymcaseattle.prod',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['prod.livedev'] = array(
  'parent' => '@ymcaseattle.prod',
  'root' => '/mnt/gfs/ymcaseattle.prod/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site ymcaseattle, environment ra.
$aliases['ra'] = array(
  'root' => '/var/www/html/ymcaseattle.ra/docroot',
  'ac-site' => 'ymcaseattle',
  'ac-env' => 'ra',
  'ac-realm' => 'prod',
  'uri' => 'ymcaseattlera.prod.acquia-sites.com',
  'remote-host' => 'ymcaseattlera.ssh.prod.acquia-sites.com',
  'remote-user' => 'ymcaseattle.ra',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['ra.livedev'] = array(
  'parent' => '@ymcaseattle.ra',
  'root' => '/mnt/gfs/ymcaseattle.ra/livedev/docroot',
);

if (!isset($drush_major_version)) {
  $drush_version_components = explode('.', DRUSH_VERSION);
  $drush_major_version = $drush_version_components[0];
}
// Site ymcaseattle, environment test.
$aliases['test'] = array(
  'root' => '/var/www/html/ymcaseattle.test/docroot',
  'ac-site' => 'ymcaseattle',
  'ac-env' => 'test',
  'ac-realm' => 'prod',
  'uri' => 'ymcaseattlestg.prod.acquia-sites.com',
  'remote-host' => 'staging-18535.prod.hosting.acquia.com',
  'remote-user' => 'ymcaseattle.test',
  'path-aliases' => array(
    '%drush-script' => 'drush' . $drush_major_version,
  ),
);
$aliases['test.livedev'] = array(
  'parent' => '@ymcaseattle.test',
  'root' => '/mnt/gfs/ymcaseattle.test/livedev/docroot',
);
