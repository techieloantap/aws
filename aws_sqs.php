<?php

namespace aw2\aws_s3;
use Aws\Sqs\SqsClient; 
use Aws\Exception\AwsException;

/**
	Desc : Receive SQS Message
	Input: 
		path   : <string>
		config : <array>
**/

\aw2_library::add_service('aws_s3.get_file_contents','Receive SQS Message',['namespace'=>__NAMESPACE__]);
function receive_sqs_message($atts,$content=null,$shortcode){
	
	$client = sqsConnect($config);

	if(\aw2_library::pre_actions('all',$atts,$content,$shortcode)==false)return;
	extract( \aw2_library::shortcode_atts( array(
		'config'=>'',
		'queueUrl'=>''
		), $atts) );	
	
	
	try {
    $result = $client->receiveMessage(array(
        'AttributeNames' => ['SentTimestamp'],
        'MaxNumberOfMessages' => 5,
        'MessageAttributeNames' => ['All'],
        'QueueUrl' => $queueUrl, // REQUIRED
        'WaitTimeSeconds' => 10,
    ));
	
	foreach ($result->getPath('Messages') as $messageBody) {
    // Do something with the message
    print_r($messageBody)."<br/>";
}
	
    if (!empty($result->get('Messages'))) {
        return $result->get('Messages');
        /*$result = $client->deleteMessage([
            'QueueUrl' => $queueUrl, // REQUIRED
            'ReceiptHandle' => $result->get('Messages')[0]['ReceiptHandle'] // REQUIRED
        ]);*/
    } else {
        echo "No messages in queue. \n";
    }
} catch (AwsException $e) {
    // output error message if fails
    error_log($e->getMessage());
}
}

function sqsConnect($config){
	
	$snsclient = new SqsClient( array(
        'credentials'=>array(
			'key' => $config['IAM_KEY'],
			'secret' => $config['IAM_SECRET']
		  ),        
        'version' => $config['aws_version'],
        'region'  => $config['aws_region']
      ));
	return $snsclient;
}
