<?php

class Auth extends CI_Controller
{
 function __construct()
    {
        parent::__construct();
        
        $this->load->model('User_model'); 
    } 
	public function logout(){
		unset($_SESSION);
    session_destroy();
   $this->session->set_flashdata('success','You have been successfully logged out! ');
		redirect('auth/index','refresh');
	}



	public function index() {
    $this->load->library('form_validation');
    $this->form_validation->set_rules('email', 'Username', 'required');
    $this->form_validation->set_rules('password', 'Password', 'required');
    if ($this->form_validation->run() == TRUE)
    {
       $username = $_POST['email'];
       $password = ($_POST['password']);
       $this->db->select('*');
       $this->db->from('user');
       $this->db->where(array('email' => $username,'password' => $password));
       $query = $this->db->get();
       $user = $query->row();

       if(!empty($user)){

            $this->session->set_flashdata('success','You Are Logged In');
            $_SESSION['user_logged'] = TRUE;
            $_SESSION['email'] = $user->email;
            $_SESSION['usertype'] = $user->user_type;
            $_SESSION['userid'] = $user->id;
            $_SESSION['message'] = "You are logged in.";
            $_SESSION['first_name'] = $user->first_name;
            $_SESSION['last_name'] = $user->last_name;
       	
      		
      		// if($user->usertype == 1){
      		    redirect('dashboard/index');
      		// }
      		// else{
      		//     redirect('website/index');
      		// }
       		
       } else {
       		$this->session->set_flashdata('error','Invaild Username And Password! Please Try Again');
       		redirect('auth/index');
       }
    }

    	    $data['_view'] = 'login';
            $this->load->view('layouts/loginMain',$data);
	}

private function get_data($url)
    {   
        $member_data = array(
            'username' => email,
            'password' => password
        );
        //create a new cURL resource
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $member_data);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}