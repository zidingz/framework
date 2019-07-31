<?php
namespace SPF\Struct;

use SPF;

class Response
{
    public $code = 0;
    public $msg = "";
    public $data = null;

    function __construct($code, $data = null, $msg = 'success')
    {
        $this->code = $code;
        $this->msg = $msg;
        $data = empty($data) ? new \stdClass() : $data;
        $this->data = $data;
    }
}
