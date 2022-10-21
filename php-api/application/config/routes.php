<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = '';

$route['index'] = '';
$route['staticData/(:any)'] = 'api/staticData/appConfig/$1';

$route['user/login/(:any)'] = 'api/user/login/$1';
$route['user/signup'] = 'api/user/signup';
$route['user/getProfile/(:num)'] = 'api/user/getProfile/$1';
$route['user/editProfile/(:num)'] = 'api/user/editProfile/$1';
$route['user/forgetPassword'] = 'api/user/forgetPassword';
$route['user/resendOtp'] = 'api/user/resendOtp';
$route['user/validateOtp'] = 'api/user/validateOtp';
$route['user/changePassword'] = 'api/user/changePassword';
$route['user/deleteUser/(:num)'] = 'api/user/deleteUser/$1';
$route['user/updateToken/(:num)'] = 'api/user/updateToken/$1';

//Category
$route['category/addCategory'] = 'api/category/addCategory';
$route['category/getAllCategory'] = 'api/category/getCategory';
$route['category/updateCategory/(:num)'] = 'api/category/updateCatergory/$1';
$route['category/getCategory/(:num)'] = 'api/category/getSingleCategory/$1';


//Sub Category
$route['category/addSubcategory'] = 'api/category/addSubcategory';
$route['category/getAllSubCategory/(:num)'] = 'api/category/getSubCategory/$1';
$route['category/updateSubcategory/(:num)'] = 'api/category/updateSubcategory/$1';
    $route['category/getSubcategory/(:num)'] = 'api/category/getSingleCategory/$1';
// Doner
$route['request/donate'] = 'api/request/doner';
$route['request/receive'] = 'api/request/receive';
$route['request/donate/(:num)'] = 'api/request/donate/$1';
$route['request/receive/(:num)'] = 'api/requestWithReqId/receive/$1';
$route['request/removeDonate/(:num)'] = 'api/request/removeDonate/$1';
$route['request/removeReceive/(:num)'] = 'api/request/removeReceive/$1';
$route['request/matched'] = 'api/request/matched';
$route['request/sendRequest'] = 'api/request/sendRequest';
$route['request/receiveList/(:num)'] = 'api/request/receiveList/$1';
$route['request/donationDetails/(:num)'] = 'api/request/donationDetails/$1';
$route['request/receiveDetails/(:num)'] = 'api/request/receiveDetails/$1';
$route['request/editDonationDetails/(:num)'] = 'api/request/editDonationDetails/$1';
$route['request/editReceiveDetails/(:num)'] = 'api/request/editReceiveDetails/$1';
$route['request/changeDonationStatus/(:num)'] = 'api/request/changeDonationStatus/$1';
$route['request/changeReceiveStatus/(:num)'] = 'api/request/changeReceiveStatus/$1';
$route['request/matchedRequestByRegion/(:any)'] = 'api/request/matchedRequestByRegion/$1';
$route['request/matchedRequestDetailsByRequestId/(:num)'] = 'api/request/matchedRequestDetailsByRequestId/$1';
$route['request/matchedRequestDetails/(:num)'] = 'api/request/matchedRequestDetails/$1';
$route['request/matchedRequest'] = 'api/request/matchedRequest';

$route['request/donationList'] = 'api/request/donationList';
$route['request/donationList/(:num)'] = 'api/request/donationList/$1';
$route['request/donationList/(:num)/(:any)'] = 'api/request/donationList/$1/$2';
$route['request/donationListByStatus/(:any)'] = 'api/request/donationListByStatus/$1';
$route['request/donationListByRegion/(:any)'] = 'api/request/donationListByRegion/$1';


$route['request/receiveList'] = 'api/request/receiveList';
$route['request/receiveList/(:num)'] = 'api/request/receiveList/$1';
$route['request/receiveList/(:num)/(:any)'] = 'api/request/receiveList/$1/$2';
$route['request/receiveListByStatus/(:any)'] = 'api/request/receiveListByStatus/$1';
$route['request/receiveListByRegion/(:any)'] = 'api/request/receiveListByRegion/$1';


$route['request/getRequests'] = 'api/request/getRequests';
$route['request/getDonates'] = 'api/request/getDonates';

$route['request/accept/(:any)/(:num)'] = 'api/request/accept/$1/$2';



$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
