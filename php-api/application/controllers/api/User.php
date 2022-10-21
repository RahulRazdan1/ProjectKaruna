<?php
defined('BASEPATH') OR exit('No direct script access allowed'); 
date_default_timezone_set('Asia/Singapore');
require APPPATH . '/vendor/autoload.php';
use Twilio\Rest\Client;
class User extends CI_Controller {
	public function __construct(){ 
		parent::__construct();
		$this->load->database();
		//$this->oakter = $this->load->database('oakter',TRUE);
		$this->load->helper(array('url','html','form'));
        $this->load->library('form_validation');
        // $this->load->library('email');
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
    
    public function success_response($msg, $data=null)
    {
        if(!empty($data)){
           $response = array(
              "success" => true,
              "msg" => $msg,
              "data" => $data
            ); 
        }else{
            $response = array(
              "success" => true,
              "msg" => $msg
            );
        }
        
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
	
    public function login($login_type)
    {
		if ($login_type == "password") {
            $inputData = file_get_contents('php://input');
            $_POST = (array)json_decode($inputData);
            $username = $_POST['username'];
            $password = $_POST['password'];
            $deviceType = $_POST['deviceType'];
            $deviceToken = $_POST['deviceToken'];
            $timeStamp = $_POST['timeStamp'];
            $this->form_validation->set_rules('username', 'User Name', 'required');
            $this->form_validation->set_rules('password', 'Password', 'required');
            // $this->form_validation->set_rules('deviceType', 'Device Type', 'required');
            // $this->form_validation->set_rules('deviceToken', 'Device Token', 'required');
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                // $error[] = array(
                //     'param' => array_keys($m)[0],
                //     'msg' => $m[$error_key]
                // );
                $error = $m[$error_key];
                $msg = 'Unauthorized';
                $response = $this->failure_response($msg, $error);
            }else{
                $sql = "SELECT * FROM `user` WHERE (`email` = '$username' AND `password` = '$password') OR (`mobile` = '$username' AND `password` = '$password')";
                $result = $this->db->query($sql)->row_array();
                unset($result['deviceType'], $result['deviceToken'], $result['password']);
                if(isset($result) && !empty($result)){
                    $paramsUp = array(
                        'deviceToken' => $deviceToken
                    );
                    $this->db->where('id', $result['id']);
                    $this->db->update('user', $paramsUp);
                    $accesToken = $this->token($result);
                    $result['_id'] = $result['id'];
                    $result['authToken'] = $accesToken;
                    $result['deviceType'] = $deviceType;
                    $result['userId'] = $result['id'];
                    $result['deviceToken'] = $deviceToken;
                    $msg = "Success";
                    $response = $this->success_response($msg, $result);
                } else {
                    $msg = "Wrong credentials";
                    $response = $this->failure_response('Unauthorized', $msg);
                }
            }
		} else if ($login_type == "otp") {
            $inputData = file_get_contents('php://input');
            $_POST = (array)json_decode($inputData);
            $username = $_POST['username'];
            
            $result = $this->db->select('*')->where(array('mobile' => $username))->get('user')->row_array();
            unset($result['deviceType'], $result['deviceToken'], $result['password']);
            if(isset($result) && !empty($result)){
                $mobile = '+65'.$result['mobile'];
                $otp = $this->otpGernate($result['id']);
                $msg = 'OTP to login to your account is '.$otp['otp'];
                $sms = $this->sms($mobile, $msg);
                $message = "Otp Sent. It is valid upto 10 minuits";
                
                $result1 = array(
                    "userId" => $otp['userId'],
                    "createdTime" => $otp['createdTime'],
                    "expireTime" => $otp['expireTime'],
                    "_id" => $otp['id']
                    );
                $response = $this->success_response($message, $result1);
            } else {
                $msg = "Invalid User";
                $response = $this->failure_response('Unauthorized', $msg);
            }
		} else {
		    $msg = "Login type is not valid";
                $response = $this->failure_response('Unauthorized', $msg);
		}
        header('Content-Type: application/json');
		echo json_encode($response);
    }
    
    public function signup()
    {
        $inputData = file_get_contents('php://input');
        $_POST = (array)json_decode($inputData);
        $this->form_validation->set_rules('firstName', 'First Name', 'required');
        $this->form_validation->set_rules('lastName', 'Last Name', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required|is_unique[user.email]');
        $this->form_validation->set_rules('mobile', 'Mobile', 'required|numeric|is_unique[user.mobile]');
        $this->form_validation->set_rules('password', 'password', 'required');
        $this->form_validation->set_rules('userType', 'UserType', 'required');
        $this->form_validation->set_rules('region', 'Region', 'required');
        $this->form_validation->set_rules('address', 'Address', 'required');
        // $this->form_validation->set_rules('deviceType', 'Device Type', 'required');
        // $this->form_validation->set_rules('deviceToken', 'Device Token', 'required');
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
            if($_POST['password'] == $_POST['confirmPassword']){
                $params = array(
                    "firstName" => $_POST['firstName'],
                    "lastName" => $_POST['lastName'],
                    "email" => $_POST['email'],
                    "mobile" => $_POST['mobile'],
                    "password" => $_POST['password'],
                    "userType" => $_POST['userType'],
                    "region" => $_POST['region'],
                    "address" => $_POST['address'],
                    "deviceType" => $_POST['deviceType'],
                    "deviceToken" => $_POST['deviceToken']
                    );
                $this->db->insert('user',$params); 
                $userId = $this->db->insert_id();
                $request = $this->db->select('*')->where('id', $userId)->get('user')->row_array();
                $request['_id'] = $request['id'];
                $request['__v'] = 1;
                $request['isActive'] = true;
                $msg = "User Saved Successfully";
                $response = $this->success_response($msg, $request);
            }else{
               $msg = "Password confirmation does not match password";
                $response = $this->failure_response($msg, 'ERROR'); 
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    public function forgetPassword()
    {
        $inputData = file_get_contents('php://input');
        $_POST = (array)json_decode($inputData);
        $this->form_validation->set_rules('username', 'Username', 'required');
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
            $result = $this->db->select('*')->where(array('mobile' => $_POST['username']))->get('user')->row_array();
            if(!empty($result)){
                $mobile = '+65'.$result['mobile'];
                $otp = $this->otpGernate($result['id']);
                $msg = 'OTP to login to your account is '.$otp['otp'];
                $sms = $this->sms($mobile, $msg);
                $message = "Otp Sent. It is valid upto 10 minuits";
                $result1 = array(
                    "userId" => $otp['userId'],
                    "createdTime" => $otp['createdTime'],
                    "expireTime" => $otp['expireTime'],
                    "_id" => $otp['id']
                    );
                $response = $this->success_response($message, $result1);
            }else{
                $msg = "Invalid User";
                $response = $this->failure_response('Unauthorized', $msg);
            }
            header('Content-Type: application/json');
            echo json_encode($response);  
        }
    }
    
    function otpGernate($userId){
        $currentTime = date('Y-m-d H:i:s', time());
        $expireTime = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $otp = rand(100000, 999999);
        $otpParams = array(
            'userId' =>$userId,
            'otp' => $otp,
            'createdTime' => $currentTime,
            'expireTime' => $expireTime
            );
        $this->db->insert('otp',$otpParams); 
        $otpId = $this->db->insert_id();
        $request = $this->db->select('*')->where('id', $otpId)->get('otp')->row_array();
        return $request;
    }
    function sendSms($mobile, $msg){
        // Pending
    }
    public function resendOtp()
    {
        $inputData = file_get_contents('php://input');
        $_POST = (array)json_decode($inputData);
        $otpData = $this->db->select('*')->where(array('id' => $_POST['id']))->get('otp')->row_array();
        $result = $this->db->select('*')->where(array('id' => $otpData['userId']))->get('user')->row_array();
        
        $mobile = '+65'.$result['mobile'];
        $otp = $this->otpGernate($result['id']);
        $msg = 'OTP to login to your account is '.$otp['otp'];
        $sms = $this->sms($mobile, $msg);
        $message = "Otp Sent. It is valid upto 10 minuits";
        
        $result1 = array(
            "userId" => $otp['userId'],
            "createdTime" => $otp['createdTime'],
            "expireTime" => $otp['expireTime'],
            "_id" => $otp['id']
            );
        $response = $this->success_response($message, $result1);
        header('Content-Type: application/json');
        echo json_encode($response);  
    }
    
    public function validateOtp()
    {
        $inputData = file_get_contents('php://input');
        $_POST = (array)json_decode($inputData);
        
        $this->form_validation->set_rules('id', 'Id', 'required');
        $this->form_validation->set_rules('otp', 'OTP', 'required');
        
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
            $otpData = $this->db->select('*')->where(array('id' => $_POST['id']))->order_by("id", "desc")->get('otp')->row_array();
            $currentTime = date('Y-m-d H:i:s', time());  
            if(strtotime($currentTime) < strtotime($otpData['expireTime'])){
                if($otpData['otp'] == $_POST['otp']){
                    $result = $this->db->select('*')->where(array('id' => $otpData['userId']))->get('user')->row_array();
                    
                // $paramsUp = array(
                //     'deviceToken' => $deviceToken
                // );

                // $this->db->where('id', $result['id']);
                // $this->db->update('user', $paramsUp);

                    unset($result['password']);
                    $accesToken = $this->token($result);
                    $result['_id'] = $result['id'];
                    $result['authToken'] = $accesToken;
                    $msg = "User Logged in Successfully";
                    $response = $this->success_response($msg, $result);
                }else{
                    $msg = "Wrong OTP";
                    $response = $this->failure_response($msg, $msg); 
                }
            }else{
                $msg = "OTP expired";
                $response = $this->failure_response($msg, $msg); 
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);  
    }
    
    public function changePassword()
    {
        $inputData = file_get_contents('php://input');
        $_POST = (array)json_decode($inputData);
        
        $this->form_validation->set_rules('id', 'Id', 'required');
        $this->form_validation->set_rules('otp', 'OTP', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required');
        
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
            $otpData = $this->db->select('*')->where(array('id' => $_POST['id']))->get('otp')->row_array();
            $currentTime = date('Y-m-d H:i:s', time());  
            if(strtotime($currentTime) < strtotime($otpData['expireTime'])){
                if($otpData['otp'] == $_POST['otp']){
                    
                    $params = array(
                        'password' => $_POST['password']
                        );
                    $this->db->where('id', $otpData['userId']);
                    $this->db->update('user', $params);
                    $msg = "Password Updated Successfully";
                    $response = $this->success_response($msg, ''); 
                    
                }else{
                    $msg = "Wrong OTP";
                    $response = $this->failure_response($msg, $msg); 
                }
            }else{
                $msg = "OTP expired";
                $response = $this->failure_response($msg, $msg); 
            }  
        }
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function getProfile($id)
    {
        $result = $this->db->select('*')->where(array('id' => $id))->get('user')->row_array();
        unset($result['deviceType'], $result['deviceToken'], $result['password']);
        $result['_id'] = $result['id'];
        $msg = "User data found";
        $response = $this->success_response($msg, $result);
        
        header('Content-Type: application/json');
        echo json_encode($response); 
    }

    public function updateToken($id)
    {
        $Authorization = $this->input->get_request_header("Authorization");
        if(!empty($Authorization)){
            $accesToken = $this->get_token($Authorization);
            $inputData = file_get_contents('php://input');
            $_POST = (array)json_decode($inputData);
            $deviceType = $_POST['deviceType'];
            $deviceToken = $_POST['deviceToken'];
            $this->form_validation->set_rules('deviceType', 'Device Type', 'required');
            $this->form_validation->set_rules('deviceToken', 'Device Token', 'required');
            if ($this->form_validation->run() == FALSE) {
                $m = $this->form_validation->error_array();
                $error_key = array_keys($m)[0];
                // $error[] = array(
                //     'param' => array_keys($m)[0],
                //     'msg' => $m[$error_key]
                // );
                $error = $m[$error_key];
                $msg = 'Unauthorized';
                $response = $this->failure_response($msg, $error);
            }else{
                $result = $this->db->select('*')->where('id', $id)->get('user')->row_array();
                unset($result['deviceType'], $result['deviceToken'], $result['password']);
                if(isset($result) && !empty($result)){
                    $paramsUp = array(
                        'deviceToken' => $deviceToken
                    );
                    $this->db->where('id', $result['id']);
                    $this->db->update('user', $paramsUp);
                    $msg = "User data updated successfully";
                    $response = $this->success_response($msg);
                }else {
                    $msg = 'Unsuccessful';
                    $error = 'User data update failed';
                    $response = $this->failure_response($msg, $error);
                }
            }
        }else{
            $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
        }
        header('Content-Type: application/json');
        echo json_encode($response);  
    }

    
    public function editProfile($id)
    {
        $Authorization = $this->input->get_request_header("Authorization");
        if(!empty($Authorization)){
            $accesToken = $this->get_token($Authorization);
            $inputData = file_get_contents('php://input');
            $_POST = (array)json_decode($inputData);
            
            $this->form_validation->set_rules('firstName', 'First Name', 'required');
            $this->form_validation->set_rules('lastName', 'Last Name', 'required');
            $this->form_validation->set_rules('email', 'Email', 'required');
            $this->form_validation->set_rules('mobile', 'Mobile', 'required|numeric');
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
                $date = date('Y-m-d H:i:s', time());
                    $params = array(
                        "firstName" => $_POST['firstName'],
                        "lastName" => $_POST['lastName'],
                        "email" => $_POST['email'],
                        "mobile" => $_POST['mobile'],
                        "modifiedAt" => $date,
                        "modifiedBy" => $accesToken->id
                    );
            $this->db->where('id', $id);
            $this->db->update('user', $params);
            $msg = "User data updated successfully";
            $response = $this->success_response($msg);
                
            }
        }else{
            $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
        }
        header('Content-Type: application/json');
        echo json_encode($response);  
    }
	
	function deleteUser($id){
        $user = $this -> db -> where('id', $id) ->  get('user') -> row_array();
        if($user['userType'] == 'admin') {
            $msg = "Admin user cannot be deleted.";
            $response = $this->failure_response($msg, $msg);
        } else {
            $result = $this -> db -> where('id', $id) -> delete('user');
            if($result) {
                $msg = "User data deleted successfully";
                $response = $this->success_response($msg);
            } else {
                $msg = "User data not found";
                $response = $this->failure_response($msg, $msg);
            }
        }
        header('Content-Type: application/json');
        echo json_encode($response);  
	}
	
	function sms($phone, $msg){
	    // Send an SMS using Twilio's REST API and PHP
        $sid = "AC574a4f90cd4b2b789ae4ace378f01ee8"; // Your Account SID from www.twilio.com/console
        $token = "049e9827320d453385c5a612d9a4caab"; // Your Auth Token from www.twilio.com/console
        
        $client = new Twilio\Rest\Client($sid, $token);
        $message = $client->messages->create(   
          $phone, // Text this number
          [
            'from' => '+19126167384', // From a valid Twilio number
            'body' => $msg
          ]
        );
	}
    
    	
    function sendEmail() {
                
        $to = "girnvikas987@gmail.com";
        $subject = "My subject";
        $txt = "Hello world!";
        $headers = "From: admin@projectkaruna.net";
        
        mail($to,$subject,$txt,$headers);
        
        // // $to, $subject, $message
        // $this->load->config('email');
        // $this->load->library('email');
        
        // $to = 'girnvikas987@gmail.com';
        // $subject = 'Testing';
        // $message = 'Hello Word';
        // $from = $this->config->item('smtp_user');

        // $to1 = $to;
        // $this->email->set_newline("\r\n");
        // $this->email->from($from);
        // $this->email->to($to1);
        // $this->email->subject($subject); 
        // $this->email->message($message);

        // if ($this->email->send()) {
        //     echo 'Your Email has successfully been sent Vikas.';
        //     // die;
        // } else {
        //     show_error($this->email->print_debugger());
        // }
    }	
}