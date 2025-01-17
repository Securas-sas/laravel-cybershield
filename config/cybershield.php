<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API key
    |--------------------------------------------------------------------------
    |
    | This API key is used by CyberShield service for API identity
    | you can find it in your CyberShield account
    |
    */

    'api_key' => 'paste here your API key',

    /*
    |--------------------------------------------------------------------------
    | API Email Address
    |--------------------------------------------------------------------------
    |
    | This Email Address is used by CyberShield service for API identity
    | you can find it in your CyberShield account
    |
    */

    'email_address' => 'paste here an api email address',

    /*
    |--------------------------------------------------------------------------
    | Sandbox Mode
    |--------------------------------------------------------------------------
    | If true, the API used is that of the test
    |
    */

    'sandbox' => false,

    /*
    |--------------------------------------------------------------------------
    | Enable CyberShield
    |--------------------------------------------------------------------------
    |
    | Disable or enabled the CyberShield middleware
    |
    */
    'enabled' => true,

    /*
   |--------------------------------------------------------------------------
   | Skips by CyberShield middleware
   |--------------------------------------------------------------------------
   |
   | the list of patterns not to be checked, separated by commas
   |
   */
    'patterns' => ['admin/*','backoffice/*'],

    /*
   |--------------------------------------------------------------------------
   | CyberShield Key Validation
   |--------------------------------------------------------------------------
   |
   | Do not change this parameter, the change is done automatically by the scheduled cron task
   |
   */

    'key_verification' => "",

];
