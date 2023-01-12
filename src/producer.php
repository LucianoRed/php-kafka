<?php
$brokers = getenv("KAFKA_BROKERS");
$topic = getenv("KAFKA_TOPIC");
if(isset($_GET['messages'])) {
	$msglimit = intval($_GET['messages']);
} else {
	$msglimit = 100;	
}
echo "Using kafka broker $brokers to send $msglimit on topic $topic<br>\n";


$conf = new RdKafka\Conf();
$conf->set('metadata.broker.list', "$brokers");

//If you need to produce exactly once and want to keep the original produce order, uncomment the line below
//$conf->set('enable.idempotence', 'true');

$producer = new RdKafka\Producer($conf);

$topic = $producer->newTopic("$topic");
$time_start = microtime(true);

for ($i = 0; $i < $msglimit; $i++) {
	$dttm = microtime(true);
    $topic->produce(RD_KAFKA_PARTITION_UA, 0, "$dttm: Message payload $i");
    $producer->poll(0);
}
$time = $time_end - $time_start;
echo "Produced $msglimit messages in $time seconds!<br>\n";

$time_start = microtime(true);
for ($flushRetries = 0; $flushRetries < $msglimit; $flushRetries++) {
    $result = $producer->flush(10000);
    if (RD_KAFKA_RESP_ERR_NO_ERROR === $result) {
        break;
    }
}
$time = $time_end - $time_start;
echo "Flushed retries $msglimit messages in $time seconds!<br>\n";

if (RD_KAFKA_RESP_ERR_NO_ERROR !== $result) {
    throw new \RuntimeException('Was unable to flush, messages might be lost!');
}
$producer->purge(RD_KAFKA_PURGE_F_QUEUE);
/*
$conf = new RdKafka\Conf();
$conf->set('log_level', (string) LOG_DEBUG);
$conf->set('debug', 'all');
$rk = new RdKafka\Producer($conf);
$rk->addBrokers("$brokers");
$topic = $rk->newTopic("$topic");
if($msglimit > 2000000) {
	exit;
}
$count=0;
$time_start = microtime(true);
for($x=0;$x<$msglimit;$x++) {
	$dttm = microtime(true);
	$topic->produce(RD_KAFKA_PARTITION_UA, 0, "$dttm: Message payload $x");
	//var_dump($topic);
}
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Produced $msglimit messages in $time seconds!<br>\n";
$rk->flush($timeout_ms);
$rk->purge(RD_KAFKA_PURGE_F_QUEUE);
$rk->flush($timeout_ms);
*/

?>
