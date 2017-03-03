<?php
namespace ZhukMax\Session;

class Adapter implements AdapterInterface
{
    protected $_id;

    /**
     * Adapter constructor.
     * @param array $options
     */
    public function __construct($options)
    {
        if (is_array($options)) {
            $this->setId($options);
        }
    }

    /**
     * @param array $options
     */
    protected function setId($options)
    {
        if ($options['id']) {
            $this->_id = $options['id'];
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param string $name
     * @return string
     */
    protected function getKey($name)
    {
        if (!empty($this->_id)) {
            $key = $this->_id . '#' . $name;
        } else {
            $key = $name;
        }

        return $key;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return bool
     */
    protected function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Starts the session
     *
     * @return bool
     */
    public function start()
    {
        if (!$this->isStarted()) {
            return session_start();
        }
        return true;
    }

    /**
     * Check the session has been started
     *
     * @return bool
     */
    public function isStarted()
    {
        if (session_id()) {
            return true;
        }
        return false;
    }

    /**
     * Returns the status of the session
     *
     * @return mixed
     */
    public function status()
    {
        switch (session_status()) {
            case PHP_SESSION_ACTIVE:
                return 'active';
                break;
            case PHP_SESSION_NONE:
                return 'none';
                break;
            default:
                return false;
        }
    }

    /**
     * Sets a session variable
     *
     * @param string $name
     * @param $value
     */
    public function set($name, $value)
    {
        $key = $this->getKey($name);

        $_SESSION[$key] = $value;
    }

    /**
     * Alias for set()
     *
     * @param string $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Gets a session variable
     *
     * @param string $name
     * @param $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        $key = $this->getKey($name);

        if ($value = $_SESSION[$key]) {
            return $value;
        }

        return $default;
    }

    /**
     * Alias for get()
     *
     * @param string $name
     * @param $default
     * @return mixed
     */
    public function __get($name, $default = null)
    {
        return $this->get($name, $default);
    }

    /**
     * Check whether a session variable is set
     *
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $key = $this->getKey($name);

        return isset($_SESSION[$key]);
    }

    /**
     * Alias for has()
     *
     * @param $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Removes a session variable
     *
     * @param string $name
     */
    public function remove($name)
    {
        $key = $this->getKey($name);

        unset($_SESSION[$key]);
    }

    /**
     * Alias for remove()
     *
     * @param $name
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * Destroys the active session
     *
     * @param $clean
     * @return bool
     */
    public function destroy($clean = false)
    {
        if ($clean) {
            if (!empty($this->_id)) {
                foreach ($_SESSION as $key => $value) {
                    if ($this->startsWith($key, $this->_id . '#')) {
                        $_SESSION[$key] = [];
                    }
                }
            } else {
                $_SESSION = [];
            }
        }

        return session_destroy();
    }
}
