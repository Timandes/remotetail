<?php
/**
 * Remote Tail
 *
 * @author Timandes White <timands@gmail.com>
 * @package remotetail/remotetail
 * @license http://www.apache.org/licenses/LICENSE-2.0
 */

namespace remotetail;

/**
 * File Reader
 */
class FileReader
{
    private $_eventBase = null;
    private $_connection = null;
    private $_path = "";
    private $_currentPos = 0;
    private $_inotify = null;
    private $_watchDescriptor = 0;
    private $_bufferFull = false;

    public function __construct($eventBase, $connection, $path)
    {
        $this->_path = $path;
        $this->_eventBase = $eventBase;
        $this->_connection = $connection;
    }

    public function beginSurveillance()
    {
        clearstatcache(true, $this->_path);
        $this->_currentPos = filesize($this->_path);
        $this->_inotify = inotify_init();
        $this->_watchDescriptor = inotify_add_watch($this->_inotify, $this->_path, IN_MODIFY);
        $this->_eventBase->add($this->_inotify,
                \Workerman\Events\EventInterface::EV_READ,
                array($this, 'onFileModified'));
    }
    public function onFileModified()
    {
        if ($this->_bufferFull)
            return;

        $info = inotify_read($this->_inotify);
        if (!isset($info[0]['mask'])
                || !($info[0]['mask'] & IN_MODIFY))
            return;

        $fp = fopen($this->_path, 'rb');
        fseek($fp, $this->_currentPos);
        while(!feof($fp)) {
            $line = fgets($fp);
            $this->_connection->send($line);
            if ($this->_bufferFull) {
                echo "Buffer is full, stop reading\n";
                break;
            }
        }
        $this->_currentPos = ftell($fp);
        fclose($fp);
    }

    public function onBufferFull()
    {
        $this->_bufferFull = true;
    }
    public function onBufferDrain()
    {
        $this->_bufferFull = false;
    }

    public function stopSurveillance()
    {
        $this->_eventBase->del($this->_inotify,
                \Workerman\Events\EventInterface::EV_READ);
        inotify_rm_watch($this->_inotify, $this->_watchDescriptor);
        fclose($this->_inotify);
    }
}

