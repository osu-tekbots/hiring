<?php
/**
 * This script handles the process of saving an uploaded file on the web server. It does NOT make any changes to the database.
 */
	$Data = array();
	$Data["successful"] = 0;
	$Data["string"] = 'Please select a file';
	if ($_POST['action'] == 'uploadCandidateFile'){
		/**
		 * This section of the script handles the process for candidate files. This includes:
		 * 		* Verifying the file is the appropriate type
		 * 		* Verifying the file is small enough
		 * 		* Storing the file in /uploads/candidate
		 */
		if(isset($_FILES['file'])){
			$errors= array();
			$file_name = $_FILES['file']['name'];
			$file_name_no_ext = explode(".",$file_name)[0];
			$file_size =$_FILES['file']['size'];
			$file_tmp =$_FILES['file']['tmp_name'];
			$file_type=$_FILES['file']['type'];
			$file_ext=strtolower(end(explode('.',$_FILES['file']['name'])));
			$Data["UploadName"] = $file_name_no_ext.date('Y-m-d-H-i-s').".".$file_ext;
			$extentions= array("pdf", "PDF", "jpg", "JPG", "PNG", "png", "heic", "HEIC", "jpeg", "JPEG", "docx", "DOCX");
			if(in_array($file_ext,$extentions)=== false){
				$errors[]="extension not allowed, please choose either an image, pdf or docx file.";
				$Data["successful"] = 0;
				$Data["string"] = 'extension not allowed, please choose either an image, pdf or docx file.';
				echo json_encode($Data);
				exit();
			}
			if($file_size > (5 * 2097152)){
				$Data["string"] ='File size must be under 10 MB';
			}
			if(empty($errors)==true){
				$dbfilename = $Data["UploadName"];
				move_uploaded_file($file_tmp,"../uploads/candidate/".$dbfilename);
				$Data["successful"] = 1;
				$Data["string"] = $dbfilename;
				$Data["path"] = $dbfilename;
				
			}else{
				print_r($errors);
				$Data["successful"] = 0;
				$Data["string"] = 'Errors occured';
				echo json_encode($Data);
				exit();
			}
		}
	}
	elseif ($_POST['action'] == 'uploadFeedbackFile'){
		/**
		 * This section of the script handles the process for feedback files. This includes:
		 * 		* Verifying the file is the appropriate type
		 * 		* Verifying the file is small enough
		 * 		* Storing the file in /uploads/feedback
		 */
		if(isset($_FILES['file'])){
			$errors= array();
			$file_name = $_FILES['file']['name'];
			$file_name_no_ext = explode(".",$file_name)[0];
			$file_size =$_FILES['file']['size'];
			$file_tmp =$_FILES['file']['tmp_name'];
			$file_type=$_FILES['file']['type'];
			$file_ext=strtolower(end(explode('.',$_FILES['file']['name'])));
			$Data["UploadName"] = $file_name_no_ext.date('Y-m-d-H-i-s').".".$file_ext;
			$extentions= array("pdf", "PDF", "jpg", "JPG", "PNG", "png", "heic", "HEIC", "jpeg", "JPEG", "docx", "DOCX");
			if(in_array($file_ext,$extentions)=== false){
				$errors[]="extension not allowed, please choose either an image, pdf or docx file.";
				$Data["successful"] = 0;
				$Data["string"] = 'extension not allowed, please choose either an image, pdf or docx file.';
				echo json_encode($Data);
				exit();
			}
			if($file_size > (5 * 2097152)){
				$Data["string"] ='File size must be under 10 MB';
			}
			if(empty($errors)==true){
				$dbfilename = $Data["UploadName"];
				move_uploaded_file($file_tmp,"../uploads/feedback/".$dbfilename);
				$Data["successful"] = 1;
				$Data["string"] = $dbfilename;
				$Data["path"] = $dbfilename;
				
			}else{
				print_r($errors);
				$Data["successful"] = 0;
				$Data["string"] = 'Errors occured';
				echo json_encode($Data);
				exit();
			}
		}
	}
	elseif ($_POST['action'] == 'uploadInterviewQuestionsFile'){
		/**
		 * This section of the script handles the process for interview questions files. This includes:
		 * 		* Verifying the file is the appropriate type
		 * 		* Verifying the file is small enough
		 * 		* Storing the file in /uploads/interviewQuestions
		 */
		if(isset($_FILES['file'])){
			$errors= array();
			$file_name = $_FILES['file']['name'];
			$file_name_no_ext = explode(".",$file_name)[0];
			$file_size =$_FILES['file']['size'];
			$file_tmp =$_FILES['file']['tmp_name'];
			$file_type=$_FILES['file']['type'];
			$file_ext=strtolower(end(explode('.',$_FILES['file']['name'])));
			$Data["UploadName"] = $file_name_no_ext.date('Y-m-d-H-i-s').".".$file_ext;
			$extentions= array("pdf", "PDF", "jpg", "JPG", "PNG", "png", "heic", "HEIC", "jpeg", "JPEG", "docx", "DOCX");
			if(in_array($file_ext,$extentions)=== false){
				$errors[]="extension not allowed, please choose either an image, pdf or docx file.";
				$Data["successful"] = 0;
				$Data["string"] = 'extension not allowed, please choose either an image, pdf or docx file.';
				echo json_encode($Data);
				exit();
			}
			if($file_size > (5 * 2097152)){
				$Data["string"] ='File size must be under 10 MB';
			}
			if(empty($errors)==true){
				$dbfilename = $Data["UploadName"];
				move_uploaded_file($file_tmp,"../uploads/interviewQuestions/".$dbfilename);
				$Data["successful"] = 1;
				$Data["string"] = $dbfilename;
				$Data["path"] = $dbfilename;
				
			}else{
				print_r($errors);
				$Data["successful"] = 0;
				$Data["string"] = 'Errors occured';
				echo json_encode($Data);
				exit();
			}
		}
	}
	if($_POST['action'] == 'isfile')
	{
		$Data["successful"] = 3;
		$Data["string"] = '<font color="red">❌ </font> Empty';
		
	}
	echo json_encode($Data);

?>