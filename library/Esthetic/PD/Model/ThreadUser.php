<?php
/**
 * Модель ThreadUser
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Model_ThreadUser extends XenForo_Model {

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
	 * Получение записи по указанным параметрам
	 * @param integer $userId
	 * @param integer $threadId
	 * @return array|false
	 */
	public function getThreadUserByThreadId ($userId, $threadId) {
		return $this->_getDb()->fetchRow('
			SELECT *
			FROM estpd_thread_users
			WHERE user_id = ?
				AND thread_id = ?
		', array($userId, $threadId));
	}
    
	/**
	 * Получение массива пользователей, присоедененных к текущему обсуждению
	 * @param integer $threadId
	 * @return array|false
	 */
	public function getThreadRecepients ($threadId) {
		return $this->_getDb()->fetchAll('
			SELECT *
			FROM estpd_thread_users
			WHERE thread_id = ?
                AND is_ts = 0
                ORDER BY `user_id` ASC
		', array((int)$threadId));
	}
    
	/**
	 * Получение массива всех пользователей, присоедененных к текущему обсуждению
	 * @param integer $thread_id
	 * @return array|false
	 */
	public function getAllThreadRecepients ($thread_id) {
		return $this->fetchAllKeyed('
			SELECT tu.user_id, tu.is_ts, u.username
                FROM `estpd_thread_users` tu 
                JOIN `xf_user` u
			WHERE u.user_id = tu.user_id
                AND tu.thread_id = ?
                ORDER BY tu.is_ts DESC, tu.user_id ASC
		', 'user_id', array ((int)$thread_id));
	}
    
	/**
	 * Получение информации о пользователе, как участнике дискуссии
	 * @param integer $threadId
     * @param integer $userId
	 * @return array|false
	 */
	public function getUserAsThreadRecepient ($threadId, $userId) {
		return $this->_getDb()->fetchRow('
			SELECT u.*
                FROM `estpd_thread_users` tu 
                JOIN `xf_user` u
			WHERE u.user_id = tu.user_id
                AND tu.thread_id = ?
                AND u.user_id = ?
		', array((int)$threadId, (int)$userId));
	}

	/**
	 * Исключение пользователя из списка доступа к теме
	 * @param integer $threadId
     * @param integer $userId
	 * @return array|false
	 */
	public function removeUserFromThread ($thread_id, $user_id) {
        $this->_getDb()->query('
			DELETE tw
                FROM `xf_thread_watch` tw
                WHERE tw.thread_id = ?
                    AND tw.user_id = ?
		', array((int)$thread_id, (int)$user_id));
		$this->_getDb()->query('
			DELETE tu
                FROM `estpd_thread_users` tu 
                JOIN `xf_user` u
			WHERE u.user_id = tu.user_id
                AND tu.thread_id = ?
                AND u.user_id = ?
		', array((int)$thread_id, (int)$user_id));
        return true;
	}
    
    
	/**
	 * Удаление участников приватного обсуждения
	 * @param integer $thread_id
	 * @return array|false
	 */
    public function removeUsersByThreadId ($thread_id) {
        $this->_getDb()->query('
            DELETE t
                FROM `estpd_thread_users` t
                WHERE t.thread_id = ?
        ', array ($thread_id));
    }
    
    
    /**
     * Добавление пользователя в список участников обсуждения
     * @param   integer     $thread_id
     * @param   array       $user_ids
     * @param   boolean     $is_ts
     * @return  integer
     */
    public function insertThreadUsers ($thread_id, $user_ids, $is_ts = false) {
        
        if (is_int ($user_ids)) {
            $user_ids = array (0 => $user_ids);
        }
        
        $rows_affected = 0;
        
        $db = $this->_getDb();
        
        if (is_array ($user_ids) && !empty ($user_ids)) {
            XenForo_Db::beginTransaction($db);
            
            foreach ($user_ids as $user_id) {
                $rows_affected += (int)($db->query('
                    INSERT INTO estpd_thread_users
                        (thread_id, user_id, is_ts)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE is_ts = VALUES(is_ts)
                ', array ($thread_id, $user_id, (int)$is_ts))->rowCount());
            }
            
            XenForo_Db::commit($db);
        }
        
        return $rows_affected;
    }
    
    
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel() {
		return $this->getModelFromCache('XenForo_Model_User');
	}

}