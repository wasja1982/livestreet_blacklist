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
        if (empty($sMail)) {
            return false;
        }
        $aParams = array(
            'f' => 'json',
            'email' => $sMail,
        );
        $sUrl = 'http://api.stopforumspam.org/api' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (isset($aInfo['success']) && $aInfo['success']) {
            if (isset($aInfo['email']) && isset($aInfo['email']['appears'])) {
                return $aInfo['email']['appears'];
            }
        }
        return false;
    }
}
?>