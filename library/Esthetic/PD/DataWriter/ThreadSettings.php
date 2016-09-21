<?php
/**
 * Шаблон модели ThreadUser
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_DataWriter_ThreadSettings extends XenForo_DataWriter {

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
			'estpd_thread_settings' => array(
				'thread_id'         => array ('type' => self::TYPE_UINT, 'required' => true),
				'settings'          => array ('type' => self::TYPE_SERIALIZED, 'default' => ''),
                'preview_message'   => array ('type' => self::TYPE_STRING, 'default' => '')
			)
		);
	}

    /**
     * Получение информации о существующих данных
     * @param   array   $data
     * @return  array
     */
	protected function _getExistingData ($data) {
        
        $thread_id = null;
        
        if (is_array ($data)) {
            if (!empty ($data['thread_id'])) {
                $thread_id = $data['thread_id'];
            } elseif (isset($data[0])) {
                $thread_id = $data[0];
            }
        } elseif (is_int ($data)) {
            $thread_id = (int)$data;
        }
        
        if (empty ($thread_id)) {
            return false;
        }

		return array('estpd_thread_settings' => $this->_getThreadSettingsModel()->getThreadSettingsByThreadId($thread_id));
	}

    /**
     * Получение строки обновления записи БД
     * @param   string  $table_name
     * @return  string
     */
	protected function _getUpdateCondition ($table_name) {
		return 'thread_id = ' . $this->_db->quote($this->getExisting('thread_id'));
	}

    /**
     * Получение модели ThreadSettings
     * @return  Esthetic_PD_Model_ThreadSettings
     */
	protected function _getThreadSettingsModel ( ) {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadSettings');
	}
}