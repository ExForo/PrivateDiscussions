<?php
/**
 * Шаблон модели DiscussionThread
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_DataWriter_Discussion_Thread extends XFCP_Esthetic_PD_DataWriter_Discussion_Thread {
    
    
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
    
    
    
    
    protected $_estpd_thread_limits = false;
    protected $_estpd_existing_thread_settings = false;
    
    
    /**
     * Получение массива свойств модели
     * @return  array
     */
	protected function _getFields ( ) {
        $response   = parent::_getFields();
        $response['xf_thread']['estpd_thread_type']         = array('type' => self::TYPE_UINT, 'default' => 0);
        $response['xf_thread']['estpd_allow_invitation']    = array('type' => self::TYPE_BOOLEAN, 'default' => 0);
        return $response;
    }
    
    
    /**
     * Обнаружение приватных тем и установка флага блокировки фидов
     * @return  NULL
     */
    protected function _discussionPreSave ( ) {

        if (XenForo_Application::isRegistered('estpd_thread_privacy_settings')) {
            $privacy_settings = XenForo_Application::get('estpd_thread_privacy_settings');

            /**
             * Подготовка данных к записи
             */
            switch ($privacy_settings['thread_type']) {
            
                /**
                 * Очистка ограничений
                 */
                case 0:

                    $this->set('estpd_thread_type', 0);
                    $this->set('estpd_allow_invitation', 0);

                    if ($this->get('thread_id')) {
                        
                        $thread_id = $this->get('thread_id');
                        
                        /**
                         * Удаление щаписей о участниках дискуссии
                         */
                        $this->_getThreadUserModel()->removeUsersByThreadId($thread_id);
                        
                        /**
                         * Удаление записи ограничения
                         */
                        $existing = $this->_getThreadSettingsModel()->getThreadSettingsByThreadId($thread_id);
                        if (!empty ($existing)) {
                            $dw = XenForo_DataWriter::create('Esthetic_PD_DataWriter_ThreadSettings');
                            $dw->setExistingData($thread_id);
                            $dw->delete();
                        }
                    }
                    
                    break;
                    
                /**
                 * Установка режима приватной темы
                 */
                case 2:

                    if ($this->get('thread_id')) {
                        
                        $thread_id = $this->get('thread_id');
                        
                        if ($this->get('estpd_thread_type') == 2) {
                            
                            $existing = $this->_getThreadSettingsModel()->getThreadSettingsByThreadId($thread_id);
                                
                            if (!empty ($existing)) {
                                $dw = XenForo_DataWriter::create('Esthetic_PD_DataWriter_ThreadSettings');
                                $dw->setExistingData($thread_id);
                                $dw->delete();
                            }
                        }
                    }

                    $this->set('estpd_thread_type', 1);
                    $this->set('estpd_allow_invitation', $privacy_settings['estpd_allow_public_invitation']);
                    
                    break;
                    
                
                /**
                 * Установка режима ограниченной темы
                 */
                case 1:

                    $this->_estpd_thread_limits = array (
                        'posts' => $privacy_settings['posts'],
                        'likes' => $privacy_settings['likes'],
                        'trophies' => $privacy_settings['trophies'],
                        'days' => $privacy_settings['days'],
                        'extended' => $privacy_settings['extended']
                    );
                    
                    $is_valid = Esthetic_PD_Helper_Limits::isValid($this->_estpd_thread_limits);
                    
                    if ($is_valid === null) {
                        $this->error(new XenForo_Phrase ('estpd_error_limitation_rule_seems_to_be_not_effective'));
                        return false;
                    }
                    if ($is_valid === false) {
                        $this->error(new XenForo_Phrase ('estpd_error_wrong_syntax_in_rule'));
                        return false;
                    }
                    
                    if ($this->get('thread_id')) {
                        
                        $thread_id = $this->get('thread_id');
                        
                        if ($this->get('estpd_thread_type') == 1) {
                            $this->_getThreadUserModel()->removeUsersByThreadId($thread_id);
                        }
                        
                        $this->_estpd_existing_thread_settings = $this->_getThreadSettingsModel()->getThreadSettingsByThreadId($thread_id);
                    }

                    $this->set('estpd_thread_type', 2);

                    break;
                
                default:
            }
        }

        return parent::_discussionPreSave();
    }
    
    
    /**
     * Дополнительные операции после сохранения модели в БД
     * @return  NULL
     */
	protected function _discussionPostSave ( ) {
    
        /**
         * Запись правил доступа к теме
         */
        if (XenForo_Application::isRegistered('estpd_thread_privacy_settings')) {
            
            $thread_id = $this->get('thread_id');
            
            if ($this->get('estpd_thread_type') == 2) {
                
                /**
                 * Запись параметров доступа к ограниченной теме
                 */
                $dw = XenForo_DataWriter::create('Esthetic_PD_DataWriter_ThreadSettings');
                if (empty ($this->_estpd_existing_thread_settings)) {
                    $dw->set('thread_id', $thread_id);
                } else {
                    $dw->setExistingData($thread_id);
                }
                $dw->set('settings', $this->_estpd_thread_limits);
                $dw->save();
                
            } else if ($this->get('estpd_thread_type') == 1) {
                
                /**
                 * Запись параметров доступа к приватной теме
                 */
                $user_id = XenForo_Visitor::getUserId();
                
                if ($this->get('user_id')) {
                    $this->_getThreadUserModel()->insertThreadUsers($thread_id, $this->get('user_id'), 1);
                    
                    /**
                     * Если приватное обсуждение создано модератором - добавить модератора в список участников
                     */
                    if ($user_id != $this->get('user_id')) {
                        $this->_getThreadUserModel()->insertThreadUsers($thread_id, $user_id, 0);
                    }
                }
            }
        }
        
        $response = parent::_discussionPostSave();
        
        if (!XenForo_Application::isRegistered('estPD_last_thread')) {
            return $response;
        }
        
        $last_thread = XenForo_Application::get('estPD_last_thread');
        if (isset ($last_thread['required']) && $last_thread['required'] === true) {
            $last_thread = array (
                'required'      => false,
                'thread'        => $this
            );
            
            XenForo_Application::set('estPD_last_thread', $last_thread);
        }
        
		return $response;
	}
    
    
    /**
     * Дополнительные операции после удадения темы
     * @return  NULL
     */
    protected function _discussionPostDelete ( ) {
        
        $thread_id = $this->get('thread_id');
        $thread_id_quoted = $this->_db->quote($thread_id);
        
        $this->_db->delete('estpd_thread_settings', "thread_id = $thread_id_quoted");
        $this->_db->delete('estpd_thread_users', "thread_id = $thread_id_quoted");

        return parent::_discussionPostDelete();
    }

    
    /**
     * Returns the ThreadUser model
     * @return Esthetic_PD_Model_ThreadUser
     */
    protected function _getThreadUserModel ( ) {
        return $this->getModelFromCache('Esthetic_PD_Model_ThreadUser');
    }
    
    
    /**
     * Returns the ThreadSettings model
     * @return Esthetic_PD_Model_ThreadSettings
     */
    protected function _getThreadSettingsModel ( ) {
        return $this->getModelFromCache('Esthetic_PD_Model_ThreadSettings');
    }
}