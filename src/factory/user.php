<?php
global $php;
$user = new SPF\Auth($php->config['user']);
return $user;
