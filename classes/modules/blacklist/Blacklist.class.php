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

class PluginBlacklist_ModuleBlacklist extends Module {
    protected $oMapper;

    public function Init() {
        $this->oMapper = Engine::GetMapper( __CLASS__ );
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

    public function check_blacklist_users_mail($sMail) {
        return in_array(strtolower($sMail), Config::Get('plugin.blacklist.blacklist_users_mail'));
    }

    public function check_blacklist_users_name($sName) {
        return in_array(strtolower($sName), Config::Get('plugin.blacklist.blacklist_users_name'));
    }

    public function check_blacklist_users_ip($sIp) {
        return in_array(strtolower($sIp), Config::Get('plugin.blacklist.blacklist_users_ip'));
    }

    private function analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact) {
        if (!is_array($aResult)) {
            return false;
        }
        $bMail = (isset($aResult['mail']) ? $aResult['mail'] : false);
        $bIp = (isset($aResult['mail']) ? $aResult['mail'] : false);
        if ($bCheckMail && !$bCheckIp) {
            return $bMail;
        } else if (!$bCheckMail && $bCheckIp) {
            return $bIp;
        } else if ($bCheckMail && $bCheckIp) {
            return ($bIpExact ? ($bMail && $bIp) : ($bMail || $bIp));
        }
    }

    public function check_local_base($sMail, $sIp, $bCheckMail, $bCheckIp) {
        $aWhere = array();
        if ($bCheckMail) {
            $aWhere['mail'] = $sMail;
        }
        if ($bCheckIp) {
            $aWhere['ip'] = $sIp;
        }
        if (!$bCheckMail && !$bCheckIp) {
            return false;
        }
        $aInfo = $this->oMapper->Check($aWhere);
        if ($aInfo) {
            $bMail = false;
            $bIp = false;
            foreach ($aInfo as $aItem) {
                if (isset($aItem['content'])) {
                    if ($bCheckMail && $aItem['content'] == $sMail) {
                        $bMail = ((isset($aItem['result']) && $aItem['result']) ? true : false);
                    } elseif ($bCheckIp && $aItem['content'] == $sIp) {
                        $bIp = ((isset($aItem['result']) && $aItem['result']) ? true : false);
                    }
                }
            }
            return array(
                'mail' => $bMail,
                'ip' => $bIp,
            );
        }
        return false;
    }

    public function check_stopforumspam_org($sMail, $sIp, $bCheckMail, $bCheckIp) {
        $aParams = array(
            'f' => 'json',
        );
        if ($bCheckMail) {
            $aParams['email'] = $sMail;
        }
        if ($bCheckIp) {
            $aParams['ip'] = $sIp;
        }
        $sUrl = 'http://api.stopforumspam.org/api' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (isset($aInfo['success']) && $aInfo['success']) {
            $bMail = false;
            $bIp = false;
            if ($bCheckMail) {
                if (isset($aInfo['email']) && isset($aInfo['email']['appears']) && isset($aInfo['email']['frequency'])) {
                    $bMail = ($aInfo['email']['appears'] ? ($aInfo['email']['frequency'] >= Config::Get('plugin.blacklist.check_mail_limit')) : false);
                }
            }
            if ($bCheckIp) {
                if (isset($aInfo['ip']) && isset($aInfo['ip']['appears']) && isset($aInfo['ip']['frequency'])) {
                    $bIp = ($aInfo['ip']['appears'] ? ($aInfo['ip']['frequency'] >= Config::Get('plugin.blacklist.check_ip_limit')) : false);
                }
            }
            return array(
                'mail' => $bMail,
                'ip' => $bIp,
            );
        }
        return false;
    }

    public function check_botscout_com($sMail, $sIp, $bCheckMail, $bCheckIp) {
        $aParams = array(
            'key' => Config::Get('plugin.blacklist.key_botscout_com'),
        );
        if ($bCheckMail) {
            $aParams['mail'] = $sMail;
        }
        if ($bCheckIp) {
            $aParams['ip'] = $sIp;
        }
        if ($bCheckMail && $bCheckIp) {
            $aParams['multi'] = true;
        }
        $sUrl = 'http://botscout.com/test/' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        if ($sAnswer) {
            $aAnswer = explode('|', $sAnswer);
            if (count($aAnswer) > 1 && $aAnswer[0] === 'Y') {
                $bMail = false;
                $bIp = false;
                $iMailLimit = Config::Get('plugin.blacklist.check_mail_limit');
                $iIpLimit = Config::Get('plugin.blacklist.check_ip_limit');
                if ($bCheckMail && $bCheckIp && $aAnswer[1] === 'MULTI') {
                    for ($i = 2; $i < count($aAnswer); $i += 2) {
                        if (isset($aAnswer[$i]) && isset($aAnswer[$i+1])) {
                            if ($aAnswer[$i] == 'MAIL') {
                                $bMail = ($aAnswer[$i+1] >= $iMailLimit);
                            } elseif ($aAnswer[$i] == 'IP') {
                                $bIp = ($aAnswer[$i+1] >= $iIpLimit);
                            }
                        }
                    }
                } else if (count($aAnswer) == 3) {
                    if ($bCheckMail && $aAnswer[1] === 'MAIL') {
                        $bMail = ($aAnswer[2] >= $iMailLimit);
                    } else if ($bCheckMail && $aAnswer[1] === 'IP') {
                        $bIp = ($aAnswer[$i+1] >= $iIpLimit);
                    }
                }
                return array(
                    'mail' => $bMail,
                    'ip' => $bIp,
                );
            }
        }
        return false;
    }

