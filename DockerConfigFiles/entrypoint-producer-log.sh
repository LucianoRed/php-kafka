#!/bin/bash
/opt/kafka/bin/kafka-producer-perf-test.sh --topic $TOPICO --num-records $NUMRECORDS --throughput $THROUGHPUT --producer-props acks=$ACKS linger.ms=$LINGER batch.size=$BATCHSIZE compression.type=$COMPRESSIONTYPE buffer.memory=67108864 bootstrap.servers=$BOOTSTRAPSERVERS --payload-file /logs/log.txt --payload-delimiter "#"
