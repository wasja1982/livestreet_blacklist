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

    static function check_stopforumspam_org($sMail) {
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

    static function check_botscout_com($sMail) {
        $aParams = array(
            'key' => Config::Get('plugin.blacklist.key_botscout_com'),
            'mail' => $sMail,
        );
        $sUrl = 'http://botscout.com/test/' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        if ($sAnswer && substr($sAnswer, 0, 1) === 'Y') {
            return true;
        }
        return false;
    }

    static function check_fspamlist_com($sMail) {
        $aParams = array(
            'json' => true,
            'key' => Config::Get('plugin.blacklist.key_fspamlist_com'),
            'spammer' => $sMail,
        );
        $sUrl = 'http://www.fspamlist.com/api.php' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (count($aInfo)) {
            foreach ($aInfo as $aItem) {
                if (isset($aItem['isspammer'])) {
                    return $aItem['isspammer'];
                }
            }
        }
        return false;
    }

    static function blackMail($sMail) {
        if (empty($sMail)) {
            return false;
        }
        $bResult = false;
        if (Config::Get('plugin.blacklist.use_stopforumspam_org')) {
            $bResult = PluginBlacklist::check_stopforumspam_org($sMail);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_botscout_com')) {
            $bResult = PluginBlacklist::check_botscout_com($sMail);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_fspamlist_com')) {
            $bResult = PluginBlacklist::check_fspamlist_com($sMail);
        }
        return $bResult;
    }
}
?>