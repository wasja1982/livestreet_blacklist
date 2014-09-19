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

/**
 * Запрещаем напрямую через браузер обращение к этому файлу.
 */
if (!class_exists('Plugin')) {
    die('Hacking attemp!');
}

class PluginBlacklist extends Plugin {

    protected $aInherits = array(
        'entity' => array('ModuleUser_EntityUser'),
        'module' => array('ModuleUser'),
    );

    /**
     * Активация плагина
     */
    public function Activate() {
        return true;
    }

    /**
     * Инициализация плагина
     */
    public function Init() {
    }

    static function blackMail($sMail) {
        $oEngine = Engine::getInstance ();
        return $oEngine -> PluginBlacklist_ModuleBlacklist_blackMail($sMail);
    }
}
?>