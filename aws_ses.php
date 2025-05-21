<?php

namespace aw2\aws_ses;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;


/**
	Desc : This will return the file content
	Input: 
		file_path   : <string>
		config 		: <array>
		keyname 	: <string>	
		regex_check	: <array>
**/

\aw2_library::add_service('aws_ses.fetch_ses_email','Get Email',['namespace'=>__NAMESPACE__]);
function fetch_ses_email($atts,$content=null,$shortcode){
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'file_path'=>'',
		'keyname'=>'',
		'regex_check'=>''
		), $atts) );	
	
	$atts['bucket']= isset($config['bucket']) ? $config['bucket'] : '';
	
	//check required fields
	$input_res=check_required_input($atts);
	if(isset($input_res['status']) && $input_res['status']==='error'){
		return $input_res;
	}
	
	//connect to s3 
	$s3=connect_s3($config);
	if(is_array($s3)){
		if(isset($s3['status']) && $s3['status'] ==='error' ){
		return $s3;
		}
	}	
	
	try {		
		// Get the object.
		$result_email = $s3->getObject([
			'Bucket' => $config['bucket'],
			'Key'    => $keyname
		]);
		
		$result=array('status'=>'success','message'=>'email found','data'=>$result_email);		
		
	} catch (S3Exception $e) {
    
		$result=array('status'=>'error','message'=>$e->getMessage() . PHP_EOL);
		return $result;
	}
	
	$result=$result['data'];
	file_put_contents($file_path,$result['Body']);
	
	//parse email
	$parser = new \PhpMimeMailParser\Parser();
	
	
	// 2. Specify the raw mime mail text (string)
	$parser->setText(file_get_contents($file_path));	
	
	$data['to'] = $parser->getHeader('to');
	
	$data['cc'] = $parser->getHeader('cc');
	
	$data['bcc'] = $parser->getHeader('bcc');

	$data['from'] = $parser->getHeader('from');
	
	$data['subject'] = $parser->getHeader('subject');
	
	$data['text'] = $parser->getMessageBody('text');
	
	$check_val=$data['to'].", ".$data['cc'].", ".$data['bcc'];
	
	$match=array();
	$type="";
	foreach($regex_check as $key=> $val){
		if(empty($match)){
			preg_match("/$val/",$check_val,$match);
			$type=$key;
		}
	}
	if(isset($match[0])){
		$data['object_id']= strstr($match[0], '@', true);
	}
	$data['attachment']= "no";
	
	$attachments = $parser->getAttachments();
	
	if(!empty($attachments)){
			$data['attachment']= "yes";
	}	
	if(!empty($match)){
		$data['type']= $type;
	}
	
	
	$res=array('status'=>'success','message'=>'email found','data'=>$data);
	return $res;
	
}


/*
	This will help to save email attachment
	input :
		source 		: <string>
		destination : <string>
	
	return 
	<array>
*/

\aw2_library::add_service('aws_ses.save_email_attachment','Save Email Attachment',['namespace'=>__NAMESPACE__]);

function save_email_attachment($atts,$content=null,$shortcode){	 
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'source'=>'',
		'destination'=>''
		), $atts) );	
	
	//check required fields
	$input_res=check_required_input($atts);
	if($input_res['status']==='error'){
		return $input_res;
	}
	
	$parser = new \PhpMimeMailParser\Parser();	
	
	$res=array('status'=>'success','message'=>'Attachment saved.');
	
	// 2. Specify the raw mime mail text (string) 
	
	$parser->setText(file_get_contents($source));	
	
	if(!is_dir($destination)){		
		mkdir($destination,0755,true);
	}  
	
	// Save all attachments with the strategy ATTACHMENT_DUPLICATE_SUFFIX (default)
	$results = $parser->saveAttachments($destination);	
	if(!empty($results)){
		foreach ($results as $attachment) {
			if (!file_exists($attachment)) {
				$res=array('status'=>'error','message'=>'file not found');
				return $res;
			}
		}
	}
	
	return $res;
}



/*
	To get the received emails
	
	input : 
		config : <array>
			
	return 
		<array>
	
*/


\aw2_library::add_service('aws_ses.fetch_all_emails','fetch all emails from bucket',['namespace'=>__NAMESPACE__]);

function fetch_all_emails($atts,$content=null,$shortcode){
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>''		
		), $atts) );	
	
	$result=array();
		
	//check required fields
	$input_res=check_required_input($atts);
	if(isset($input_res['status']) && $input_res['status']==='error'){
		return $input_res;
	}
	
	//connect to s3 
	$s3=connect_s3($config);
	if(is_array($s3)){
		if(isset($s3['status']) && $s3['status'] ==='error' ){
		return $s3;
		}
	}
	
	try {
		// Get  ONLY up to 1000 of your objects.
		$result_email = $s3->listObjects([
			'Bucket' => $config['bucket']
		]);
		
	if(!empty($result_email) && isset($result_email['Contents'])){
		foreach ($result_email['Contents']  as $object) {
			$result[]=$object['Key'];
		}
	}
		
	$result=array('status'=>'success','message'=>'email found','data'=>$result);		
		
	} catch (S3Exception $e) {
    
		$result=array('status'=>'error','message'=>$e->getMessage() . PHP_EOL);
	}	
	
	return $result;
	 
	
}



function connect_s3($config){	
	
	//**required parameters to connect**//
	$connect_array['IAM_KEY']=isset($config['IAM_KEY']) ? $config['IAM_KEY'] : '';
	$connect_array['IAM_SECRET']=isset($config['IAM_SECRET']) ? $config['IAM_SECRET'] : '';
	$connect_array['aws_version']=isset($config['aws_version']) ? $config['aws_version'] : '';
	$connect_array['aws_region']=isset($config['aws_region']) ? $config['aws_region'] : '';
	
	$res=check_required_input($connect_array);
	if(isset($res['status']) && $res['status']==='error'){
		return $res;
	}
	
	$s3 = new S3Client([
        'credentials' => [
            'key'    => $config['IAM_KEY'],
            'secret' => $config['IAM_SECRET'],
        ],
        'version' => $config['aws_version'],
        'region'  => $config['aws_region'],
        'suppress_php_deprecation_warning' => true
    ]);
    return $s3;
}

/*
	* This will check all the required parameters
	input : <array>
	return :
		<array>
	
*/
function check_required_input($input){
	
	$required_field=array();
	foreach($input as $key => $val){
		if(empty($val)){
			$required_field[]="$key parameter is required";
		}
	}
	
	if(!empty($required_field)){
		return array('status'=>'error','message'=>'missing required parameters ','errors'=>$required_field);
	}
	return array('status'=>'success','message'=>'validation pass');
}

