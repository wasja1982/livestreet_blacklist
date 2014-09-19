Плагин "Blacklist" (версия 1.0.0) для LiveStreet 1.0.3


ОПИСАНИЕ

Проверка E-Mail и IP пользователей на наличие в базах спамеров.

Настройка плагина осуществляется редактированием файла "/plugins/blacklist/config/config.php".

Поддерживаемые директивы:
1) $config['check_ip'] - Дополнительно проверять IP. По умолчанию включено (true).

2) $config['check_ip_exact'] - Строгая проверка IP (e-mail и IP должны быть в базе одновременно). По умолчанию отлючено (false).

3) $config['use_stopforumspam_org'] - Использовать базу сайта stopforumspam.org. По умолчанию включено (true).

4) $config['use_botscout_com'] - Использовать базу сайта botscout.com. По умолчанию включено (true).

5) $config['key_botscout_com'] - Ключ для сайта botscout.com (http://botscout.com/getkey.htm).

6) $config['use_fspamlist_com'] - Использовать базу сайта fspamlist.com. По умолчанию включено (true).

7) $config['key_fspamlist_com'] - Ключ для сайта fspamlist.com (http://fspamlist.com/index.php?c=register).

8) $config['check_authorization'] - Проверять e-mail при авторизации. По умолчанию включено (true).

9) $config['whitelist_domains'] - Белый список доменов (e-mail с этих доменов считаются доверенными и не проверяются).

10) $config['blacklist_domains'] - Черный список доменов (e-mail с этих доменов запрещены).



УСТАНОВКА

1. Скопировать плагин в каталог /plugins/
2. Через панель управления плагинами (/admin/plugins/) запустить его активацию.



АВТОР
Александр Вереник

САЙТ 
https://github.com/wasja1982/livestreet_blacklist
