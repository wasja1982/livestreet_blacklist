<?php
/**
 * Blacklist - проверка E-Mail пользователей на наличие в базах спамеров.
 *
 * Версия:	1.0.0
 * Автор:	Александр Вереник
 * Профиль:	http://livestreet.ru/profile/Wasja/
 * GitHub:	https://github.com/wasja1982/livestreet_blacklist
 *
 **/

// Использовать базу сайта stopforumspam.org
$config['use_stopforumspam_org']=true;

// Использовать базу сайта botscout.com
$config['use_botscout_com']=true;

// Ключ для сайта botscout.com - http://botscout.com/getkey.htm
$config['key_botscout_com']='xxxxxxxxxxxxxxx';

return $config;
?>