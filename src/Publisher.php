<?php
namespace App;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Publisher
{
  private $queue;
  private $exchange;
  private $connection;
  private $channel;

  public function __construct($host, $port, $user, $password, $queue = 'query')
  {
    $this->queue = $queue;
    $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
    $this->channel = $this->connection->channel();

    $this->channel->queue_declare($this->queue, false, true, false, false);
  }

  public function sendQueries(array $queries)
  {
    foreach ($queries as $q) {
      $msg = new AMQPMessage($q, ["status" => 'new']);
      $this->channel->batch_basic_publish($msg, '', $this->queue);
    }

    $this->channel->publish_batch();
  }

  public function close()
  {
    $this->channel->close();
    $this->connection->close();
  }
}

class Receiver
{
  private $connection;
  private $channel;
  private $queue;

  public function __construct($host, $port, $user, $password, $queue = 'query')
  {
    $this->queue = $queue;
    $this->connection = new AMQPStreamConnection($host, $port, $user, $password);
    $this->channel = $this->connection->channel();

    $this->channel->queue_declare($this->queue, false, true, false, false);
    $this->channel->basic_qos(10, 1, true);
  }

  public function receiveCallback($callback)
  {
    $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);

    while ($this->channel->confirm_select()) {
      $this->channel->wait();
    }
  }

  public function close()
  {
    $this->channel->close();
    $this->connection->close();
  }
}
