<?php
$brokers = getenv("KAFKA_BROKERS");
$topic = getenv("KAFKA_TOPIC");
if(isset($_GET['messages'])) {
        $msglimit = intval($_GET['messages']);
} else {
        $msglimit = 10;
}
if(isset($_GET['acks'])) {
        $acks = intval($_GET['acks']);
} else {
        $acks = 0;
}
echo "Using kafka broker $brokers to send $msglimit on topic $topic<br>\n";

$conf = new RdKafka\Conf();
$conf->set('metadata.broker.list', $brokers);
$conf->set('acks', $acks);
$conf->set('log_level', (string) LOG_DEBUG);
$conf->set('debug', 'all');

//If you need to produce exactly once and want to keep the original produce order, uncomment the line below
//$conf->set('enable.idempotence', 'true');

$producer = new RdKafka\Producer($conf);

$ctopic = $producer->newTopic("$topic");

for ($i = 0; $i < $msglimit; $i++) {
    $ctopic->produce(RD_KAFKA_PARTITION_UA, 0, "Message $i");
            $producer->poll(0);
            $result = $producer->flush(10000);
            if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
                echo "Message $i ok";
            }
        if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
            throw new \RuntimeException('Was unable to flush, messages might be lost!');
        }
}

?>
