<?php

defined('BASEPATH') or exit('No direct script access allowed');

$route['api/v1/(:any)/permissions/(:num)'] = '$1/permissions/$2';
$route['api/v1/(:any)/permissions']        = '$1/permissions';

$route['api/v1/delete/(:any)/(:num)'] = '$1/data/$2';
$route['api/v1/(:any)/search/(:any)'] = '$1/data_search/$2';
$route['api/v1/(:any)/search']        = '$1/data_search';
$route['api/v1/login/auth']           = 'login/login_api';
$route['api/v1/login/view']           = 'login/view';
$route['api/v1/login/key']            = 'login/api_key';
$route['api/v1/custom_fields/(:any)/(:num)'] = 'custom_fields/data/$1/$2';
$route['api/v1/custom_fields/(:any)'] = 'custom_fields/data/$1';
$route['api/v1/common/(:any)/(:num)'] = 'common/data/$1/$2';
$route['api/v1/common/(:any)'] = 'common/data/$1';
$route['api/v1/(:any)/(:num)']        = '$1/data/$2';
$route['api/v1/(:any)']               = '$1/data';
$route['api/v1/(:any)/auth']               = '$1/signin';
$route['api/v1/(:any)/(:any)/(:num)'] = '$1/data/$2/$3';
$route['api/v1/(:any)/(:num)/(:num)'] = '$1/data/$2/$3';
