<?php
/**
 * Шаблон модели ThreadUser
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_DataWriter_ThreadUser extends XenForo_DataWriter {

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
     * Получение массива свойств модели
     * @return  array
     */
	protected function _getFields () {
		return array (
			'estpd_thread_users'   => array(
				'user_id'           => array ('type' => self::TYPE_UINT,    'required' => true),
				'thread_id'         => array ('type' => self::TYPE_UINT,    'required' => true),
				'is_ts'             => array ('type' => self::TYPE_BOOLEAN, 'default' => 0)
			)
		);
	}

    /**
     * Получение информации о существующих данных
     * @param   array   $data
     * @return  array
     */
	protected function _getExistingData ($data) {
		if (!is_array($data)) {
			return false;
		} else if (isset($data['user_id'], $data['thread_id'])) {
			$userId = $data['user_id'];
			$threadId = $data['thread_id'];
		} else if (isset($data[0], $data[1])) {
			$userId = $data[0];
			$threadId = $data[1];
		} else {
			return false;
		}

		return array('estpd_thread_users' => $this->_getThreadUserModel()->getThreadUserByThreadId($userId, $threadId));
	}

    /**
     * Получение строки обновления записи БД
     * @param   string  $tableName
     * @return  string
     */
	protected function _getUpdateCondition ($tableName) {
		return 'user_id = ' . $this->_db->quote($this->getExisting('user_id'))
			. ' AND thread_id = ' . $this->_db->quote($this->getExisting('thread_id'));
	}

    /**
     * Получение модели ThreadUser
     * @return  Esthetic_PD_Model_ThreadUser
     */
	protected function _getThreadUserModel () {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadUser');
	}
}