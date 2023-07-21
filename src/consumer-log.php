<?php
$brokers = getenv("KAFKA_BROKERS");
$topic = getenv("KAFKA_TOPIC");
$consumer_group = getenv("KAFKA_CONSUMER_GROUP");
$debug = getenv("DEBUG");

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
        
            $jsonMessage = $message->payload;
                      
            $messageArray = json_decode($jsonMessage, true);
            $Objeto = json_decode($jsonMessage, true);
            //print_r($Objeto);
            $departamento = getenv("DEPARTAMENTO");
            // if($Objeto['departamento'] == "$departamento") {
            //     error_log($jsonMessage);
            // }
            $ObjAdditional = $Objeto['additional-data'];
            $tempo_execucao = intval($ObjAdditional['tempo-execucao-endpoint-ms']);
            //echo "Transacao levou $tempo_execucao\n";
            $limite_tempo = getenv("LIMITE_TEMPO");
            if($limite_tempo == "") {
                    $limite_tempo = 110;
            }
            if($tempo_execucao > $limite_tempo) {
                    error_log("LIMITE DE TEMPO EXCEDIDO");
                    error_log($jsonMessage);
            }
            // Add the new object
            //    $messageArray['kubernetes'] = array(
            //        'namespace_name' => "$departamento"
            //    );
    
            // Encode the modified array back to JSON
            $newJsonMessage = json_encode($messageArray);

        // Add the new object
        //    $messageArray['kubernetes'] = array(
        //        'namespace_name' => "$departamento"
        //    );

        // Encode the modified array back to JSON
        $newJsonMessage = json_encode($messageArray);

                 
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
