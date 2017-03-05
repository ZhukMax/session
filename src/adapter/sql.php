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
     * @var string
     */
    protected $column_id;

    /**
     * @var string
     */
    protected $column_data;

    /**
     * @var string
     */
    protected $column_created_at;

    /**
     * @var string
     */
    protected $column_modified_at;

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

        /**
         * Name of table for session data
         */
        if (!isset($options['table']) || empty($options['table']) || !is_string($options['table'])) {
            throw new Exception("Parameter 'table' is required and it must be a non empty string");
        } else {
            $this->table = $options['table'];
        }

        /**
         * Name of session's id column
         */
        if (!isset($options['column']['id']) || empty($options['column']['id']) || !is_string($options['column']['id'])) {
            $this->column_id = 'session_id';
        } else {
            $this->column_id = $options['column']['id'];
        }

        /**
         * Name of session's data column
         */
        if (!isset($options['column']['data']) || empty($options['column']['data']) || !is_string($options['column']['data'])) {
            $this->column_data = 'data';
        } else {
            $this->column_data = $options['column']['data'];
        }

        /**
         * Name of session's created time column
         */
        if (!isset($options['column']['created_at']) || empty($options['column']['created_at']) || !is_string($options['column']['created_at'])) {
            $this->column_created_at = 'created_at';
        } else {
            $this->column_created_at = $options['column']['created_at'];
        }

        /**
         * Name of session's modified time column
         */
        if (!isset($options['column']['modified_at']) || empty($options['column']['modified_at']) || !is_string($options['column']['modified_at'])) {
            $this->column_modified_at = 'modified_at';
        } else {
            $this->column_modified_at = $options['column']['modified_at'];
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
            $this->column_data,
            $this->table,
            $this->column_id,
            $this->column_modified_at,
            $this->column_created_at,
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
            $this->table,
            $this->column_id
        );

        $query = $this->connection->prepare($sql);
        $query->execute([$sessionId]);

        $row = $query->fetch(\PDO::FETCH_NUM);

        if (!empty($row) && intval($row[0]) > 0) {
            $sql = sprintf(
                'UPDATE %s SET %s = ?, %s = ? WHERE %s = ?',
                $this->table,
                $this->column_data,
                $this->column_modified_at,
                $this->column_id
            );

            $query = $this->connection->prepare($sql);
            return $query->execute([$sessionId]);
        } else {
            $sql = sprintf(
                'INSERT INTO %s (%s, %s, %s, %s) VALUES (?, ?, ?, ?)',
                $this->table,
                $this->column_id,
                $this->column_data,
                $this->column_created_at,
                $this->column_modified_at
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
            $this->table,
            $this->column_id
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
            $this->table,
            $this->column_modified_at,
            $this->column_created_at,
            $maxLifetime
        );
        $query = $this->connection->prepare($sql);
        return $query->execute([time()]);
    }
}
