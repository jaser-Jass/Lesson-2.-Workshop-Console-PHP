<?php

namespace App\Queue;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;

class RabbitMQ implements Queue
{
    private ?AMQPMessage $lastMessage = null;
    private ?AMQPChannel $channel = null;
    private ?AMQPStreamConnection $connection = null;

    public function __construct(private string $queueName)
    {
        $this->lastMessage = null;
    }

    
    public function sendMessage($message): void 
    {
        $this->open();

        try {
            $msg = new AMQPMessage((string)$message, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $this->channel->basic_publish($msg, '', $this->queueName);
            var_dump($msg);
        } catch (\Exception $e) {
            echo "Ошибка при отправке сообщения: " . $e->getMessage();
        } finally {
            $this->close();
        }
    }

    public function getMessage(): ?string
    {
        $this->open();

        try {
            $msg = $this->channel->basic_get($this->queueName);

            if ($msg) {
                $this->lastMessage = $msg;
                return $msg->body;
            }
        } catch (\Exception $e) {
            echo "Ошибка при получении сообщения: " . $e->getMessage();
        } finally {
            $this->close();
        }

        return null;
    }

    public function ackLastMessage(): void
    {
        if ($this->lastMessage) {
            $this->lastMessage->ack();
            $this->lastMessage = null; 
        }
        $this->close();
    }

    private function open(): void
    {
        if (is_null($this->connection) || !$this->connection->isConnected()) {
            try {
                $this->connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
                $this->channel = $this->connection->channel();
                $this->channel->queue_declare($this->queueName, false, false, false, true);
            } catch (AMQPConnectionClosedException $e) {
                echo "Ошибка соединения с RabbitMQ: " . $e->getMessage();
                $this->connection = null; 
            }
        }
    }

    private function close(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }
        if ($this->connection) {
            $this->connection->close();
        }
    }
}

