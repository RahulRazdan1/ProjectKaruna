<?php
defined('BASEPATH') OR exit('No direct script access allowed'); 

class StaticData extends CI_Controller {
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
    
    
    public function appConfig($any)
    {
        // echo "<pre>";
        // print_r('Vikas');
        // die;
        $staticData['userType'] = [
            "doner",
            "receiver",
            "volunteer"
        ];
        $staticData['regions'] = [
            "Central Region",
            "East Region",
            "North Region",
            "North-East Region",
            "West Region"
        ];
        $staticData['deliveryTypes'] = [
            "Pickup By Receiver",
            "Drop off To Receiver",
            "Sent through Volounteer"
        ];
        $staticData['requestStatus'] = [
            "Pending",
            "Completed",
            "Matched"
        ];
        $staticData['categories'] = $this->db->select('id, name, parentCategory')->or_where(array('parentCategory'=> null, 'parentCategory'=> 0))->get('categories')->result_array();
        $staticData['sub_categories'] = $this->db->select('id, name, parentCategory')->or_where(array('parentCategory !='=> null, 'parentCategory !='=> 0))->get('categories')->result_array();
        $data = array(
            'regions' => $staticData['regions'],
            'deliveryTypes' => $staticData['deliveryTypes'],
            'requestStatus' => $staticData['requestStatus'],
            'categories' => $staticData['categories'],
            'sub_categories' => $staticData['sub_categories'],
        );
        $msg = "Initial config";
        $response = $this->success_response($msg, $data);  
        header('Content-Type: application/json');  
        echo json_encode($response);
    }
	
   	
}