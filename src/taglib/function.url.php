<?php
function smarty_function_url($params)
{
    if (isset($params['ignore']))
    {
        return SPF\Tool::url_merge($params['key'], $params['value'], $params['ignore']);
    }
    else
    {
        return SPF\Tool::url_merge($params['key'], $params['value']);
    }
}
