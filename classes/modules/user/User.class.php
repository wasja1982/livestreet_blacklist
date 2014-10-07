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

class PluginBlacklist_ModuleUser extends PluginBlacklist_Inherit_ModuleUser {
    /**
     * Авторизовывает юзера
     *
     * @param ModuleUser_EntityUser $oUser	Объект пользователя
     * @param bool $bRemember	Запоминать пользователя или нет
     * @param string $sKey	Ключ авторизации для куков
     * @return bool
     */
    public function Authorization(ModuleUser_EntityUser $oUser,$bRemember=true,$sKey=null) {
        if (!Config::Get('plugin.blacklist.check_authorization') || ($oUser && $oUser->isAdministrator()) || ($oUser && !$this->PluginBlacklist_ModuleBlacklist_blackMail($oUser->getMail(), $oUser->getLogin()))) {
            return parent::Authorization($oUser, $bRemember, $sKey);
        }
		$this->Message_AddErrorSingle($this->Lang_Get(Config::Get('plugin.blacklist.check_ip_exact') ? 'plugin.blacklist.user_in_exact_blacklist' : 'plugin.blacklist.user_in_blacklist'));
        return false;
    }
}
?>