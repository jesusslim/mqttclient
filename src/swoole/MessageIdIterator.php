<?php
/**
 * Created by PhpStorm.
 * User: jesusslim
 * Date: 2017/8/4
 * Time: 下午10:33
 */

namespace mqttclient\src\swoole;


class MessageIdIterator implements \Iterator
{

    private $id;

    const MAX = 65535;

    public function __construct($start = 0)
    {
        $this->id = $start;
    }

    public function current()
    {
        return $this->id;
    }

    public function next()
    {
        $this->id = $this->id % self::MAX + 1;
        return $this->id;
    }

    public function key()
    {
        return $this->current();
    }

    public function valid()
    {
        return true;
    }

    public function rewind()
    {
        $this->id = 1;
        return $this->id;
    }


}