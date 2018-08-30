<?php
/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote 
 */

$metadata['https://fs.seattleymca.org/adfs/services/trust'] = array (
  'entityid' => 'https://fs.seattleymca.org/adfs/services/trust',
  'contacts' =>
    array (
      0 =>
        array (
          'contactType' => 'support',
        ),
    ),
  'metadata-set' => 'saml20-idp-remote',
  'SingleSignOnService' =>
    array (
      0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://fs.seattleymca.org/adfs/ls/',
        ),
      1 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://fs.seattleymca.org/adfs/ls/',
        ),
    ),
  'SingleLogoutService' =>
    array (
      0 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
          'Location' => 'https://fs.seattleymca.org/adfs/ls/',
        ),
      1 =>
        array (
          'Binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
          'Location' => 'https://fs.seattleymca.org/adfs/ls/',
        ),
    ),
  'ArtifactResolutionService' =>
    array (
    ),
  'NameIDFormats' =>
    array (
      0 => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
      1 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
      2 => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
    ),
  'keys' =>
    array (
      0 =>
        array (
          'encryption' => true,
          'signing' => false,
          'type' => 'X509Certificate',
          'X509Certificate' => 'MIIC5jCCAc6gAwIBAgIQQrGXZWjo3KZH55VvRHHBjDANBgkqhkiG9w0BAQsFADAvMS0wKwYDVQQDEyRBREZTIEVuY3J5cHRpb24gLSBmcy5zZWF0dGxleW1jYS5vcmcwHhcNMTcwNDI2MTUyMDUxWhcNMTgwNDI2MTUyMDUxWjAvMS0wKwYDVQQDEyRBREZTIEVuY3J5cHRpb24gLSBmcy5zZWF0dGxleW1jYS5vcmcwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDm2yvONUD31M0r5ufRdxS3gZR27Jr4KUXp11hRtSfShnb5V6nowxdjAAUXINo9SH7cwVjeeneT3a42qebtIN0Z2s4ScsSw9yJeElzqEGIknmd6rM+I1CQVqeMilMv8i2Pkb9XxJXjj7a8Gt/Njyr3l8FswlSk+mfXFJPxPh6SEbuj7PaT7NSSEkLX15U+g9wf9lPZhGh/RQNF0i9H46pca2jvhZJ++Wg3pOLF9THljpQYDW2i7EowE5USkl1HUqeiVpMr7yEGPuU0afmCHtxzM7xoHl9l04TQFjDE83ULjP1XcXgXjp+kSaCvuGpAK9gBRjseXejUq2dQiQ634baC/AgMBAAEwDQYJKoZIhvcNAQELBQADggEBACA6UZ6CZyZnCFgTiQNqBZ4Ol1LwJpgV9IQsINDu3Y0FiQUI9czj2nGewANzzUIVuK2NvFGpax7PKWgkaSK4pFhsfUgOvLZBekD684p/X81A7hszAD06n5P/qJC0N8yPdg0eyXeXFVLDrX9RR0ckT5lumLUS97vE96PT0s9zPPiWnbM6NDlm97Q/5L1cAvJHaddLl5caDAcEvHH2c61ezw4DBnaPGqG+/IjB+3vpZtmQVLDQ8PG8MwFAPjeMF0hbcFYxLfKf6ZgzJn3dBL++B5qYOKy6X2Kxmqn7OrtuMv2yrTu3pxsf3qMtEw0iC7yY+B2hxqm2WEm659AuSdVX9CU=',
        ),
      1 =>
        array (
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => 'MIIC4DCCAcigAwIBAgIQPYXO+gfwhLVDq90QcXATQTANBgkqhkiG9w0BAQsFADAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmcy5zZWF0dGxleW1jYS5vcmcwHhcNMTYwNTA4MTgyMDI2WhcNMTcwNTA4MTgyMDI2WjAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmcy5zZWF0dGxleW1jYS5vcmcwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDEe24W85JiMlbdtEA1wFvNL+kIc95gTjSBOBKfNMxh/YQ4eiPt7UAYF5fuggBKo56wP/m2/hdZCkxtHixt+f5DrR0Mngv+rqNfKCUMCINmvCXP70WP3jxL7nOK636rafGYLFKpcRLUGxrtAnCZSAITaQYaD7b67NXdkYmSALzBkrZdSmHzezFgHcPCs4UhJ1+Eud3P9hQN/p/8w4E7SyseilsvFvdVMpHN3SKmhIAmtuGPMYNx+5BhnehFstOZ4R+Ju+zdJPL/8j1O+T0DFcrxjKdDYOvKXCcZUij1iuUt39u3aZ6p45ACJ5lFxDj365Byc0upFXa7XZQH8x6IrF4XAgMBAAEwDQYJKoZIhvcNAQELBQADggEBAEjMaWPwuF9hSFOyoZFjbhyH2fQKdNx/XSxF/9InDkxPhfIxMiiIT8bGWnvFDNi9iISigWBhhDw1y7IQImEyaqskEtwx7tk/GXU5hkJRRvUmHiUkLyuRPfp0mUE4r3T3cNkQKARW2pTaPvhXPgxFg3ap+1UpSOs+tjH0xS5kCyr0VakvMVKxvbppzxUSdGLY4ULoUkBuBFBEOUfn+++4LCPU+3dENSWd+IQmBiVomVloKrtje+bvD3x3ZgFRoHCao7Sh0Kg/YFIw6dIVV/7UShSBy/zHAjczEmzMXaAPhnTktHj1QIdXdgAPJMD4VBy6jMZJCGTS88x4eQda0coBlww=',
        ),
      2 =>
        array (
          'encryption' => false,
          'signing' => true,
          'type' => 'X509Certificate',
          'X509Certificate' => 'MIIC4DCCAcigAwIBAgIQLhqgOnYW6ZRGBvgufL2UZDANBgkqhkiG9w0BAQsFADAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmcy5zZWF0dGxleW1jYS5vcmcwHhcNMTcwNDI2MTUyMDQyWhcNMTgwNDI2MTUyMDQyWjAsMSowKAYDVQQDEyFBREZTIFNpZ25pbmcgLSBmcy5zZWF0dGxleW1jYS5vcmcwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDOKwusrP+DAj9NaSwRD/a0BPiY5XZTMc/crwAfJ0eDd2a/FGhvdBmjE+nQsSsBTj7/aXY0x+osVsHpy3K15QfhXGL6vSotitzSDqS5nsPXEhSEEPPfAFYE/bM/xmXckow8CM0nt8lIU2gyb4UTKSyCmhvvye07aVf7ZlOwbUPHBjim8ZfPnh/sgsU5X+WyDcdmBcX2MuKdyb3GnCBWnUw2tv9kpUsKhV8PD1WnKkhoLxC6aLhq+QC8HqhgVHDGv9vM7QU4vVS3lnl2AuytFmMXolnLE6jwj+/o78i4E1Ojk8wbBp7uOv4c8gNedMKsD6EGP9LSs1DWvEkUwhMuCrA5AgMBAAEwDQYJKoZIhvcNAQELBQADggEBAIjXZ4BpEfKphoAV610xAnL4pFc4NaWZ0mfQ2rC4qV0pXJYxoa6EHIiXYqLe0o8TMOn9mTdqSrmygAFKAe/BWmPaQqglPVBGxbkKf9WsHXMi4p9ZgHNRz8CA4KBc51q7HZjH1Oxd/BNrrppDEFWxkS4H/7N8jIS09Skx3phY0o/mdiyPxoMWHktiWQjdgjesH/wRp5mJiF6FcakvDEWpk7h1Ti4jvAl+U0uR1W0VtRz6WS0I4Z8vA4VgRw89mN0yV8EiYuL2Dqq2lWlNMAyaWRTCQldQXH0vvKWYDCBJJ2jvFpK/dzhLuHK9SYintNgrJ4q51yKCAcHZE3XgcZ8ndAw=',
        ),
    ),
);