    public function check_fspamlist_com($sMail, $sIp, $bCheckMail, $bCheckIp) {
        $aParams = array(
            'json' => true,
            'key' => Config::Get('plugin.blacklist.key_fspamlist_com'),
        );
        $aSpammer = array();
        if ($bCheckMail) {
            $aSpammer[] = $sMail;
        }
        if ($bCheckIp) {
            $aSpammer[] = $sIp;
        }
        if ($bCheckMail || $bCheckIp) {
            $aParams['spammer'] = implode(',', $aSpammer);
        } else {
            return false;
        }
        $sUrl = 'http://www.fspamlist.com/api.php' . '?' . urldecode(http_build_query($aParams));
        $sAnswer = @file_get_contents($sUrl);
        $aInfo = json_decode($sAnswer, true);
        if (count($aInfo)) {
            $bMail = false;
            $bIp = false;
            $iMailLimit = Config::Get('plugin.blacklist.check_mail_limit');
            $iIpLimit = Config::Get('plugin.blacklist.check_ip_limit');
            foreach ($aInfo as $aItem) {
                if (isset($aItem['spammer'])) {
                    if ($bCheckMail && $aItem['spammer'] == $sMail) {
                        $bMail = ((isset($aItem['isspammer']) && isset($aItem['timesreported']) && $aItem['isspammer']) ? ($aItem['timesreported'] >= $iMailLimit) : false);
                    } elseif ($bCheckIp && $aItem['spammer'] == $sIp) {
                        $bIp = ((isset($aItem['isspammer']) && isset($aItem['timesreported']) && $aItem['isspammer']) ? ($aItem['timesreported'] >= $iIpLimit) : false);
                    }
                }
            }
            return array(
                'mail' => $bMail,
                'ip' => $bIp,
            );
        }
        return false;
    }

    public function blackMail($sMail, $sName = null) {
        $sIp = func_getIp();
        if ((!empty($sMail) && $this->check_whitelist_users_mail($sMail)) ||
            (!empty($sName) && $this->check_whitelist_users_name($sName)) ||
            (!empty($sIp) && $this->check_whitelist_users_ip($sIp)) || 
            $this->check_whitelist_domains($sMail)) {
            return false;
        }
        if ((!empty($sMail) && $this->check_blacklist_users_mail($sMail)) ||
            (!empty($sName) && $this->check_blacklist_users_name($sName)) ||
            (!empty($sIp) && $this->check_blacklist_users_ip($sIp)) ||
            $this->check_blacklist_domains($sMail)) {
            return true;
        }
        $bCheckMail = (Config::Get('plugin.blacklist.check_mail') && $sMail);
        $bCheckIp = (Config::Get('plugin.blacklist.check_ip') && $sIp && $sIp !== '127.0.0.1');
        if (!$bCheckMail && !$bCheckIp) {
            return false;
        }
        $bIpExact = Config::Get('plugin.blacklist.check_ip_exact');
        if ($this->analyse_result($this->check_local_base($sMail, $sIp, $bCheckMail, $bCheckIp), $bCheckMail, $bCheckIp, $bIpExact)) {
            return true;
        }
        $bMail = false;
        $bIp = false;
        if (Config::Get('plugin.blacklist.use_stopforumspam_org')) {
            $aResult = $this->check_stopforumspam_org($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult['mail']) ? $aResult['mail'] : false);
            $bIp |= (is_array($aResult) && isset($aResult['ip']) ? $aResult['ip'] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_botscout_com')) {
            $aResult = $this->check_botscout_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult['mail']) ? $aResult['mail'] : false);
            $bIp |= (is_array($aResult) && isset($aResult['ip']) ? $aResult['ip'] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_fspamlist_com')) {
            $aResult = $this->check_fspamlist_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult['mail']) ? $aResult['mail'] : false);
            $bIp |= (is_array($aResult) && isset($aResult['ip']) ? $aResult['ip'] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
        }

        if ($bCheckMail) {
            $this->oMapper->AddMailResult($sMail, $bMail);
        }
        if ($bCheckIp) {
            $this->oMapper->AddIpResult($sIp, $bIp);
        }
        return $bResult;
    }
}
?>