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

class PluginBlacklist_ModuleBlacklist extends Module {
    public function Init () {
    }

    public function check_whitelist_domains($sMail) {
        $aMail = explode("@", $sMail);
        $sDomain = (count($aMail) > 1 ? $aMail[1] : '');
        return in_array(strtolower($sDomain), Config::Get('plugin.blacklist.whitelist_domains'));
    }

    public function check_blacklist_domains($sMail) {
        $aMail = explode("@", $sMail);
        $sDomain = (count($aMail) > 1 ? $aMail[1] : '');
        $aDomains = Config::Get('plugin.blacklist.blacklist_domains');
        return (in_array('*', $aDomains) || in_array(strtolower($sDomain), $aDomains));
    }

    public function check_whitelist_users_mail($sMail) {
        return in_array(strtolower($sMail), Config::Get('plugin.blacklist.whitelist_users_mail'));
    }

    public function check_whitelist_users_name($sName) {
        return in_array(strtolower($sName), Config::Get('plugin.blacklist.whitelist_users_name'));
    }

    public function check_whitelist_users_ip($sIp) {
        return in_array(strtolower($sIp), Config::Get('plugin.blacklist.whitelist_users_ip'));
    }

    public function check_stopforumspam_org($sMail, $sIp) {
        $aParams = array(
            'f' => 'json',
        );
        $bCheckMail = (Config::Get('plugin.blacklist.check_mail') && $sMail);
        if ($bCheckMail) {
            $aParams['email'] = $sMail;
        }
        $bCheckIp = (Config::Get('plugin.blacklist.check_ip') && $sIp && $sIp !== '127.0.0.1');
        if ($bCheckIp) {
            $aParams['ip'] = $sIp;
        }
        $sUrl = 'http://api.stopforumspam.org/api' . '?' . urldecode(http_build_query($aParams));
        error_log($sUrl);
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (isset($aInfo['success']) && $aInfo['success']) {
            $bMail = false;
            $bIp = false;
            if ($bCheckMail) {
                if (isset($aInfo['email']) && isset($aInfo['email']['appears'])) {
                    $bMail = ($aInfo['email']['appears'] ? true : false);
                }
            }
            if ($bCheckIp) {
                if (isset($aInfo['ip']) && isset($aInfo['ip']['appears'])) {
                    $bIp = ($aInfo['ip']['appears'] ? true : false);
                }
            }
            if ($bCheckMail && !$bCheckIp) {
                return $bMail;
            } else if (!$bCheckMail && $bCheckIp) {
                return $bIp;
            } else if ($bCheckMail && $bCheckIp) {
                return (Config::Get('plugin.blacklist.check_ip_exact') ? ($bMail && $bIp) : ($bMail || $bIp));
            }
        }
        return false;
    }

    public function check_botscout_com($sMail, $sIp) {
        $aParams = array(
            'key' => Config::Get('plugin.blacklist.key_botscout_com'),
        );
        $bCheckMail = (Config::Get('plugin.blacklist.check_mail') && $sMail);
        if ($bCheckMail) {
            $aParams['mail'] = $sMail;
        }
        $bCheckIp = (Config::Get('plugin.blacklist.check_ip') && $sIp && $sIp !== '127.0.0.1');
        if ($bCheckIp) {
            $aParams['ip'] = $sIp;
        }
        if ($bCheckMail && $bCheckIp) {
            $aParams['multi'] = true;
        }
        $sUrl = 'http://botscout.com/test/' . '?' . urldecode(http_build_query($aParams));
        error_log($sUrl);
        $sAnswer = @file_get_contents($sUrl);
        if ($sAnswer) {
            $aAnswer = explode('|', $sAnswer);
            if (count($aAnswer) > 1 && $aAnswer[0] === 'Y') {
                if ($bCheckMail && $bCheckIp && $aAnswer[1] === 'MULTI') {
                    $bMail = false;
                    $bIp = false;
                    for ($i = 2; $i < count($aAnswer); $i += 2) {
                        if (isset($aAnswer[$i]) && isset($aAnswer[$i+1])) {
                            if ($aAnswer[$i] == 'MAIL' && $aAnswer[$i+1] > 0) {
                                $bMail = true;
                            } elseif ($aAnswer[$i] == 'IP' && $aAnswer[$i+1] > 0) {
                                $bIp = true;
                            }
                        }
                    }
                    return (Config::Get('plugin.blacklist.check_ip_exact') ? ($bMail && $bIp) : ($bMail || $bIp));
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    public function check_fspamlist_com($sMail, $sIp) {
        $aParams = array(
            'json' => true,
            'key' => Config::Get('plugin.blacklist.key_fspamlist_com'),
        );
        $aSpammer = array();
        $bCheckMail = (Config::Get('plugin.blacklist.check_mail') && $sMail);
        if ($bCheckMail) {
            $aSpammer[] = $sMail;
        }
        $bCheckIp = (Config::Get('plugin.blacklist.check_ip') && $sIp && $sIp !== '127.0.0.1');
        if ($bCheckIp) {
            $aSpammer[] = $sIp;
        }
        if ($bCheckMail || $bCheckIp) {
            $aParams['spammer'] = implode(',', $aSpammer);
        } else {
            return false;
        }
        $sUrl = 'http://www.fspamlist.com/api.php' . '?' . urldecode(http_build_query($aParams));
        error_log($sUrl);
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (count($aInfo)) {
            $bMail = false;
            $bIp = false;
            foreach ($aInfo as $aItem) {
                if (isset($aItem['spammer'])) {
                    if ($bCheckMail && $aItem['spammer'] == $sMail) {
                        $bMail = ((isset($aItem['isspammer']) && $aItem['isspammer']) ? true : false);
                    } elseif ($bCheckIp && $aItem['spammer'] == $sIp) {
                        $bIp = ((isset($aItem['isspammer']) && $aItem['isspammer']) ? true : false);
                    }
                }
            }
            if ($bCheckMail && !$bCheckIp) {
                return $bMail;
            } else if (!$bCheckMail && $bCheckIp) {
                return $bIp;
            } else if ($bCheckMail && $bCheckIp) {
                return (Config::Get('plugin.blacklist.check_ip_exact') ? ($bMail && $bIp) : ($bMail || $bIp));
            }
        }
        return false;
    }

    public function blackMail($sMail, $sName = null) {
        if (empty($sMail)) {
            return false;
        }
        $sIp = func_getIp();
        if ($this->check_whitelist_users_mail($sMail) ||
            (!empty($sName) && $this->check_whitelist_users_name($sName)) ||
            (!empty($sIp) && $this->check_whitelist_users_ip($sIp)) || 
            $this->check_whitelist_domains($sMail)) {
            return false;
        }
        if ($this->check_blacklist_domains($sMail)) {
            return true;
        }
        if (!Config::Get('plugin.blacklist.check_mail') && !Config::Get('plugin.blacklist.check_ip')) {
            return false;
        }
        $bResult = false;
        if (Config::Get('plugin.blacklist.use_stopforumspam_org')) {
            $bResult = $this->check_stopforumspam_org($sMail, $sIp);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_botscout_com')) {
            $bResult = $this->check_botscout_com($sMail, $sIp);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_fspamlist_com')) {
            $bResult = $this->check_fspamlist_com($sMail, $sIp);
        }
        return $bResult;
    }
}
?>