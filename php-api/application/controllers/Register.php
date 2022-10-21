<?php

class Register extends CI_Controller
{
 function __construct()
    {
        parent::__construct();
        
        $this->load->model('User_model');
    } 
    function index(){
        $this->load->library('form_validation');    
		$this->form_validation->set_rules('firstName','First Name','required');
		$this->form_validation->set_rules('lastName','Last Name','required');
		$this->form_validation->set_rules('email','Email','required|is_unique[user.email]');
		$this->form_validation->set_rules('mobile','Mobile','required');
		$this->form_validation->set_rules('password','Password','required');
		if($this->form_validation->run())     
        {
            date_default_timezone_set('Asia/Kolkata');
            $date = date('d-m-Y'); 
            $pwd = md5(12345);
            $params = array(
                'firstName' => $_POST['firstName'],
                'lastName' => $_POST['lastName'],
                'email' => $_POST['email'],
                'password' => $pwd,
                'mobile' => $_POST['mobile'],
                'createdAt' => $date,
                'createdBy' => $_POST['firstName'],
                'modifiedAt' => $date,
                'modifiedBy' => $_POST['firstName'],
                );
        
            $userId = $this->User_model->add_user($params);
            
            $this->session->set_flashdata('alert_msg','<div class="alert alert-primary mb-2" role="alert">Registration  Successfully Done!</div>');
            redirect('auth/index');
            
            
        }else{
    	    $data['_view'] = 'app/register';
            $this->load->view('layouts/loginMain',$data);
        }
    }
}
