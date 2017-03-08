<?php
namespace ZhukMax\Session\Adapters;

use ZhukMax\Session\Adapter;
use Predis\Client as RedisClient;

/**
 * Class Redis
 * @package ZhukMax\Session
 */
class Redis extends Adapter
{
    protected $redis;
    protected $lifetime;

    /**
     * Redis constructor.
     * @param array $options
     */
    public function __construct($options)
    {
        if (!$options['scheme']) {
            $options['scheme'] = 'tcp';
        }

        if (!$options['host']) {
            $options['host'] = "127.0.0.1";
        }

        if (!$options['port']) {
            $options['port'] = 6379;
        }

        if (!$options['database']) {
            $options['database'] = 1;
        }

        // TODO: use lifetime
        if (!$options['lifetime']) {
            $this->lifetime = $options['lifetime'];
        }

        // TODO: Add password for Redis connection

        $this->redis = new RedisClient([
            'scheme'   => $options['scheme'],
            'host'     => $options['host'],
            'port'     => $options['port'],
            'database' => $options['database']
        ]);

        parent::__construct($options);

        session_set_save_handler(
            [$this, "open"],
            [$this, "close"],
            [$this, "read"],
            [$this, "write"],
            [$this, "destroy"],
            [$this, "gc"]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function open()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        return (string)$this->redis->get($sessionId);
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $this->redis->set($sessionId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId = null)
    {
        if ($sessionId === null) {
            $id = $this->getId();
        } else {
            $id = $sessionId;
        }

        return $this->redis->exists($id) ? $this->redis->del($id) : true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function gc()
    {
        return true;
    }
}
