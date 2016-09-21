<?php
/**
 * Модель Thread
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Model_Thread extends XFCP_Esthetic_PD_Model_Thread {

    /**
     * Номер версии скрипта
     * @var     int
     */
    public static $est_version_id   = 1120;
    
    /**
     * Идентификатор продукта
     * @var     string
     */
    public static $est_addon_id     = 'estpd';
    
    
    
    /**
     * Поиск прав доступа к темам, идентификаторы которых указанны в массиве
     * @param   array   $thread_ids
     * @return  array
     */
    public function getAccessSettingsByIds ($thread_ids) {
        
        if (empty ($thread_ids)) {
            return array ( );
        }
        
        sort ($thread_ids);
        
        if (count ($thread_ids) == 1) {
            $where_cause = 'thread.thread_id = ' . $thread_ids[0];
        } else {
            $where_cause = sprintf ('thread.thread_id IN (%s)', implode (', ', $thread_ids));
        }
        
        $values = $this->fetchAllKeyed('
            SELECT thread.thread_id,
                thread.node_id,
                thread.estpd_thread_type,
                node.estpd_node_type,
                (SELECT IF(thread.estpd_thread_type = 1, 
                    (SELECT GROUP_CONCAT(users.user_id ORDER BY users.user_id ASC SEPARATOR \',\') FROM `estpd_thread_users` AS users WHERE users.thread_id = thread.thread_id GROUP BY users.thread_id),
                    (SELECT settings.settings FROM `estpd_thread_settings` AS settings WHERE settings.thread_id = thread.thread_id LIMIT 1))) AS access_rule
            FROM `xf_thread` AS thread
                LEFT JOIN `xf_node` AS node ON (node.node_id = thread.node_id)
                WHERE ' . $where_cause . '
                ORDER BY thread.thread_id
        ', 'thread_id');
        
        if (!empty ($values)) {
            foreach ($values as &$value) {
                if ($value['estpd_thread_type'] == 1) {
                    $value['access_rule'] = explode (',', $value['access_rule']);
                    if (count ($value['access_rule'])) {
                        $value['access_rule'] = array_map ('intval', $value['access_rule']);
                    }
                } else if ($value['estpd_thread_type'] == 2) {
                    $value['access_rule'] = unserialize ($value['access_rule']);
                }
            }
        }
        
        return $values;
    }
}