<?php
namespace SPF\Struct;

use SPF;

class Response extends ResponseBase
{
    /**
     * @return array
     */
    public function toJson()
    {
        $ret = [];
        $ret['code'] = $this->getErrorCode();
        $ret['msg'] = $this->getErrorMsg();
        $ret['data'] = get_object_vars($this);
        return $ret;
    }
}
