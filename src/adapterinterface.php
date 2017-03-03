<?php
namespace ZhukMax;

/**
 * Interface AdapterInterface
 * @package ZhukMax\Session
 */
interface AdapterInterface
{
    /**
     * Starts the session
     *
     * @return bool
     */
    public function start();

    /**
     * Check the session has been started
     *
     * @return bool
     */
    public function isStarted();

    /**
     * Returns the status of the session
     *
     * @return mixed
     */
    public function status();

    /**
     * Sets a session variable
     *
     * @param string $name
     * @param $value
     */
    public function set($name, $value);

    /**
     * Gets a session variable
     *
     * @param string $name
     * @param $default
     * @return mixed
     */
    public function get($name, $default = null);

    /**
     * Check whether a session variable is set
     *
     * @param string $name
     * @return bool
     */
    public function has($name);

    /**
     * Removes a session variable
     *
     * @param string $name
     */
    public function remove($name);

    /**
     * Destroys the active session
     *
     * @param $clean
     * @return bool
     */
    public function destroy($clean);
}
