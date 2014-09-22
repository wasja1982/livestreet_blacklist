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

class PluginBlacklist_ModuleUser_EntityUser extends PluginBlacklist_Inherit_ModuleUser_EntityUser {
    /**
     * Проверка емайла на существование
     *
     * @param string $sValue	Валидируемое значение
     * @param array $aParams	Параметры
     * @return bool
     */
    public function ValidateMailExists($sValue,$aParams) {
        if (!$this->PluginBlacklist_ModuleBlacklist_blackMail($sValue)) {
            return parent::ValidateMailExists($sValue,$aParams);
        }
        return $this->Lang_Get(Config::Get('plugin.blacklist.check_ip_exact') ? 'plugin.blacklist.user_in_exact_blacklist' : 'plugin.blacklist.user_in_blacklist');
    }
}
?>