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
$conf->set('log_level', (string) LOG_DEBUG);
$conf->set('debug', 'all');
$conf->set('acks', 0);
$conf->set('bootstrap.servers', "$brokers");
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
	$mensagem = "$dttm: Message payload $x";
	echo "Produzindo mensagem $mensagem\n";
	$topic->produce(RD_KAFKA_PARTITION_UA, 0, "$mensagem");
	
	//var_dump($topic);
}
$time_end = microtime(true);
$time = $time_end - $time_start;
echo "Produced $msglimit messages in $time seconds!<br>\n";
$rk->flush($timeout_ms);
$rk->purge(RD_KAFKA_PURGE_F_QUEUE);
$rk->flush($timeout_ms);


?>
