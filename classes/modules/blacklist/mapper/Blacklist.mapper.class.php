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

class PluginBlacklist_ModuleBlacklist_MapperBlacklist extends Mapper {
    public function AddMailResult($sMail, $bResult) {
        $sDate = date("Y-m-d H:i:s");
        $iResult = ($bResult ? 1 : 0);
        $sql = "INSERT INTO " . Config::Get('db.table.blacklist') . "
                    (content, type, date, result)
                VALUES
                    (?, ?, ?, ?d)
                ON DUPLICATE KEY UPDATE
                    date = ?,
                    result = ?d
                ";
        if ($this->oDb->query($sql,$sMail,'mail',$sDate,$iResult,$sDate,$iResult)) {
            return true;
        }
        return false;
    }

    public function AddIpResult($sIp, $bResult) {
        $sDate = date("Y-m-d H:i:s");
        $iResult = ($bResult ? 1 : 0);
        $sql = "INSERT INTO " . Config::Get('db.table.blacklist') . "
                    (content, type, date, result)
                VALUES
                    (?, ?, ?, ?d)
                ON DUPLICATE KEY UPDATE
                    date = ?,
                    result = ?d
                ";
        if ($this->oDb->query($sql,$sIp,'ip',$sDate,$iResult,$sDate,$iResult)) {
            return true;
        }
        return false;
    }

    public function Check($aWhere) {
        $sWhere = '';
        if (is_array($aWhere)) {
            foreach($aWhere as $sType => $sContent) {
                if (!empty($sWhere)) {
                    $sWhere .= " OR ";
                }
                $sWhere .= "(content = '" . $sContent . "' AND type = '" . $sType . "')";
            }
        }
        if (!empty($sWhere)) {
            $sDate = date("Y-m-d H:00:00",time()-Config::Get('plugin.blacklist.recheck_time'));
            $sql = "SELECT * FROM " . Config::Get('db.table.blacklist') . " WHERE (" . $sWhere . ") AND date >=  '" . $sDate . "'";
            if ($aRow = $this->oDb->select($sql,$sMail,'mail',$sDate,$iResult,$sDate,$iResult)) {
                return $aRow;
            }
        }
        return false;
    }
}