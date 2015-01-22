<?php
/**
 * Blacklist - проверка E-Mail пользователей на наличие в базах спамеров.
 *
 * Версия:	1.0.2
 * Автор:	Александр Вереник
 * Профиль:	http://livestreet.ru/profile/Wasja/
 * GitHub:	https://github.com/wasja1982/livestreet_blacklist
 *
 **/

// Проверять e-mail по базам
$config['check_mail']=true;

// Порог для срабатывания проверки e-mail (не менее указанного значения)
$config['check_mail_limit']=1;

// Проверять IP по базам
$config['check_ip']=true;

// Порог для срабатывания проверки IP (не менее указанного значения)
$config['check_ip_limit']=1;

// Строгая проверка IP (e-mail и IP должны быть в базе одновременно)
$config['check_ip_exact']=false;

// Использовать базу сайта stopforumspam.com
$config['use_stopforumspam_com']=true;

// Использовать базу сайта botscout.com
$config['use_botscout_com']=true;

// Ключ для сайта botscout.com - http://botscout.com/getkey.htm
$config['key_botscout_com']='xxxxxxxxxxxxxxx';

// Использовать базу сайта fspamlist.com
$config['use_fspamlist_com']=true;

// Ключ для сайта fspamlist.com - http://fspamlist.com/index.php?c=register
$config['key_fspamlist_com']='xxxxxxxxxxxxxxx';

// Проверять e-mail и IP при авторизации
$config['check_authorization']=true;

// Белый список доменов
$config['whitelist_domains']=array();

// Черный список доменов
$config['blacklist_domains']=array();

// Белый список пользователей (логины)
$config['whitelist_users_name']=array();

// Белый список пользователей (e-mail)
$config['whitelist_users_mail']=array();

// Белый список пользователей (IP)
$config['whitelist_users_ip']=array();

// Черный список пользователей (логины)
$config['blacklist_users_name']=array();

// Черный список пользователей (e-mail)
$config['blacklist_users_mail']=array();

// Черный список пользователей (IP)
$config['blacklist_users_ip']=array();

// Время в секундах, в течении которого данные о предыдущей проверке пользователя считаются корректными
$config['recheck_time']=60*60*24*1;

Config::Set('db.table.blacklist', '___db.table.prefix___blacklist');

return $config;
?>