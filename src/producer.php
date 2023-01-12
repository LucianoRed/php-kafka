<?php
$brokers = getenv("KAFKA_BROKERS");
$topic = getenv("KAFKA_TOPIC");
if(isset($_GET['messages'])) {
	$msglimit = intval($_GET['messages']);
} else {
	$msglimit = 100;	
}
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
for($x=0;$x<$msglimit;$x++) {
	$dttm = date();
	$topic->produce(RD_KAFKA_PARTITION_UA, 0, "$dttm: Message payload $x");
	var_dump($topic);
}
echo "Produced $msglimit messages!";
$rk->flush($timeout_ms);
$rk->purge(RD_KAFKA_PURGE_F_QUEUE);
$rk->flush($timeout_ms);

?>
