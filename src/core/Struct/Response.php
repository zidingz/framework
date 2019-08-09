<?php
namespace SPF\Struct;

use SPF;

class Response
{
    /**
     * @var int
     */
    public $code = 0;
    /**
     * @var string
     */
    public $msg = "";
    /**
     * @var
     */
    public $data;

    public function __construct($code = 0, $msg = "success", $data = [])
    {
        $this->code = $code;
        $this->msg = $msg;
        $this->data = $data;
    }

    public function error($code,$msg = '')
    {
        $this->code = $code;
        if ($msg) {
            $this->msg = $msg;
        }
    }

    public function getErrorCode()
    {
        return $this->code;
    }

    public function getErrorMsg()
    {
        return $this->msg;
    }

    /**
     * @return array
     */
    public function toJson()
    {
        return get_object_vars($this);
    }
}
