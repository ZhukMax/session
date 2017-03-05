<?php
namespace ZhukMax\Session;

use ZhukMax\Session;
use Exception;

/**
 * Class Sql
 * @package ZhukMax\Session
 */
class Sql extends Session
{
    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var string
     */
    protected $table;

    /**
     * Sql constructor.
     *
     * @param array $options
     * @throws Exception
     */
    public function __construct($options = null)
    {
        /**
         * DSN string
         *
         * Example for Mysql:
         * 'mysql:dbname=testdb;host=127.0.0.1';
         */
        if ($options['dsn']) {
            $dsn = $options['dsn'];
        } else {
            throw new Exception("Parameter 'dsn' is required and it must be a non empty string");
        }

        if (!$options['user']) {
            $options['user'] = 'test';
        }

        if (!$options['password']) {
            $options['password'] = '';
        }

        try {
            $this->connection  = new \PDO($dsn, $options['user'], $options['password']);
        } catch (\PDOException $e) {
            throw new Exception('DB Error: ' . $e->getMessage());
        }

        if (!isset($options['table']) || empty($options['table']) || !is_string($options['table'])) {
            throw new Exception("Parameter 'table' is required and it must be a non empty string");
        } else {
            $this->table = $options['table'];
        }

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
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param $sessionId
     * @return string
     */
    public function read($sessionId)
    {
        $maxLifetime = (int) ini_get('session.gc_maxlifetime');
        $sql = sprintf(
            'SELECT %s FROM %s WHERE %s = ? AND COALESCE(%s, %s) + %d >= ? LIMIT 1',
            'data', $this->table, 'id', 'modified_at', 'created_at',
            $maxLifetime
        );
        $query = $this->connection->prepare($sql);
        $query->execute([$sessionId, time()]);

        $row = $query->fetch(\PDO::FETCH_NUM);

        if (empty($row)) {
            return '';
        }

        return $row[0];
    }

    /**
     * {@inheritdoc}
     *
     * @param $sessionId
     * @param $data
     * @return bool
     */
    public function write($sessionId, $data)
    {
        $sql = sprintf(
            'SELECT COUNT(*) FROM %s WHERE %s = ?',
            $this->table, 'id'
        );

        $query = $this->connection->prepare($sql);
        $query->execute([$sessionId]);

        $row = $query->fetch(\PDO::FETCH_NUM);

        if (!empty($row) && intval($row[0]) > 0) {
            $sql = sprintf(
                'UPDATE %s SET %s = ?, %s = ? WHERE %s = ?',
                $this->table, 'data', 'modified_at', 'id'
            );

            $query = $this->connection->prepare($sql);
            return $query->execute([$sessionId]);
        } else {
            $sql = sprintf(
                'INSERT INTO %s (%s, %s, %s, %s) VALUES (?, ?, ?, ?)',
                $this->table, 'id', 'data', 'created_at', 'modified_at'
            );

            $query = $this->connection->prepare($sql);
            return $query->execute([$sessionId, $data, time(), time()]);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $sessionId
     * @return bool
     */
    public function destroy($sessionId = null)
    {
        if (!$this->isStarted()) {
            return true;
        }

        if (is_null($sessionId)) {
            $sessionId = $this->getId();
        }

        $sql = sprintf(
            'DELETE FROM %s WHERE %s = ?',
            $this->table, 'id'
        );
        $query = $this->connection->prepare($sql);
        $result = $query->execute([$sessionId]);

        return $result && session_destroy();
    }

    /**
     * {@inheritdoc}
     *
     * @param $maxLifetime
     * @return bool
     */
    public function gc($maxLifetime)
    {
        $sql = sprintf(
            'DELETE FROM %s WHERE COALESCE(%s, %s) + %d < ?',
            $this->table, 'modified_at', 'created_at', $maxLifetime
        );
        $query = $this->connection->prepare($sql);
        return $query->execute([time()]);
    }
}
