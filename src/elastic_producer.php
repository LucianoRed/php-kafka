<?php
$brokers = getenv("KAFKA_BROKERS");
$topic = getenv("KAFKA_TOPIC");
$consumer_group = getenv("KAFKA_CONSUMER_GROUP");
$debug = getenv("DEBUG");
$departamento = getenv("DEPARTAMENTO");
$elasticsearch_address = getenv("ELASTIC_ADDRESS");
$app_number = GETENV("APP_NUMBER");
if($consumer_group == "") {
        $consumer_group = "phpKafkaTester";
} 
if($topic == "") {
        $topic = "phpKafkaTester";
} 
$conf = new RdKafka\Conf();

// Set a rebalance callback to log partition assignments (optional)
$conf->setRebalanceCb(function (RdKafka\KafkaConsumer $kafka, $err, array $partitions = null) {
    switch ($err) {
        case RD_KAFKA_RESP_ERR__ASSIGN_PARTITIONS:
            echo "Assign: ";
            var_dump($partitions);
            $kafka->assign($partitions);
            break;

         case RD_KAFKA_RESP_ERR__REVOKE_PARTITIONS:
             echo "Revoke: ";
             var_dump($partitions);
             $kafka->assign(NULL);
             break;

         default:
            throw new \Exception($err);
    }
});

// Configure the group.id. All consumer with the same group.id will consume
// different partitions.
$conf->set('group.id', "$consumer_group");

// Initial list of Kafka brokers
$conf->set('metadata.broker.list', "$brokers");

// Set where to start consuming messages when there is no initial offset in
// offset store or the desired offset is out of range.
// 'earliest': start from the beginning
$conf->set('auto.offset.reset', 'earliest');

// Emit EOF event when reaching the end of a partition
$conf->set('enable.partition.eof', 'true');

$consumer = new RdKafka\KafkaConsumer($conf);

// Subscribe to topic 'test'
$consumer->subscribe(["$topic"]);

echo "Waiting for partition assignment... (make take some time when\n";
echo "quickly re-joining the group after leaving it.)\n";

while (true) {
    $message = $consumer->consume(120*1000);
    switch ($message->err) {
        case RD_KAFKA_RESP_ERR_NO_ERROR:
            if($debug == "S") {
               var_dump($message);
            }

          $jsonMessage = $message;
          
          // Decode JSON into an associative array
          $messageArray = json_decode($jsonMessage, true);
          
          // Add the new object
          $messageArray['kubernetes'] = array(
              'namespace_name' => "$departamento"
          );
          
          // Encode the modified array back to JSON
          $newJsonMessage = json_encode($messageArray);
          
          // Output the updated JSON message to elasticsearch
          $cmd = "curl -tls1.2 -s -k --cert /etc/elasticsearch/secret/admin-cert   --key /etc/elasticsearch/secret/admin-key -kv -X POST "https://$elasticsearch_address:9200/app-$app_number/_doc" -H 'Content-Type: application/json' -d '$newJsonMessage'";
          exec($cmd);
      
            break;
        case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            echo "No more messages; will wait for more\n";
            break;
        case RD_KAFKA_RESP_ERR__TIMED_OUT:
            echo "Timed out\n";
            break;
        default:
            throw new \Exception($message->errstr(), $message->err);
            break;
    }
}

?>
