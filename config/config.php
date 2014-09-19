<?php
/**
 * Blacklist - проверка E-Mail пользователей на наличие в базах спамеров.
 *
 * Версия:	1.0.1
 * Автор:	Александр Вереник
 * Профиль:	http://livestreet.ru/profile/Wasja/
 * GitHub:	https://github.com/wasja1982/livestreet_blacklist
 *
 **/

// Дополнительно проверять IP
$config['check_ip']=true;

// Строгая проверка IP (e-mail и IP должны быть в базе одновременно)
$config['check_ip_exact']=false;

// Использовать базу сайта stopforumspam.org
$config['use_stopforumspam_org']=true;

// Использовать базу сайта botscout.com
$config['use_botscout_com']=true;

// Ключ для сайта botscout.com - http://botscout.com/getkey.htm
$config['key_botscout_com']='xxxxxxxxxxxxxxx';

// Использовать базу сайта fspamlist.com
$config['use_fspamlist_com']=true;

// Ключ для сайта fspamlist.com - http://fspamlist.com/index.php?c=register
$config['key_fspamlist_com']='xxxxxxxxxxxxxxx';

// Проверять e-mail при авторизации
$config['check_authorization']=true;

// Белый список доменов
$config['whitelist_domains']=array();

// Черный список доменов
$config['blacklist_domains']=array();

return $config;
?>