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

define('DEBUG', false);

class PluginBlacklist_ModuleBlacklist extends Module {
    protected $oMapper;

    const SERVICE_STOPFORUMSPAM_COM = 1;
    const SERVICE_BOTSCOUT_COM = 2;
    const SERVICE_FSPAMLIST_COM = 3;

    const TYPE_MAIL = 'mail';
    const TYPE_IP = 'ip';

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
        $bMail = (isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
        $bIp = (isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
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
            $aWhere[self::TYPE_MAIL] = $sMail;
        }
        if ($bCheckIp) {
            $aWhere[self::TYPE_IP] = $sIp;
        }
        if (!$bCheckMail && !$bCheckIp) {
            return false;
        }
        $aInfo = $this->oMapper->Check($aWhere);

        if (DEBUG) {
            error_log('Local Base');
            error_log(serialize($aWhere));
            error_log(serialize($aInfo));
        }
        
        if ($aInfo) {
            $bMail = false;
            $bIp = false;
            foreach ($aInfo as $aItem) {
                if (isset($aItem['content'])) {
                    if ($bCheckMail && $aItem['content'] == $sMail) {
                        $bMail |= ((isset($aItem['result']) && $aItem['result']) ? true : false);
                    } elseif ($bCheckIp && $aItem['content'] == $sIp) {
                        $bIp |= ((isset($aItem['result']) && $aItem['result']) ? true : false);
                    }
                }
            }

            // TODO: Проверка каждого из сервисов по отдельности
/*
            $aCache = array();
            foreach ($aInfo as $aItem) {
                if (isset($aItem['type']) && isset($aItem['service'])) {
                    $aCache[$aItem['service']][$aItem['type']] = ((isset($aItem['result']) && $aItem['result']) ? true : false);
                }
            }
 */
            return array(
                self::TYPE_MAIL => $bMail,
                self::TYPE_IP => $bIp,
            );
        }
        return false;
    }

    public function check_stopforumspam_com($sMail, $sIp, $bCheckMail, $bCheckIp) {
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
        
        if (DEBUG) {
            error_log('stopforumspam.com');
            error_log($sUrl);
            error_log($sAnswer);
        }
        
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
                self::TYPE_MAIL => $bMail,
                self::TYPE_IP => $bIp,
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
        
        if (DEBUG) {
            error_log('botscout.com');
            error_log($sUrl);
            error_log($sAnswer);
        }
        
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
                    self::TYPE_MAIL => $bMail,
                    self::TYPE_IP => $bIp,
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
        
        if (DEBUG) {
            error_log('fspamlist.com');
            error_log($sUrl);
            error_log($sAnswer);
        }
        
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
                self::TYPE_MAIL => $bMail,
                self::TYPE_IP => $bIp,
            );
        }
        return false;
    }

    public function AddMailResult($sMail, $bResult, $iService) {
        $this->oMapper->AddResult(self::TYPE_MAIL, $sMail, $bResult, $iService);
    }

    public function AddIpResult($sIp, $bResult, $iService) {
        $this->oMapper->AddResult(self::TYPE_IP, $sIp, $bResult, $iService);
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
        $bResult = false;
        if (Config::Get('plugin.blacklist.use_stopforumspam_com')) {
            $aResult = $this->check_stopforumspam_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
            $bIp |= (is_array($aResult) && isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
            if ($bCheckMail) {
                $this->AddMailResult($sMail, $bMail, self::SERVICE_STOPFORUMSPAM_COM);
            }
            if ($bCheckIp) {
                $this->AddIpResult($sIp, $bIp, self::SERVICE_STOPFORUMSPAM_COM);
            }
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_botscout_com')) {
            $aResult = $this->check_botscout_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
            $bIp |= (is_array($aResult) && isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
            if ($bCheckMail) {
                $this->AddMailResult($sMail, $bMail, self::SERVICE_BOTSCOUT_COM);
            }
            if ($bCheckIp) {
                $this->AddIpResult($sIp, $bIp, self::SERVICE_BOTSCOUT_COM);
            }
        }
        if (!$bResult && Config::Get('plugin.blacklist.use_fspamlist_com')) {
            $aResult = $this->check_fspamlist_com($sMail, $sIp, $bCheckMail, $bCheckIp);
            $bMail |= (is_array($aResult) && isset($aResult[self::TYPE_MAIL]) ? $aResult[self::TYPE_MAIL] : false);
            $bIp |= (is_array($aResult) && isset($aResult[self::TYPE_IP]) ? $aResult[self::TYPE_IP] : false);
            $bResult = $this->analyse_result($aResult, $bCheckMail, $bCheckIp, $bIpExact);
            if ($bCheckMail) {
                $this->AddMailResult($sMail, $bMail, self::SERVICE_FSPAMLIST_COM);
            }
            if ($bCheckIp) {
                $this->AddIpResult($sIp, $bIp, self::SERVICE_FSPAMLIST_COM);
            }
        }
        return $bResult;
    }
}
?>