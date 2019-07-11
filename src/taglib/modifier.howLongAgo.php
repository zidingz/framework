<?php
function smarty_modifier_howLongAgo($string)
{
    return SPF\Tool::howLongAgo($string);
}
