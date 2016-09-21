<?php
/**
 * Модель Search
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Model_User extends XFCP_Esthetic_PD_Model_User {

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
     * Поиск детальной информации о приватных темах, участником которых является указанны пользователь
     * @param   array|int       $user
     * @return  array|false
     */
    public function getUsersPrivateThreadsSortedArray ($user) {
    
        if (is_int ($user)) {
            $user_id = $user;
        } else if (isset ($user['user_id'])) { 
            $user_id = $user['user_id'];
        } else {
            return false;
        }
        
        $threads = $this->_getDb()->fetchAll(
            'SELECT t.*, u.*, IF(u.username IS NULL, t.username, u.username) AS username,
                IF(tr.thread_read_date > 1349666755, tr.thread_read_date, 1349666755) AS thread_read_date,
                IF(tup.user_id IS NULL, 0, tup.post_count) AS user_post_count
            
                FROM `estpd_thread_users` AS tu 
                LEFT JOIN `xf_thread` AS t ON (t.thread_id = tu.thread_id)
                LEFT JOIN `xf_user` AS u ON (u.user_id = t.user_id)
                
                LEFT JOIN xf_thread_read AS tr ON (tr.thread_id = t.thread_id)
                LEFT JOIN xf_thread_user_post AS tup ON (tup.thread_id = t.thread_id)
                
                WHERE tu.user_id = ?
                    AND t.discussion_open = 1
                    AND t.discussion_state = \'visible\'
                    AND t.estpd_thread_type = 1
                GROUP BY tu.thread_id
                ORDER BY t.last_post_date DESC',
            array ($user_id)
        );

        if (!$threads) {
            return false;
        }
        
        foreach ($threads as &$thread) {
            $thread['lastPostInfo'] = array (
                'post_date'             => isset ($thread['last_post_date']) ? (int)$thread['last_post_date'] : 0,
                'post_id'               => isset ($thread['last_post_id']) ? (int)$thread['last_post_id'] : 0,
                'user_id'               => isset ($thread['last_post_user_id']) ? (int)$thread['last_post_user_id'] : 0,
                'username'              => isset ($thread['last_post_username']) ? (string)$thread['last_post_username'] : '',
            );
        }
        
        return $threads;
    }
}