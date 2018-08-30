<?php

/**
 * @file
 */

$env = !empty($_ENV['AH_SITE_ENVIRONMENT']) ? '-' . $_ENV['AH_SITE_ENVIRONMENT'] : '';

$config = array(
  'admin' => array(
    'core:AdminPassword',
  ),
  'default-sp' => array(
    'saml:SP',

    // You can get this from ADFS Federation file
    // Contact your ADFS administrator
    // to obtain this information.
    'entityID'             => 'urn:drupal:adfs-saml' . $env,
    'idp'                  => 'https://fs.seattleymca.org/adfs/services/trust',
    'NameIDPolicy'         => NULL,
    'redirect.sign'        => TRUE,
    'assertion.encryption' => TRUE,
    'sign.logout'          => TRUE,

    // Generate using openssl, @see example above.
    // These are the certs from `/cert` directory.
    'privatekey'           => 'saml.pem',
    'certificate'          => 'saml.crt',
    // Defaults to SHA1 (http://www.w3.org/2000/09/xmldsig#rsa-sha1)
    'signature.algorithm'  => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
  ),
);
