<?php
/**
 * Модель ThreadSettings
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Model_ThreadSettings extends XenForo_Model {

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
	 * @param integer $thread_id
	 * @return array|false
	 */
	public function getThreadSettingsByThreadId ($thread_id) {
		return $this->_getDb()->fetchRow('
			SELECT ts.*
			FROM estpd_thread_settings AS ts
                WHERE ts.thread_id = ?
		', array ($thread_id));
	}
    
    
	/**
	 * Получение установок темы по указанному идентификатору
	 * @param integer $thread_id
	 * @return array|false
	 */
    public function getSettingsByThreadId ($thread_id) {
		$row = $this->_getDb()->fetchRow('
			SELECT ts.settings
			FROM estpd_thread_settings AS ts
                WHERE ts.thread_id = ?
		', array ($thread_id));
        
        if ($row) {
            return unserialize ($row['settings']);
        } else {
            return false;
        }
    }
}