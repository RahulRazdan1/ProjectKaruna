<?php 
defined('BASEPATH') OR exit('No direct script access allowed'); 
date_default_timezone_set('Asia/Singapore');
require APPPATH . '/vendor/autoload.php';
use Twilio\Rest\Client;
class Request extends CI_Controller {  
	public function __construct(){ 
		parent::__construct();
		$this->load->database();
		//$this->oakter = $this->load->database('oakter',TRUE);
		$this->load->library(array('form_validation')); 
		$this->load->helper(array('url','html','form'));
	}
	
	public function token($data)
    {
        $jwt = new JWT();
        $jwtSecretKey = "Superman@77";
        $token = $jwt->encode($data, $jwtSecretKey, 'HS256');
        return $token;
    }
    
    public function get_token($dataToken){
        $jwt = new JWT();
        $jwtSecretKey = "Superman@77";
        $token = $jwt->decode($dataToken,$jwtSecretKey,true);
        return $token;
    }
	public function success_response($msg, $data)
    {
        $response = array(
          "success" => true,
          "msg" => $msg,
          "data" => $data
        );
        return $response;
    }
    
    public function failure_response($msg, $error)
    {
        $response = array(
          "success" => false,
          "msg" => $msg,
          "error" => $error
        );
        return $response;
    }
	public function matched(){
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $fromCol = '';
           $res = array();
            if($accesToken->userType == 'doner'){
                $result = $this->db->select('*')->where(array('userId' => $accesToken->id, 'isActive' => 1))->get('donates')->result_array();
                $i=0;
                foreach($result as $r) {
                    $subcategory = $this->db->select('*')->where(array('subcategoryId' => $r->subcategoryId))->get('donates')->row_array();
                    $res[$i] = $result;
                    $res[$i]['matchedRequest'] = $subcategory;
                 $i++;   
                }
            }elseif($accesToken->userType == 'receiver'){
                $result = $this->db->select('*')->where(array('userId' => $accesToken->id, 'isActive' => 1))->get('donates')->result_array();
                $i=0;
                foreach($result as $r) {
                    $receiver = $this->db->select('*')->where(array('subcategoryId' => $r->subcategoryId))->get('receive')->row_array();
                    $res[$i] = $result;
                    $res[$i]['matchedRequest'] = $receiver;  
                }
                
            } 
            $msg = 'Ok';
            $response = $this->success_response($msg, $res);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	public function sendRequest(){
	    $Authorization = $this->input->get_request_header("Authorization");
	    if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           
	    $inputData = file_get_contents('php://input');
	    $inputData = json_decode($inputData);
	    $donationId = $inputData->donationId;
	    $receiveId = $inputData->receiveId;
	    
	    $record = $this->db->select('*')->where(array('donationId' => $donationId, 'receiveId' => $receiveId, 'isActive' => 1))->get('requestMapping')->result_array();
	    if (count($record) > 0) {
            $msg = "Already matched";
            $response = $this->failure_response($msg, '');
        }
        $donerRegion = $this->db->select('*')->where(array('id' => $donationId, 'isActive' => 1))->get('donates')->row_array();
        $receiveRegion = $this->db->select('*')->where(array('id' => $receiveId, 'isActive' => 1))->get('receive')->row_array();
        $response = '';
        $date = date('Y-m-d H:i:s', time());
            if($accesToken->userType == 'doner'){
               $params = array(
                    'donationId' => $donationId,
                    'receiveId' => $receiveId,
                    'initiatedBy' => $donerRegion['createdBy'],
                    'region' => $donerRegion['region'],
                    'status' => 'Matched',
                    'createdAt' => $date,
                    'createdBy' => $accesToken->id,
                    'modifiedAt' => $date,
                    'modifiedBy' => $accesToken->id,
                ); 
                $this->db->insert('requestMapping',$params); 
                $requestMappingId = $this->db->insert_id();
                $request = $this->db->select('*')->where('id', $requestMappingId)->get('requestMapping')->row_array();
                $msg = "Request Matched";
                $response = $this->success_response($msg, $request);
            }
            else if($accesToken->userType == 'receiver'){
                $params = array(
                    'donationId' => $donationId,
                    'receiveId' => $receiveId,
                    'initiatedBy' => $receiveRegion['createdBy'],
                    'region' => $receiveRegion['region'],
                    'status' => 'Matched',
                    'createdAt' => $date,
                    'createdBy' => $accesToken->id,
                    'modifiedAt' => $date,
                    'modifiedBy' => $accesToken->id,
                ); 
                $this->db->insert('requestMapping',$params); 
                $requestMappingId = $this->db->insert_id();
                $request = $this->db->select('*')->where('id', $requestMappingId)->get('requestMapping')->row_array();
                $msg = "Request Matched";
                $response = $this->success_response($msg, $request);

                
        
                $notificationReceiverData = array(
                    'title' => "Offer Status",
                    'body' => "Your donation offer / request has been matched. Step 1: Please go to app and click on account to complete the transation. Step 2: Once the transaction is completed /rejected, please click the status button and change it to completed / rejected",
                ); 
                
                $notificationDonorData = array(
                    'title' => "Offer Status",
                    'body' => "Your donation offer / request has been matched. Step 1: Please go to app and click on account to complete the transation. Step 2: Once the transaction is completed /rejected, please click the status button and change it to completed / rejected",
                ); 

                $receiveData = $this->getUserByReceiveId($receiveId);
                // $sms = $this->sms($receiveData['mobile'], $notificationReceiverData['body']);
                $this->sendFCM($notificationReceiverData['title'], $notificationReceiverData['body'], $receiveData['deviceToken']);
                $donorData = $this->getUserByDonationId($donationId);
                // $sms = $this->sms($donorData['mobile'], $notificationDonorData['body']);
                $this->sendFCM($notificationDonorData['title'], $notificationDonorData['body'], $donorData['deviceToken']);
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	public function donationList($num=null, $status=null){
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    $requestStatus = array(
                "pending",
                "completed",
                "matched"
               );
            if(!empty($num) && !empty($status)){
                $status = strtolower($status);
                $indexOfStatus = in_array($status, $requestStatus); 
                if($indexOfStatus < 0){
                    $msg = "Please give suggested status only";
                    $response = $this->failure_response($msg, '');
                }else{
                    $user = $this->db->select('*')->where(array('id' => $num))->get('user')->row_array(); 
                    if($user['userType'] == "admin") {
                        $donationRequestData = $this->db->where(array('isActive' => 1))->get('donates')->result_array(); 
                    }else {
                        $this->db->select('*');
                        $donationRequestData = $this->db->where(array('isActive' => 1, 'userId' => $num, 'status' => $status))->get('donates')->result_array();  
                    } 
                }
            }   
            elseif(!empty($status)){
                $user = $this->db->select('*')->where(array('id' => $num))->get('user')->row_array(); 
                if($user['userType'] == "admin") {
                    $donationRequestData = $this->db->where(array('isActive' => 1))->get('donates')->result_array(); 
                }else {
                    $this->db->select('*');
                    $donationRequestData = $this->db->where(array('isActive' => 1, 'userId' => $num))->get('donates')->result_array();  
                } 
            }else{
                $this->db->select('*');
                $donationRequestData = $this->db->where(array('isActive' => 1))->get('donates')->result_array(); 
            }
            
            
            $result = array();
            $i =0;
            foreach($donationRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
		
	public function donationListByStatus($status){
	   // echo "<pre>";
    //         print_r('VIkas');
    //         die;
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    
		    $requestStatus = array(
                "pending",
                "completed",
                "matched"
               );
               
                $status = strtolower($status);
               $indexOfStatus = in_array($status, $requestStatus); 
               if($indexOfStatus < 0){
                   $msg = "Please give suggested status only";
                   $response = $this->failure_response($msg, '');
               }else{
                 $this->db->select('*');
                 $donationRequestData = $this->db->where(array('isActive' => 1, 'status' => $status))->get('donates')->result_array();   
               
            
            $result = array();
            $i =0;
            foreach($donationRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
               }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	
	public function donationListByRegion($region){
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
            $region = str_replace("%20"," ",$region);
            $this->db->select('*');
            $donationRequestData = $this->db->where(array('isActive' => 1, 'region' => $region))->get('donates')->result_array();
            $result = array();
            $i =0;
            foreach($donationRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	
	
	public function receiveList($num=null, $status=null){
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    
		    $requestStatus = array(
                "pending",
                "completed",
                "matched"
               );
            if(!empty($num) && !empty($status)){
                $status = strtolower($status);
                $indexOfStatus = in_array($status, $requestStatus); 
                if($indexOfStatus < 0){
                    $msg = "Please give suggested status only";
                    $response = $this->failure_response($msg, '');
                }else{
                    $user = $this->db->select('*')->where(array('id' => $num))->get('user')->row_array(); 
                    if($user['userType'] == "admin") {
                        $receiveRequestData = $this->db->where(array('isActive' => 1))->get('receive')->result_array(); 
                    }else {
                        $this->db->select('*');
                        $receiveRequestData = $this->db->where(array('isActive' => 1, 'userId' => $num, 'status' => $status))->get('receive')->result_array();  
                    }  
                }
            }   
            elseif(!empty($status)){
                $user = $this->db->select('*')->where(array('id' => $num))->get('user')->row_array(); 
                if($user['userType'] == "admin") {
                    $receiveRequestData = $this->db->where(array('isActive' => 1))->get('receive')->result_array(); 
                }else {
                    $this->db->select('*');
                    $receiveRequestData = $this->db->where(array('isActive' => 1, 'userId' => $num))->get('receive')->result_array();  
                } 
            }else{
               $this->db->select('*');
               $receiveRequestData = $this->db->where(array('isActive' => 1))->get('receive')->result_array(); 
            }
            
            
            $result = array();
            $i =0;
            foreach($receiveRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
		
	public function receiveListByStatus($status){
	   // echo "<pre>";
    //         print_r('VIkas');
    //         die;
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    
		    $requestStatus = array(
                "pending",
                "completed",
                "matched"
               );
               
                $status = strtolower($status);
               $indexOfStatus = in_array($status, $requestStatus); 
               if($indexOfStatus < 0){
                   $msg = "Please give suggested status only";
                   $response = $this->failure_response($msg, '');
               }else{
                 $this->db->select('*');
                 $receiveRequestData = $this->db->where(array('isActive' => 1, 'status' => $status))->get('receive')->result_array();   
               
            
            $result = array();
            $i =0;
            foreach($receiveRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
               }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	
	public function receiveListByRegion($region){
	   // echo "<pre>";
    //         print_r('VIkas');
    //         die;
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
           $region = str_replace("%20"," ",$region);
		    
                 $this->db->select('*');
                 $receiveRequestData = $this->db->where(array('isActive' => 1, 'region' => $region))->get('receive')->result_array();   
               
            
            $result = array();
            $i =0;
            foreach($receiveRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['__v'] = 1;
                $result[$i]['isActive'] = true;
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = ($rrd['createdAt']?date_format(date_create($rrd['createdAt']),"m/d/Y h:i:s A"):'');
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name']; 
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}

	public function donationDetails($num){
	   // echo "<pre>";
    //         print_r('VIkas');
    //         die;
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    
            $this->db->select('*');
            $donationRequestData = $this->db->where(array('isActive' => 1, 'id' => $num))->get('donates')->row_array();   
            
            $categoryData = $this->db->select('*')->where('id', $donationRequestData['categoryId'])->get('categories')->row_array();
            $subCategoryData = $this->db->select('*')->where('id', $donationRequestData['subcategoryId'])->get('categories')->row_array();
            $donationRequestData['_id'] = $donationRequestData['id'];
            $donationRequestData['__v'] = 1;
            $donationRequestData['isActive'] = true;
            $donationRequestData['userId'] = $donationRequestData['userId'];
            $donationRequestData['categoryId'] = $donationRequestData['categoryId'];
            $donationRequestData['subcategoryId'] = $donationRequestData['subcategoryId'];
            $donationRequestData['description'] = $donationRequestData['description'];
            $donationRequestData['image'] = $donationRequestData['image'];
            $donationRequestData['region'] = $donationRequestData['region'];
            $donationRequestData['deliveryType'] = $donationRequestData['deliveryType'];
            $donationRequestData['address'] = $donationRequestData['address'];
            $donationRequestData['cretedAt'] = ($donationRequestData['createdAt']?date_format(date_create($donationRequestData['createdAt']),"m/d/Y h:i:s A"):'');
            $donationRequestData['createdBy'] = $donationRequestData['createdBy'];
            $donationRequestData['modifiedAt'] = $donationRequestData['modifiedAt'];
            $donationRequestData['modifiedBy'] = $donationRequestData['modifiedBy'];
            $donationRequestData['status'] = $donationRequestData['status'];
            $donationRequestData['categoryName'] = $categoryData['name'];
            $donationRequestData['subCategoryName'] = $subCategoryData['name']; 
                
            $msg = "OK";
            $response = $this->success_response($msg, $donationRequestData);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	public function receiveDetails($num){
	   // echo "<pre>";
    //         print_r('VIkas');
    //         die;
	    $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    
            $this->db->select('*');
            $receiveRequestData = $this->db->where(array('isActive' => 1, 'id' => $num))->get('receive')->row_array();   
            $categoryData = $this->db->select('*')->where('id', $receiveRequestData['categoryId'])->get('categories')->row_array();
            
            $subCategoryData = $this->db->select('*')->where('id', $receiveRequestData['subcategoryId'])->get('categories')->row_array();
            
            $receiveRequestData['_id'] = $receiveRequestData['id'];
            $receiveRequestData['__v'] = 1;
            $receiveRequestData['isActive'] = true;
            $receiveRequestData['userId'] = $receiveRequestData['userId'];
            $receiveRequestData['categoryId'] = $receiveRequestData['categoryId'];
            $receiveRequestData['subcategoryId'] = $receiveRequestData['subcategoryId'];
            $receiveRequestData['description'] = $receiveRequestData['description'];
            $receiveRequestData['region'] = $receiveRequestData['region'];
            $receiveRequestData['deliveryType'] = $receiveRequestData['deliveryType'];
            $receiveRequestData['address'] = $receiveRequestData['address'];
            $receiveRequestData['cretedAt'] = ($receiveRequestData['createdAt']?date_format(date_create($receiveRequestData['createdAt']),"m/d/Y h:i:s A"):'');
            $receiveRequestData['createdBy'] = $receiveRequestData['createdBy'];
            $receiveRequestData['modifiedAt'] = $receiveRequestData['modifiedAt'];
            $receiveRequestData['modifiedBy'] = $receiveRequestData['modifiedBy'];
            $receiveRequestData['status'] = $receiveRequestData['status'];
            $receiveRequestData['categoryName'] = $categoryData['name'];
            $receiveRequestData['subCategoryName'] = $subCategoryData['name']; 
                
            $msg = "OK";
            $response = $this->success_response($msg, $receiveRequestData);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
	}
	
	
	
	public function editDonationDetails($num)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $doner = $this->db->select('*')->where('id', $num)->get('donates')->row_array();
            if($accesToken->userType == 'doner'){
                if(!empty($_FILES["image"]['name']))
                { 
                    $fileName = $this->upload_doc_single('uploads/doner', $_FILES["image"], 'image');
                }else{
                    $fileName = $doner['image'];
                }
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "categoryId" => $_POST['categoryId'],
                    "subcategoryId" => $_POST['subcategoryId'],
                    "description" => $_POST['description'],
                    "image" => $fileName,
                    "region" => $_POST['region'],
                    "deliveryType" => $_POST['deliveryType'],
                    "address" => $_POST['address'],
                    "status" => "Pending",
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $num);
                $this->db->update('donates', $params);
                $msg = "Donation detail updated";
                $response = $this->success_response($msg, '');
        //   echo "<pre>";
        //   print_r($params);
        //   die;
            }else{
                $msg = "Only Doner can edit the Donate request";
                $response = $this->failure_response($msg, '');
            }  
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
	public function editReceiveDetails($num)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
            $inputData = file_get_contents('php://input');
            $_POST = (array)json_decode($inputData);
           $receive = $this->db->select('*')->where('id', $num)->get('receive')->row_array();
            if($accesToken->userType == 'receiver'){
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "categoryId" => $_POST['categoryId'],
                    "subcategoryId" => $_POST['subcategoryId'],
                    "description" => $_POST['description'],
                    "region" => $_POST['region'],
                    "deliveryType" => $_POST['deliveryType'],
                    "address" => $_POST['address'],
                    "status" => "Pending",
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $num);
                $this->db->update('receive', $params);
                $msg = "Receive detail updated";
                $response = $this->success_response($msg, '');
        //   echo "<pre>";
        //   print_r($params);
        //   die;
            }else{
                $msg = "Only Receiver can Edit the Recieve request";
                $response = $this->failure_response($msg, '');
            }  
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
     
	public function changeDonationStatus($num)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $donationId = '';
           $receiveId = '';
           $mappedReq = '';
           $requestStatus = array(
                "pending",
                "completed",
                "matched"
               );
               
            $inputData = file_get_contents('php://input');
            $inputData = json_decode($inputData);
            $status = strtolower($inputData->status);
            $indexOfStatus = in_array($status, $requestStatus);
            if($indexOfStatus != 1){
                $msg = "Please give suggested status only";
                $response = $this->failure_response($msg, '');
            }else{
                $donationId = $num;
                $mappedReq = $this->db->select('*')->where(array('donationId' => $donationId, 'isActive' => 1))->get('requestMapping')->row_array();
                if(!empty($mappedReq)){
                    if($status == "matched"){
                        $msg = "You can not set status as Matched";
                        $response = $this->failure_response($msg, '');
                    }else{
                        $paramRequestMap = array(
                            'status' => $inputData->status,
                            'isActive' => 0
                        );
                        $this->db->where('id', $mappedReq['id']);
                        $this->db->update('requestMapping', $paramRequestMap);
                        $requestMap = $this->db->where('id', $mappedReq['id'])->get('requestMapping')->row_array();
                        $paramDonate = array(
                            'status' => $inputData->status
                        );
                        $this->db->where('id', $requestMap['donationId']);
                        $this->db->update('donates', $paramDonate);
                        $this->db->where('id', $requestMap['receiveId']);
                        $this->db->update('receive', $paramDonate);
                        $msg = "Donation Status Changed";
                        $response = $this->success_response($msg, '');
                        $notificationDonorData = array();
                        if($status == 'pending'){
                           $notificationDonorData = array(
                                'title' => "Offer Status",
                                'body' => "You have rejected Donation Offer"
                            ); 
                        }
                        
                        if($status == 'completed'){
                           $notificationDonorData = array(
                               'title' => "Offer Status",
                               'body' => "You have completed Donation Offer"
                            ); 
                        }
                        $donorData = $this->getUserByDonationId($donationId);
                        // $sms = $this->sms($donorData['mobile'], $notificationDonorData['body']);
                        $this->sendFCM($notificationDonorData['title'], $notificationDonorData['body'], $donorData['deviceToken']);
                    }
                } else{
                    $msg = "This donation request / offer is already completed.";
                    $response = $this->failure_response($msg, '');
                }
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
	public function changeReceiveStatus($num)
    {
        $Authorization = $this->input->get_request_header("Authorization");
        if(!empty($Authorization)){
            $accesToken = $this->get_token($Authorization);
            $donationId = '';
            $receiveId = '';
            $mappedReq = '';
            $requestStatus = array(
                "pending",
                "completed",
                "matched"
            );
            
            $inputData = file_get_contents('php://input');
            $inputData = json_decode($inputData);
            $status = strtolower($inputData->status);
            $indexOfStatus = in_array($status, $requestStatus);
            if($indexOfStatus != 1){
                $msg = "Please give suggested status only";
                $response = $this->failure_response($msg, '');
            }else{
                $receiveId = $num;
                $mappedReq = $this->db->select('*')->where(array('receiveId' => $receiveId, 'isActive' => 1))->get('requestMapping')->row_array();
                if(!empty($mappedReq)){
                    if($status == "matched"){
                        $msg = "You can not set status as Matched";
                        $response = $this->failure_response($msg, '');
                        print_r('naruto');
                    }else{
                        $paramRequestMap = array(
                            'status' => $inputData->status,
                            'isActive' => 0
                        );
                        $this->db->where('id', $mappedReq['id']);
                        $this->db->update('requestMapping', $paramRequestMap);
                        $requestMap = $this->db->where('id', $mappedReq['id'])->get('requestMapping')->row_array();
                        $paramDonate = array(
                            'status' => $inputData->status
                        );
                        $this->db->where('id', $requestMap['donationId']);
                        $this->db->update('donates', $paramDonate);
                        $this->db->where('id', $requestMap['receiveId']);
                        $this->db->update('receive', $paramDonate);
                        $msg = "Donation Status Changed";
                        $response = $this->success_response($msg, '');
                        $notificationReceiverData = array();
                        if($status == 'pending'){
                            $notificationReceiverData = array(
                                'title' => "Offer Status",
                                'body' => "You have rejected Donation Offer"
                            ); 
                        }
                        
                        if($status == 'completed'){
                            $notificationReceiverData = array(
                                'title' => "Offer Status",
                                'body' => "You have completed Donation Offer"
                            ); 
                        }
                        $receiveData = $this->getUserByReceiveId($receiveId);
                        // $sms = $this->sms($receiveData['mobile'], $notificationReceiverData['body']);
                        $this->sendFCM($notificationReceiverData['title'], $notificationReceiverData['body'], $receiveData['deviceToken']);
                    }
                } else {
                    $msg = "This donation request / offer is already completed.";
                    $response = $this->failure_response($msg, $msg);
                }
            }
             
        }else{
            $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
        }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    function getUserByDonationId($donateId){
        $donateUser = $this->db->select('*')->where(array('id' => $donateId))->get('donates')->row_array();
        $user = $this->db->select('*')->where(array('id' => $donateUser['userId']))->get('user')->row_array();
        return $user;
    }
    function getUserByReceiveId($receiveId){
        $donateUser = $this->db->select('*')->where(array('id' => $receiveId))->get('receive')->row_array();
        $user = $this->db->select('*')->where(array('id' => $donateUser['userId']))->get('user')->row_array();
        return $user;
    }
    
    public function matchedRequestByRegion($num){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $num = str_replace("%20"," ",$num);
            $RequestMap = $this->db->select('*')->where(array('region' => $num, 'isActive' => 1))->get('requestMapping')->result_array();
            $result = array();
            $i =0;
            foreach($RequestMap as $rm){
                $donate = $this->db->select('*')->where(array('id' => $rm['donationId']))->get('donates')->row_array();
                $receives = $this->db->select('*')->where(array('id' => $rm['receiveId']))->get('receive')->row_array();
                $categoryDetails = $this->db->select('*')->where(array('id' => $donate['categoryId']))->get('categories')->row_array();
                $subcategoryDetails = $this->db->select('*')->where(array('id' => $donate['subcategoryId']))->get('categories')->row_array();
                $donerDetails = $this->db->select('*')->where(array('id' => $donate['userId']))->get('user')->row_array();
                $receiverDetails = $this->db->select('*')->where(array('id' => $receives['userId']))->get('user')->row_array();
                
                $result[$i]['_id'] = $rm['id'];
                $result[$i]['donationId'] = $rm['donationId'];
                $result[$i]['receiveId'] = $rm['receiveId'];
                $result[$i]['initiatedBy'] = $rm['initiatedBy'];
                $result[$i]['region'] = $rm['region'];
                $result[$i]['categoryDetails']['_id'] = $categoryDetails['id'];
                $result[$i]['categoryDetails']['name'] = $categoryDetails['name'];
                $result[$i]['subcategoryDetails']['_id'] = $subcategoryDetails['id'];
                $result[$i]['subcategoryDetails']['name'] = $subcategoryDetails['name'];
                $result[$i]['donerDetails']['_id'] = $donerDetails['id'];
                $result[$i]['donerDetails']['firstName'] = $donerDetails['firstName'];
                $result[$i]['donerDetails']['lastName'] = $donerDetails['lastName'];
                $result[$i]['donerDetails']['email'] = $donerDetails['email'];
                $result[$i]['donerDetails']['mobile'] = $donerDetails['mobile'];
                $result[$i]['donerDetails']['region'] = $donerDetails['region'];
                $result[$i]['donerDetails']['address'] = $donerDetails['address'];
                $result[$i]['receiverDetails']['_id'] = $receiverDetails['id'];
                $result[$i]['receiverDetails']['firstName'] = $receiverDetails['firstName'];
                $result[$i]['receiverDetails']['lastName'] = $receiverDetails['lastName'];
                $result[$i]['receiverDetails']['email'] = $receiverDetails['email'];
                $result[$i]['receiverDetails']['mobile'] = $receiverDetails['mobile'];
                $result[$i]['receiverDetails']['region'] = $receiverDetails['region'];
                $result[$i]['receiverDetails']['address'] = $receiverDetails['address'];
                
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    public function matchedRequest(){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
            $accesToken = $this->get_token($Authorization);
            $RequestMap = $this->db->select('*')->where(array('isActive' => 1))->get('requestMapping')->result_array();
            $result = array();
            $i =0;
            foreach($RequestMap as $rm){
                $donate = $this->db->select('*')->where(array('id' => $rm['donationId']))->get('donates')->row_array();
                $receives = $this->db->select('*')->where(array('id' => $rm['receiveId']))->get('receive')->row_array();
                $categoryDetails = $this->db->select('*')->where(array('id' => $donate['categoryId']))->get('categories')->row_array();
                $subcategoryDetails = $this->db->select('*')->where(array('id' => $donate['subcategoryId']))->get('categories')->row_array();
                $donerDetails = $this->db->select('*')->where(array('id' => $donate['userId']))->get('user')->row_array();
                $receiverDetails = $this->db->select('*')->where(array('id' => $receives['userId']))->get('user')->row_array();
                
                $result[$i]['id'] = $rm['id'];
                $result[$i]['donationId'] = $rm['donationId'];
                $result[$i]['receiveId'] = $rm['receiveId'];
                $result[$i]['initiatedBy'] = $rm['initiatedBy'];
                $result[$i]['region'] = $rm['region'];
                $result[$i]['categoryDetails']['_id'] = $categoryDetails['id'];
                $result[$i]['categoryDetails']['name'] = $categoryDetails['name'];
                $result[$i]['subcategoryDetails']['_id'] = $subcategoryDetails['id'];
                $result[$i]['subcategoryDetails']['name'] = $subcategoryDetails['name'];
                $result[$i]['donerDetails']['_id'] = $donerDetails['id'];
                $result[$i]['donerDetails']['firstName'] = $donerDetails['firstName'];
                $result[$i]['donerDetails']['lastName'] = $donerDetails['lastName'];
                $result[$i]['donerDetails']['email'] = $donerDetails['email'];
                $result[$i]['donerDetails']['mobile'] = $donerDetails['mobile'];
                $result[$i]['donerDetails']['region'] = $donerDetails['region'];
                $result[$i]['donerDetails']['address'] = $donerDetails['address'];
                $result[$i]['receiverDetails']['_id'] = $receiverDetails['id'];
                $result[$i]['receiverDetails']['firstName'] = $receiverDetails['firstName'];
                $result[$i]['receiverDetails']['lastName'] = $receiverDetails['lastName'];
                $result[$i]['receiverDetails']['email'] = $receiverDetails['email'];
                $result[$i]['receiverDetails']['mobile'] = $receiverDetails['mobile'];
                $result[$i]['receiverDetails']['region'] = $receiverDetails['region'];
                $result[$i]['receiverDetails']['address'] = $receiverDetails['address'];
                
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
      
    public function matchedRequestDetails($num){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
            $RequestMap = $this->db->select('*')->where(array('id' => $num,'isActive' => 1))->get('requestMapping')->result_array();
            $result = array();
            $i =0;
            foreach($RequestMap as $rm){
                $donate = $this->db->select('*')->where(array('id' => $rm['donationId']))->get('donates')->row_array();
                $receives = $this->db->select('*')->where(array('id' => $rm['receiveId']))->get('receive')->row_array();
                $categoryDetails = $this->db->select('*')->where(array('id' => $donate['categoryId']))->get('categories')->row_array();
                $subcategoryDetails = $this->db->select('*')->where(array('id' => $donate['subcategoryId']))->get('categories')->row_array();
                $donerDetails = $this->db->select('*')->where(array('id' => $donate['userId']))->get('user')->row_array();
                $receiverDetails = $this->db->select('*')->where(array('id' => $receives['userId']))->get('user')->row_array();
                
                $result[$i]['_id'] = $rm['id'];
                $result[$i]['donationId'] = $rm['donationId'];
                $result[$i]['receiveId'] = $rm['receiveId'];
                $result[$i]['initiatedBy'] = $rm['initiatedBy'];
                $result[$i]['region'] = $rm['region'];
                $result[$i]['categoryDetails']['_id'] = $categoryDetails['id'];
                $result[$i]['categoryDetails']['name'] = $categoryDetails['name'];
                $result[$i]['subcategoryDetails']['_id'] = $subcategoryDetails['id'];
                $result[$i]['subcategoryDetails']['name'] = $subcategoryDetails['name'];
                $result[$i]['donerDetails']['_id'] = $donerDetails['id'];
                $result[$i]['donerDetails']['firstName'] = $donerDetails['firstName'];
                $result[$i]['donerDetails']['lastName'] = $donerDetails['lastName'];
                $result[$i]['donerDetails']['email'] = $donerDetails['email'];
                $result[$i]['donerDetails']['mobile'] = $donerDetails['mobile'];
                $result[$i]['donerDetails']['region'] = $donerDetails['region'];
                $result[$i]['donerDetails']['address'] = $donerDetails['address'];
                $result[$i]['receiverDetails']['_id'] = $receiverDetails['id'];
                $result[$i]['receiverDetails']['firstName'] = $receiverDetails['firstName'];
                $result[$i]['receiverDetails']['lastName'] = $receiverDetails['lastName'];
                $result[$i]['receiverDetails']['email'] = $receiverDetails['email'];
                $result[$i]['receiverDetails']['mobile'] = $receiverDetails['mobile'];
                $result[$i]['receiverDetails']['region'] = $receiverDetails['region'];
                $result[$i]['receiverDetails']['address'] = $receiverDetails['address'];
                
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
      
    public function matchedRequestDetailsByRequestId($num){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
            $orCon = 'donationId = "'.$num.'" or receiveId = "'.$num.'"';
            $this->db->where('isActive', 1);
            $RequestMap = $this->db->where($orCon)->get('requestMapping')->result_array();
            $result = array();
            $i =0;
            foreach($RequestMap as $rm){
                $donate = $this->db->select('*')->where(array('id' => $rm['donationId']))->get('donates')->row_array();
                $receives = $this->db->select('*')->where(array('id' => $rm['receiveId']))->get('receive')->row_array();
                $categoryDetails = $this->db->select('*')->where(array('id' => $donate['categoryId']))->get('categories')->row_array();
                $subcategoryDetails = $this->db->select('*')->where(array('id' => $donate['subcategoryId']))->get('categories')->row_array();
                $donerDetails = $this->db->select('*')->where(array('id' => $donate['userId']))->get('user')->row_array();
                $receiverDetails = $this->db->select('*')->where(array('id' => $receives['userId']))->get('user')->row_array();
                
                $result[$i]['_id'] = $rm['id'];
                $result[$i]['donationId'] = $rm['donationId'];
                $result[$i]['receiveId'] = $rm['receiveId'];
                $result[$i]['initiatedBy'] = $rm['initiatedBy'];
                $result[$i]['region'] = $rm['region'];
                $result[$i]['categoryDetails']['_id'] = $categoryDetails['id'];
                $result[$i]['categoryDetails']['name'] = $categoryDetails['name'];
                $result[$i]['subcategoryDetails']['_id'] = $subcategoryDetails['id'];
                $result[$i]['subcategoryDetails']['name'] = $subcategoryDetails['name'];
                $result[$i]['donerDetails']['_id'] = $donerDetails['id'];
                $result[$i]['donerDetails']['firstName'] = $donerDetails['firstName'];
                $result[$i]['donerDetails']['lastName'] = $donerDetails['lastName'];
                $result[$i]['donerDetails']['email'] = $donerDetails['email'];
                $result[$i]['donerDetails']['mobile'] = $donerDetails['mobile'];
                $result[$i]['donerDetails']['region'] = $donerDetails['region'];
                $result[$i]['donerDetails']['address'] = $donerDetails['address'];
                $result[$i]['receiverDetails']['_id'] = $receiverDetails['id'];
                $result[$i]['receiverDetails']['firstName'] = $receiverDetails['firstName'];
                $result[$i]['receiverDetails']['lastName'] = $receiverDetails['lastName'];
                $result[$i]['receiverDetails']['email'] = $receiverDetails['email'];
                $result[$i]['receiverDetails']['mobile'] = $receiverDetails['mobile'];
                $result[$i]['receiverDetails']['region'] = $receiverDetails['region'];
                $result[$i]['receiverDetails']['address'] = $receiverDetails['address'];
                
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    public function getRequests(){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    $orCon = 'status = "Pending"';
            $this->db->select('*');
            $this->db->where('isActive', 1);
            $receiveRequestData = $this->db->where($orCon)->get('receive')->result_array();
            $result = array();
            $i =0;
            foreach($receiveRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = $rrd['createdAt'];
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name'];
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    public function getDonates(){
        $Authorization = $this->input->get_request_header("Authorization");
		if(!empty($Authorization)){
		    $orCon = 'status = "Pending"';
            $this->db->select('*');
            $this->db->where('isActive', 1);
            $donationRequestData = $this->db->where($orCon)->get('donates')->result_array();
            $result = array();
            $i =0;
            foreach($donationRequestData as $rrd) {
                $categoryData = $this->db->select('*')->where('id', $rrd['categoryId'])->get('categories')->row_array();
                $subCategoryData = $this->db->select('*')->where('id', $rrd['subcategoryId'])->get('categories')->row_array();
                $result[$i]['_id'] = $rrd['id'];
                $result[$i]['userId'] = $rrd['userId'];
                $result[$i]['categoryId'] = $rrd['categoryId'];
                $result[$i]['subcategoryId'] = $rrd['subcategoryId'];
                $result[$i]['description'] = $rrd['description'];
                $result[$i]['image'] = $rrd['image'];
                $result[$i]['region'] = $rrd['region'];
                $result[$i]['deliveryType'] = $rrd['deliveryType'];
                $result[$i]['address'] = $rrd['address'];
                $result[$i]['cretedAt'] = $rrd['createdAt'];
                $result[$i]['createdBy'] = $rrd['createdBy'];
                $result[$i]['modifiedAt'] = $rrd['modifiedAt'];
                $result[$i]['modifiedBy'] = $rrd['modifiedBy'];
                $result[$i]['status'] = $rrd['status'];
                $result[$i]['categoryName'] = $categoryData['name'];
                $result[$i]['subCategoryName'] = $subCategoryData['name'];
                $i++;
            }
            $msg = "OK";
            $response = $this->success_response($msg, $result);
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    
    public function donate($num)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $this->form_validation->set_rules('categoryId', 'Category', 'required');
           $this->form_validation->set_rules('subcategoryId', 'Sub Category', 'required');
           $this->form_validation->set_rules('description', 'Description', 'required');
           $this->form_validation->set_rules('region', 'Region', 'required');
           $this->form_validation->set_rules('deliveryType', 'Delivery Type', 'required');
           
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                $error[] = array(
                    'param' => array_keys($m)[0],
                    'msg' => $m[$error_key]
                );
                $msg = '';
                $response = $this->failure_response($msg, $error);
            }else{
                if($accesToken->userType == 'doner'){
                    if(!empty($_FILES["image"]['name']))
                    { 
                        $fileName = $this->upload_doc_single('uploads/doner', $_FILES["image"], 'image');
                    }else{
                        $fileName = "";
                    }
                    
                    $date = date('Y-m-d H:i:s', time());
                    $params = array(
                        "userId" => $accesToken->id,
                        "categoryId" => $_POST['categoryId'],
                        "subcategoryId" => $_POST['subcategoryId'],
                        "description" => $_POST['description'],
                        "image" => $fileName,
                        "region" => $_POST['region'],
                        "deliveryType" => $_POST['deliveryType'],
                        "address" => $_POST['address'],
                        "status" => "Pending",
        				'createdAt' => $date,
        				'createdBy' => $accesToken->id,
        				'modifiedAt' => $date,
        				'modifiedBy' => $accesToken->id,
                    );
                    $this->db->insert('donates',$params); 
                    $donateId = $this->db->insert_id();
                    $donate = $this->db->select('*')->where('id', $donateId)->get('donates')->row_array();
                    $msg = "Request added";
                    $response = $this->success_response($msg, $donate);
                }else{
                    $msg = "Only Doner can add the Donate request";
                    $response = $this->failure_response($msg, '');
                }     
            }
       }else{
            $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    public function requestWithReqId($num)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $inputData = file_get_contents('php://input');
           $_POST = (array)json_decode($inputData);
           $this->form_validation->set_rules('categoryId', 'Category', 'required');
           $this->form_validation->set_rules('subcategoryId', 'Sub Category', 'required');
           $this->form_validation->set_rules('description', 'Description', 'required');
           $this->form_validation->set_rules('region', 'Region', 'required');
           $this->form_validation->set_rules('deliveryType', 'Delivery Type', 'required');
           
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                $error[] = array(
                    'param' => array_keys($m)[0],
                    'msg' => $m[$error_key]
                );
                $msg = '';
                $response = $this->failure_response($msg, $error);
            }else{
                if($accesToken->userType == 'receiver'){
                   $date = date('Y-m-d H:i:s', time());
                    $params = array(
                        "userId" => $accesToken->id,
                        "categoryId" => $_POST['categoryId'],
                        "subcategoryId" => $_POST['subcategoryId'],
                        "description" => $_POST['description'],
                        "region" => $_POST['region'],
                        "deliveryType" => $_POST['deliveryType'],
                        "address" => $_POST['address'],
                        "status" => "Pending",
        				'createdAt' => $date,
        				'createdBy' => $accesToken->id,
        				'modifiedAt' => $date,
        				'modifiedBy' => $accesToken->id,
                    );
                    $this->db->insert('receive',$params); 
                    $receiveId = $this->db->insert_id();
                    $receive = $this->db->select('*')->where('id', $receiveId)->get('receive')->row_array();
                    $msg = "Request added";
                    $response = $this->success_response($msg, $receive);
                }else{
                    $msg = "Only Receiver can add the Recieve request";
                    $response = $this->failure_response($msg, '');
                }     
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    
    public function accept($type, $requestId)
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           
            if($type == "donation") {
        	    $record = $this->db->select('*')->where(array('receiveId' => $requestId, 'isActive' => 1))->get('requestMapping')->result_array();
        	    if (count($record) > 0) {
                    $msg = "Already matched";
                    $response = $this->failure_response($msg, '');
                    header('Content-Type: application/json');
                    echo json_encode($response); 
                    return;
                }
                $receiveRequestData = $this->db->where(array('id' => $requestId))->get('receive')->row_array();
        	    if (empty($receiveRequestData)) {
                    $msg = "Request not found";
                    $response = $this->failure_response($msg, '');
                    header('Content-Type: application/json');
                    echo json_encode($response); 
                    return;
                }
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "userId" => $accesToken->id,
                    "categoryId" => $receiveRequestData['categoryId'],
                    "subcategoryId" => $receiveRequestData['subcategoryId'],
                    "description" => $receiveRequestData['description'],
                    "region" => $receiveRequestData['region'],
                    "deliveryType" => $receiveRequestData['deliveryType'],
                    "address" => $receiveRequestData['address'],
                    "status" => "Matched",
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->insert('donates',$params); 
                $donateId = $this->db->insert_id();
                $donate = $this->db->select('*')->where('id', $donateId)->get('donates')->row_array();
                $msg = "OK";
                $data = array(
                    'donationId' => $donateId,
                    'receiveId' => $requestId,
                    'initiatedBy' => $accesToken->id,
                    );
                $this->requestMappinMganager($donateId,$requestId,$accesToken->id ,"receive");
                
                $paramsReceive = array(
                    "status" => "Matched",
                );
                
                $this->db->where('id', $requestId);
                $this->db->update('receive', $paramsReceive);
                $response = $this->success_response($msg, $donate);
               
            }else if($type == "receive") {
        	    $record = $this->db->select('*')->where(array('donationId' => $requestId, 'isActive' => 1))->get('requestMapping')->result_array();
        	    if (count($record) > 0) {
                    $msg = "Already matched";
                    $response = $this->failure_response($msg, '');
                    header('Content-Type: application/json');
                    echo json_encode($response); 
                    return;
                }
                $donateRequestData = $this->db->where(array('id' => $requestId))->get('donates')->row_array();
        	    if (empty($donateRequestData)) {
                    $msg = "Request not found";
                    $response = $this->failure_response($msg, '');
                    header('Content-Type: application/json');
                    echo json_encode($response); 
                    return;
                }
                
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "userId" => $accesToken->id,
                    "categoryId" => $donateRequestData['categoryId'],
                    "subcategoryId" => $donateRequestData['subcategoryId'],
                    "description" => $donateRequestData['description'],
                    "region" => $donateRequestData['region'],
                    "deliveryType" => $donateRequestData['deliveryType'],
                    "address" => $donateRequestData['address'],
                    "status" => "Matched",
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->insert('receive',$params); 
                $receiveId = $this->db->insert_id();
                $receive = $this->db->select('*')->where('id', $receiveId)->get('receive')->row_array();
                
                $msg = "OK";
                
                $this->requestMappinMganager($requestId,$receiveId,$accesToken->id ,"doner");
                $paramsDonates = array(
                    "status" => "Matched",
                );
                
                $this->db->where('id', $requestId);
                $this->db->update('donates', $paramsDonates);
                $response = $this->success_response($msg, $receive);
                
            }
                 
           
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }

    
	public function requestMappinMganager($donateId,$requestId,$initiatedBy ,$flag){
	    
                // echo "<pre>";
                // print_r($requestId);
                // die;
	    $donationId = $donateId;
	    $receiveId = $requestId;
	    
	    $record = $this->db->select('*')->where(array('donationId' => $donationId, 'receiveId' => $receiveId, 'isActive' => 1))->get('requestMapping')->result_array();
	    if (count($record) > 0) {
            $msg = "Already matched";
            $response = $this->failure_response($msg, '');
            header('Content-Type: application/json');
            echo json_encode($response); 
            return;
        }
        $donerRegion = $this->db->select('*')->where(array('id' => $donationId, 'isActive' => 1))->get('donates')->row_array();
        $receiveRegion = $this->db->select('*')->where(array('id' => $receiveId, 'isActive' => 1))->get('receive')->row_array();
        $response = '';
        $date = date('Y-m-d H:i:s', time());
        if($flag == 'doner'){
            $params = array(
                'donationId' => $donationId,
                'receiveId' => $receiveId,
                'initiatedBy' => $donerRegion['createdBy'],
                'region' => $donerRegion['region'],
                'status' => 'Matched',
                'createdAt' => $date,
                'createdBy' => $initiatedBy,
                'modifiedAt' => $date,
                'modifiedBy' => $initiatedBy,
            ); 
            $this->db->insert('requestMapping',$params); 
            $requestMappingId = $this->db->insert_id();
            $request = $this->db->select('*')->where('id', $requestMappingId)->get('requestMapping')->row_array();
            // $msg = "Request Mapped";
            // $response = $this->success_response($msg, $request);
        }
        else if($flag == 'receive'){
            $params = array(
                'donationId' => $donationId,
                'receiveId' => $receiveId,
                'initiatedBy' => $receiveRegion['createdBy'],
                'region' => $receiveRegion['region'],
                'status' => 'Matched',
                'createdAt' => $date,
                'createdBy' => $initiatedBy,
                'modifiedAt' => $date,
                'modifiedBy' => $initiatedBy,
            ); 
            $this->db->insert('requestMapping',$params); 
            $requestMappingId = $this->db->insert_id();
            $request = $this->db->select('*')->where('id', $requestMappingId)->get('requestMapping')->row_array();
            // $msg = "Request Mapped";
            // $response = $this->success_response($msg, $request);
        }

        
        $notificationReceiverData = array(
            'title' => "Offer Status",
            'body' => "Your donation offer / request has been matched. Step 1: Please go to app and click on account to complete the transation. Step 2: Once the transaction is completed /rejected, please click the status button and change it to completed / rejected.",
        ); 
         
        $notificationDonorData = array(
            'title' => "Offer Status",
            'body' => "Your donation offer / request has been matched. Step 1: Please go to app and click on account to complete the transation. Step 2: Once the transaction is completed /rejected, please click the status button and change it to completed / rejected",
        ); 

        $receiveData = $this->getUserByReceiveId($receiveId);
        // $sms = $this->sms($receiveData['mobile'], $notificationReceiverData['body']);
        $this->sendFCM($notificationReceiverData['title'], $notificationReceiverData['body'], $receiveData['deviceToken']);
        $donorData = $this->getUserByDonationId($donationId);
        // $sms = $this->sms($donorData['mobile'], $notificationDonorData['body']);
        $this->sendFCM($notificationDonorData['title'], $notificationDonorData['body'], $donorData['deviceToken']);
       
        // header('Content-Type: application/json');
        // echo json_encode($response); 
	}
	
	public function doner()
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $this->form_validation->set_rules('categoryId', 'Category', 'required');
           $this->form_validation->set_rules('subcategoryId', 'Sub Category', 'required');
           $this->form_validation->set_rules('description', 'Description', 'required');
           $this->form_validation->set_rules('region', 'Region', 'required');
           $this->form_validation->set_rules('deliveryType', 'Delivery Type', 'required');
           
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                $error[] = array(
                    'param' => array_keys($m)[0],
                    'msg' => $m[$error_key]
                );
                $msg = '';
                $response = $this->failure_response($msg, $error);
            }else{
                if($accesToken->userType == 'doner'){
                    if(!empty($_FILES["image"]['name']))
                    { 
                        $fileName = $this->upload_doc_single('uploads/doner', $_FILES["image"], 'image');
                    }else{
                        $fileName = "";
                    }
                    
                    $date = date('Y-m-d H:i:s', time());
                    $params = array(
                        "userId" => $accesToken->id,
                        "categoryId" => $_POST['categoryId'],
                        "subcategoryId" => $_POST['subcategoryId'],
                        "description" => $_POST['description'],
                        "image" => $fileName,
                        "region" => $_POST['region'],
                        "deliveryType" => $_POST['deliveryType'],
                        "address" => $_POST['address'],
                        "status" => "Pending",
        				'createdAt' => $date,
        				'createdBy' => $accesToken->id,
        				'modifiedAt' => $date,
        				'modifiedBy' => $accesToken->id,
                    );
                    $this->db->insert('donates',$params); 
                    $donateId = $this->db->insert_id();

                    $donate = $this->db->select('*')->where('id', $donateId)->get('donates')->row_array();
                    $msg = "Request added";
                    $response = $this->success_response($msg, $donate);
                }else{
                    $msg = "Only Doner can add the Donate request";
                    $response = $this->failure_response($msg, $msg);
                }     
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
	
    public function receive()
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $inputData = file_get_contents('php://input');
           $_POST = (array)json_decode($inputData);
           $this->form_validation->set_rules('categoryId', 'Category', 'required');
           $this->form_validation->set_rules('subcategoryId', 'Sub Category', 'required');
           $this->form_validation->set_rules('description', 'Description', 'required');
           $this->form_validation->set_rules('region', 'Region', 'required');
           $this->form_validation->set_rules('deliveryType', 'Delivery Type', 'required');
           
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                $error[] = array(
                    'param' => array_keys($m)[0],
                    'msg' => $m[$error_key]
                );
                $msg = '';
                $response = $this->failure_response($msg, $error);
            }else{
                if($accesToken->userType == 'receiver'){
                   $date = date('Y-m-d H:i:s', time());
                    $params = array(
                        "userId" => $accesToken->id,
                        "categoryId" => $_POST['categoryId'],
                        "subcategoryId" => $_POST['subcategoryId'],
                        "description" => $_POST['description'],
                        "region" => $_POST['region'],
                        "deliveryType" => $_POST['deliveryType'],
                        "address" => $_POST['address'],
                        "status" => "Pending",
        				'createdAt' => $date,
        				'createdBy' => $accesToken->id,
        				'modifiedAt' => $date,
        				'modifiedBy' => $accesToken->id,
                    );
                    $this->db->insert('receive',$params); 
                    $receiveId = $this->db->insert_id();
                    $receive = $this->db->select('*')->where('id', $receiveId)->get('receive')->row_array();
                    $msg = "Request added";
                    $response = $this->success_response($msg, $receive);
                }else{
                    $msg = "Only Receiver can add the Recieve request";
                    $response = $this->failure_response($msg, '');
                }     
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
       }
        header('Content-Type: application/json'); 
        echo json_encode($response);  
    }
    
    public function removeDonate($id)
    {
        $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           
            if($accesToken->userType == 'doner'){
               
                $date = date('Y-m-d H:i:s', time());
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "isActive" => 0,
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $id);
                $this->db->update('donates', $params);
                $msg = "Request deleted";
                $response = $this->success_response($msg, '');
            }else{
                $msg = "Only Doner can add the Donate request";
                $response = $this->failure_response($msg, '');
            }  
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json'); 
        echo json_encode($response);  
    }
    
    
    public function removeReceive($id)
    {
        $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           
            if($accesToken->userType == 'receiver'){
               
                $date = date('Y-m-d H:i:s', time());
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "isActive" => 0,
    				'createdAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $id);
                $this->db->update('receive', $params);
                $msg = "Request deleted";
                $response = $this->success_response($msg, '');
            }else{
                $msg = "Only Receiver can remove the Recieve request";
                $response = $this->failure_response($msg, '');
            }  
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, '');
       }
        header('Content-Type: application/json'); 
        echo json_encode($response);  
    }
    
    
	function upload_doc_single($dirname, $file = null, $fileTypeName= null){
        
            $parentdirname = $dirname;
            
            
            $year = date("Y");   
            $month = date("m"); 
            $date = date("d"); 
            $yeardir = $parentdirname.'/'.$year;   
            $monthdir = $yeardir."/".$month;
            $datedir = $monthdir.'/'.$date;
            
            // File management system
            if(file_exists($yeardir)){
                if(file_exists($monthdir)){
                    if(file_exists($datedir)==false){
                        mkdir($datedir,0777);
                    }
                }else{
                    mkdir($monthdir,0777);
                    if(file_exists($datedir)==false){
                        mkdir($datedir,0777);
                    }
                }
            }else{
                mkdir($yeardir,0777);
                if(file_exists($monthdir)){
                    if(file_exists($datedir)==false){
                        mkdir($datedir,0777);
                    }
                }else{
                    mkdir($monthdir,0777);
                    if(file_exists($datedir)==false){
                        mkdir($datedir,0777);
                    }
                }
            }
            
            $filedirname = $datedir;
            

            if(basename($file["name"])){
    		    $docname = $filedirname ."/" . basename($file["name"]);
    		    $doctFileType = pathinfo($docname, PATHINFO_EXTENSION);
    		    $docname = $filedirname ."/" . $fileTypeName. time() . "." . $doctFileType;
    		}else{
    		    $docname = "";
    		}
    		
            $uploadOk = 1;
    		if ($docname == $filedirname."/") {
                $msg = "All documents are empty";
                $uploadOk = 0;
            } // Check if file already exists
            else if (file_exists($docname)) {
                $msg = "Sorry, one or more of these files already exists.";
                $uploadOk = 0;
            } // Check file size
            else if ($file["size"] > 5000000) {
                $msg = "Sorry, one or more of the files is too large.";
                $uploadOk = 0;
            } // Check if $uploadOk is set to 0 by an error
            else if ($uploadOk == 0) {
                $msg = "Sorry, your file was not uploaded.";
        
                // if everything is ok, try to upload file
            } else {
                   
                $msg = "The file ";
                if (move_uploaded_file($file["tmp_name"], $docname)){
                    $msg .=  basename($file["name"]);
                } 
                    $msg .= " have been uploaded.";
                
            }
    		
    		
    		return $docname;
    }

     
    function sendFCM($title, $body, $userToken) {
        // FCM API Url
        $url = 'https://fcm.googleapis.com/fcm/send';
    
        // Put your Server Key here
        $apiKey = "AAAAVTrls7k:APA91bHvX2Q4OnazHrAoNi0CMdnxasYZUQJoYT1fOwlJD4WeqECUztbb-MGpKFbFCjdeEKUDlk3kMyvY9JP0NrQyypDvzhdvlcQ1HPR1fbCbH9uVKyEmW80VBf5DqPVLi1ew9sS1ulj6";
    
        // Compile headers in one variable
        $headers = array (
        'Authorization:key=' . $apiKey,
        'Content-Type:application/json'
        );
        
        $notificationData = array(
            'title' => $title,
            'body' => $body,
        ); 

        $apiBody = [
            'notification' => $notificationData,
            'time_to_live' => 600, // optional - In Seconds
            'to' => $userToken
        ];
    
        // Initialize curl with the prepared headers and body
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_POST, true);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, json_encode($apiBody));
    
        // Execute call and save result
        $result = curl_exec($ch);
        // print($result);
        // echo '<br>';
    }
    
	
	function sms($phone, $msg){
	    // Send an SMS using Twilio's REST API and PHP
        $sid = "AC574a4f90cd4b2b789ae4ace378f01ee8"; // Your Account SID from www.twilio.com/console
        $token = "049e9827320d453385c5a612d9a4caab"; // Your Auth Token from www.twilio.com/console
        
        $client = new Twilio\Rest\Client($sid, $token);
        $message = $client->messages->create(   
            '+65'.$phone, // Text this number
            [
                'from' => '+19126167384', // From a valid Twilio number
                'body' => $msg
            ]
        );
	}
}