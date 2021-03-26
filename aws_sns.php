<?php

namespace aw2\aws_sns;

use Aws\Sns\SnsClient; 
use Aws\Resultdata; 
use Aws\Exception\AwsException;

/*
	* This will create https subscription for SNS
	* input 
		config 		: <array>
		endpoint	: <sting>
		topic		: <string>
	*
*/

\aw2_library::add_service('aws_sns.create_https_subscription','This will create subscription for sns',['namespace'=>__NAMESPACE__]);

function create_https_subscription($atts,$content=null,$shortcode){	

	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'endpoint'=>'',
		'topic'=>''
		), $atts) );
	
	$snsClient = snsConnect($config);	
	$protocol = 'https';
	try {
		$result = $snsClient->subscribe([
			'Protocol' => $protocol,
			'Endpoint' => $endpoint,
			'ReturnSubscriptionArn' => true,
			'TopicArn' => $topic,
		]);
		
		return array('status'=>'success','message'=>'Subscription created successfully');	
	
	} catch (AwsException $e) {
		// output error message if fails
		return array('status'=>'error','message'=>$e->getMessage());
		
	} 
}

/*
	* This will create sqs subscription 
	* input 
		config 		: <array>
		endpoint	: <sting>
		topic		: <string>
	*
*/

\aw2_library::add_service('aws_sns.create_sqs_subscription','This will create subscription for sns',['namespace'=>__NAMESPACE__]);

function create_sqs_subscription($atts,$content=null,$shortcode){
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'endpoint'=>'',
		'topic'=>''
		), $atts) );
	
	$snsClient = snsConnect($config);	
	$protocol = 'sqs';
	try {
		$result = $snsClient->subscribe([
			'Protocol' => $protocol,
			'Endpoint' => $endpoint,
			'ReturnSubscriptionArn' => true,
			'TopicArn' => $topic,
		]);
		return array('status'=>'success','message'=>'Subscription created successfully');	
	} catch (AwsException $e) {
		// output error message if fails
		return array('status'=>'error','message'=>$e->getMessage());
		
	} 
}

/*
	* This will send message
	* input 
		config 		: <array>
		message	: <sting>
		topic		: <string>
	  
	  return 
		<array>
	  
	*
*/

\aw2_library::add_service('aws_sns.publish_message','This will send message',['namespace'=>__NAMESPACE__]);

function publish_message($atts,$content=null,$shortcode){ 
		
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'message'=>'',
		'topic'=>''
		), $atts) );
	
	$SnSclient = snsConnect($config);
	
	try {
		$result = $SnSclient->publish([
			'Message' => $message,
			'TopicArn' => $topic,
		]);
		return array('status'=>'success','message'=>'message sent successfully');
	} catch (AwsException $e) {
		// output error message if fails
		return array('status'=>'error','message'=>$e->getMessage());
	} 
}


/*
	* This will Create a topic for sns
	* input 
		config 		: <array>
		topic_name	: <string>
	  
	  return 
		<array>
	  
	*
*/
\aw2_library::add_service('aws_sns.create_topic','This will send message',['namespace'=>__NAMESPACE__]);

function create_topic($atts,$content=null,$shortcode){
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'topic_name'=>''
		), $atts) );
	
	$SnSclient = snsConnect($config);

	try {
		$result = $SnSclient->createTopic([
			'Name' => $topic_name,
		]);
		return array('status'=>'success','data'=>($result),'topicarn'=>$result->get('TopicArn'));
		
	} catch (AwsException $e) {
		// output error message if fails
		return array('status'=>'error','message'=>$e->getMessage());
		
	} 
}

/*
	* This will send message
	* input 
		config 		: <array>
		topic_arn	: <sting>
		
	  
	  return 
		<array>
	  
	*
*/
\aw2_library::add_service('aws_sns.delete_topic','This Will Delete Topic',['namespace'=>__NAMESPACE__]);

function delete_topic($atts,$content=null,$shortcode){
	
	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'topic_arn'=>''
		), $atts) );
		
	$SnSclient = snsConnect($config);

	try {
		$result = $SnSclient->deleteTopic([
			'TopicArn' => $topic_arn,
		]);
		return array('status'=>'success','message'=>'topic deleted successfully');
		
	} catch (AwsException $e) {
		// output error message if fails
		return array('status'=>'error','message'=>$e->getMessage());
		
	}
}

function snsConnect($config){
	
	$snsclient = new SnsClient( array(
        'credentials'=>array(
			'key' => $config['IAM_KEY'],
			'secret' => $config['IAM_SECRET']
		  ),        
        'version' => $config['aws_version'],
        'region'  => $config['aws_region']
      ));
	return $snsclient;
}

?>
