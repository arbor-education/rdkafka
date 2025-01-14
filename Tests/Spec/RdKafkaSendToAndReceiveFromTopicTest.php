<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Interop\Queue\Message;
use Interop\Queue\Spec\SendToAndReceiveFromTopicSpec;

/**
 * @group functional
 *
 * @retry 5
 */
class RdKafkaSendToAndReceiveFromTopicTest extends SendToAndReceiveFromTopicSpec
{
    public function test()
    {
        $context = $this->createContext();

        $topic = $this->createTopic($context, uniqid('', true));

        $consumer = $context->createConsumer($topic);
        $expectedBody = __CLASS__.time();

        $context->createProducer()->send($topic, $context->createMessage($expectedBody));

        // Initial balancing can take some time, so we want to make sure the timeout is high enough
        $message = $consumer->receive(15000); // 15 sec

        $this->assertInstanceOf(Message::class, $message);
        $consumer->acknowledge($message);

        $this->assertSame($expectedBody, $message->getBody());
    }

    protected function createContext()
    {
        $config = [
            'global' => [
                'group.id' => uniqid('', true),
                'metadata.broker.list' => getenv('RDKAFKA_HOST').':'.getenv('RDKAFKA_PORT'),
                'enable.auto.commit' => 'false',
            ],
            'topic' => [
                'auto.offset.reset' => 'earliest',
            ],
        ];

        $context = (new RdKafkaConnectionFactory($config))->createContext();

        sleep(3);

        return $context;
    }
}
