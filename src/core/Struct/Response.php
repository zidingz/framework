<?php
namespace SPF\Struct;

use SPF;

class Response extends Map
{
    /**
     * @var int
     */
    protected $code = 0;

    /**
     * @var string
     */
    protected $msg = "";

    /**
     * @param int $code
     * @param string $msg
     * @param array $data
     */
    public function __construct(int $code = 0, string $msg = "success", array $data = [])
    {
        $this->code = $code;
        $this->msg = $msg;

        $this->sets($data);
    }

    /**
     * @param int $code
     * @param string $msg
     * 
     * @return self
     */
    public function error(int $code, string $msg = '')
    {
        $this->code = $code;

        $msg && $this->msg = $msg;

        return $this;
    }

    /**
     * @return int
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->msg;
    }
}
