<?php
namespace SPF\Struct;

use SPF;

class Response
{
    /**
     * @var int
     */
    private $_code = 0;
    /**
     * @var string
     */
    private $_msg = "";

    public function error($code,$msg = '')
    {
        $this->_code = $code;
        if ($msg) {
            $this->_msg = $msg;
        }
    }

    public function getErrorCode()
    {
        return $this->_code;
    }

    public function getErrorMsg()
    {
        return $this->_msg;
    }

    public function __set($name, $value)
    {
        throw new ResponseException("can not set property $name, $value",1);
    }
}
