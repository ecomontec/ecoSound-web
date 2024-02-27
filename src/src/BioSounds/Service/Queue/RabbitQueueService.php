<?php

namespace BioSounds\Service\Queue;

use BioSounds\Entity\Queue;
use BioSounds\Utils\Auth;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitQueueService
{
    /**
     * @var AMQPStreamConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * RabbitQueueService constructor.
     */
    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(QUEUE_HOST, QUEUE_PORT, QUEUE_USER, QUEUE_PASSWORD);
        $this->channel = $this->connection->channel();
    }

    public function queue($data, $type, $count)
    {
        $this->channel->queue_declare('list', false, true, false, false);
        $arr['type'] = $type;
        $arr['user_id'] = Auth::getUserID();
        $arr['start_time'] = date('Y-m-d H:i:s');
        $arr['total'] = $count;
        $queue_id = (new Queue())->insert($arr);
        $message = new AMQPMessage(
            $data,
            ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]
        );
        $headers = [
            'list_type' => $type,
            'queue_id' => $queue_id,
        ];
        $message->set('application_headers', new \PhpAmqpLib\Wire\AMQPTable($headers));
        $this->channel->basic_publish($message, '', 'list');
    }

    /**
     * @throws \Exception
     */
    public function closeConnection()
    {
        $this->channel->close();
        $this->connection->close();
    }
}
