<?php 
defined('BASEPATH') OR exit('No direct script access allowed'); 
class Category extends CI_Controller {  
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
    
    
    public function addCategory()
    {
        $Authorization = $this->input->get_request_header("Authorization");
        if(!empty($Authorization)){
            $accesToken = $this->get_token($Authorization);
            $this->form_validation->set_rules('name', 'Name', 'required');
            if(empty($_FILES["categoryImage"]['name']))
            { 
                $this->form_validation->set_rules('categoryImage', 'Category Image', 'required');
            }
            
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
                if(!empty($_FILES["categoryImage"]['name']))
                { 
                    $fileName = $this->upload_doc_single('uploads/category', $_FILES["categoryImage"], 'image');
                }else{
                    $fileName = "";
                }
                
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "name" => $_POST['name'],
                    "categoryImage" => $fileName,
    				'cretedAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->insert('categories',$params); 
                $categoryId = $this->db->insert_id();
                $category = $this->db->select('*')->where('id', $categoryId)->get('categories')->row_array();
                $category['_id'] = $category['id'];
                $category['__v'] = 1;
                $category['isActive'] = true;
                unset($category['parentCategory']);
                $msg = "Category Created";
                $response = $this->success_response($msg, $category);
                //   echo "<pre>";
                //   print_r($params);
                //   die;
                   
            }
        }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
        }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    public function getCategory()
    {
        $result = $this->db->select('*')->where('parentCategory', null)->or_where('parentCategory', 0)->get('categories')->result_array();
        
        if(isset($result) && !empty($result)){
            $res = array();
            $i = 0;
            foreach($result as $r){
                $r['_id'] = $r['id'];
                $r['__v'] = 1;
                $r['isActive'] = true;
                unset($r['parentCategory']);
                $res[$i] = $r;
                $i++;
            }
            
            $msg = "Success";
            $response = $this->success_response($msg, $res);
        } else {
            $msg = "Category Not Found";
            $response = $this->failure_response($msg, $result);
        }
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function getSingleCategory($id)
    {
        $result = $this->db->select('*')->where('id', $id)->get('categories')->row_array();
        
        if(isset($result) && !empty($result)){
            $result['_id'] = $result['id'];
            $result['__v'] = 1;
            $result['isActive'] = true;
            unset($result['parentCategory']);
            $msg = "Success";
            $response = $this->success_response($msg, $result);
        } else {
            $msg = "Category Not Found";
            $response = $this->failure_response($msg, $msg);
        }
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function updateCatergory($id)
    {
        $category = $this->db->select('*')->where('id', $id)->get('categories')->row_array();
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $this->form_validation->set_rules('name', 'Name', 'required');
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
                if(!empty($_FILES["categoryImage"]['name']))
                { 
                    $fileName = $this->upload_doc_single('uploads/category', $_FILES["categoryImage"], 'image');
                }else{
                    $fileName = $category['categoryImage'];
                }
                
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "name" => $_POST['name'],
                    "categoryImage" => $fileName,
    				'cretedAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $id);
                $this->db->update('categories', $params);
                $category = $this->db->select('*')->where('id', $id)->get('categories')->row_array();
                $category['_id'] = $category['id'];
                $category['__v'] = 1;
                $category['isActive'] = true;
                unset($category['parentCategory']);
                $msg = "Category Updated";
                $response = $this->success_response($msg, $category);
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
       }
        header('Content-Type: application/json');
        echo json_encode($response); 
    }
    
    // Subcategory  
    
    public function addSubcategory()
    {
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $this->form_validation->set_rules('name', 'Name', 'required');
           $this->form_validation->set_rules('parentCategory', 'Category', 'required');
           if(empty($_FILES["categoryImage"]['name']))
            { 
                $this->form_validation->set_rules('categoryImage', 'Category Image', 'required');
            }
            
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
                if(!empty($_FILES["categoryImage"]['name']))
                { 
                    $fileName = $this->upload_doc_single('uploads/category', $_FILES["categoryImage"], 'image');
                }else{
                    $fileName = "";
                }
                
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "name" => $_POST['name'],
                    "parentCategory" => $_POST['parentCategory'],
                    "categoryImage" => $fileName,
    				'cretedAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->insert('categories',$params); 
                $categoryId = $this->db->insert_id();
                $category = $this->db->select('*')->where('id', $categoryId)->get('categories')->row_array();
                $category['_id'] = $category['id'];
                $category['__v'] = 1;
                $category['isActive'] = true;
                unset($category['parentCategory']);
                $msg = "Sub Category Created";
                $response = $this->success_response($msg, $category);
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
       }
        header('Content-Type: application/json');
        echo json_encode($response);  
    }
        
    public function getSubCategory($categoryId)
    {
        
        $result = $this->db->select('*')->where('parentCategory', $categoryId)->get('categories')->result_array();
        
        if(isset($result) && !empty($result)){
            $res = array();
            $i = 0;
            foreach($result as $r){
                $r['_id'] = $r['id'];
                $r['__v'] = 1;
                $r['categoryName'] = $r['name'];
                $r['parentCategory'] = $r['parentCategory'];
                $r['image'] = $r['categoryImage'];
                $r['isActive'] = true;
                unset($r['categoryImage'], $r['id']);
                $res[$i] = $r;
                $i++;
            }
            $msg = "Success";
            $response = $this->success_response($msg, $res);
        } else {
            $msg = "Category Not Found";
            $response = $this->failure_response($msg, $result);
        }
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
    public function updateSubcategory($id)
    {
        $category = $this->db->select('*')->where('id', $id)->get('categories')->row_array();
        // echo "<pre>";
        // print_r($category);
        // die;
       $Authorization = $this->input->get_request_header("Authorization");
       if(!empty($Authorization)){
           $accesToken = $this->get_token($Authorization);
           $this->form_validation->set_rules('name', 'Name', 'required');
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
                if(!empty($_FILES["categoryImage"]['name']))
                { 
                    $fileName = $this->upload_doc_single('uploads/category', $_FILES["categoryImage"], 'image');
                }else{
                    $fileName = $category['categoryImage'];
                }
                
                $date = date('Y-m-d H:i:s', time());
                $params = array(
                    "name" => $_POST['name'],
                    "categoryImage" => $fileName,
    				'cretedAt' => $date,
    				'createdBy' => $accesToken->id,
    				'modifiedAt' => $date,
    				'modifiedBy' => $accesToken->id,
                );
                $this->db->where('id', $id);
                $this->db->update('categories', $params);
                $category = $this->db->select('*')->where('id', $id)->get('categories')->row_array();
                $category['_id'] = $category['id'];
                $category['__v'] = 1;
                $category['isActive'] = true;
                unset($category['parentCategory']);
                $msg = "Category Updated";
                $response = $this->success_response($msg, $category);
            }
       }else{
           $msg = "JWT error";
            $response = $this->failure_response($msg, $msg);
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
}