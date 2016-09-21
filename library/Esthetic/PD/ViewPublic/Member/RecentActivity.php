<?php
/**
 * Рендер шаблона "Esthetic_PD_ViewPublic_Member_RecentActivity"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ViewPublic_Member_RecentActivity extends XFCP_Esthetic_PD_ViewPublic_Member_RecentActivity {
    
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
     * Обработка данных вывода страницы
     * @return  NULL
     */
	public function renderHtml ( ) { 
        $this->_updateFeedByPermissions();
        return parent::renderHtml();
    }
    
    
    /**
     * Обработка вывода Ajax-запросов
     * @return  NULL
     */
    public function renderJson ( ) { 
        $this->_updateFeedByPermissions();
        return parent::renderJson();
    }
    
    
    /**
     * Обработка данных фида новостей
     * @param       array       $feed
     * @return      boolean
     */
    protected function _updateFeedByPermissions ( ) {
        
        if (empty ($this->_params['newsFeed'])) {
            return false;
        }
        
        $visitor = XenForo_Visitor::getInstance();
        $options = XenForo_Application::getOptions();
        
        /**
         * Парсинг идентификаторов контента
         */
        $threads = array ( );
        foreach ($this->_params['newsFeed'] as $feed) {
            if (empty ($feed['content'])) {
                continue;
            }
            if (!empty ($feed['content']['thread_id'])) {
                $threads[$feed['content']['thread_id']] = $feed['content']['thread_id'];
            }
        }
        
        if (!empty ($threads)) {
            $access_settings = XenForo_Model::create('XenForo_Model_Thread')->getAccessSettingsByIds($threads);
        } else {
            $access_settings = array ( );
        }
        
        foreach ($this->_params['newsFeed'] as &$feed) {
            
            if (empty ($feed['content_type']) || ($feed['content_type'] != 'post' && $feed['content_type'] != 'thread')) {
                continue;
            }
            if (empty ($feed['content'])) {
                continue;
            }
            if (empty ($access_settings[$feed['content']['thread_id']])) {
                continue;
            }
            
            $settings = $access_settings[$feed['content']['thread_id']];
            $access_rule = $settings['access_rule'];
            
            /**
             * Если есть права менеджера узла - продолжить
             */
            if ($visitor->hasNodePermission($settings['node_id'], 'estpd_can_manage')) {
                continue;
            }
            
            if (empty ($settings['estpd_node_type'])) {
                $node_type = 0;
            } else {
                $node_type = (int)$settings['estpd_node_type'];
            }
            
            $can_be_private = ($node_type & 1) == 1;
            $can_be_limited = ($node_type & 2) == 2;
            
            switch ($settings['estpd_thread_type']) {
                
                case 2: // limited
                
                    $result = Esthetic_PD_Helper_Limits::apply($settings['access_rule']);
                    if (!$result['match'] && $can_be_limited) {
                        $feed['content']['message'] = new XenForo_Phrase ('estpd_limited_content_warning');
                        $feed['content']['private'] = true;
                    }
                    
                    break;
                    
                case 1: // private
                
                    /**
                     * Список пользователей не обнаружен, открыть тему для публичного доступа
                     */
                    if (empty ($access_rule)) {
                        break;
                    }
                    
                    /**
                     * Проверка параметров темы
                     */
                    if ($can_be_private && !in_array ($visitor['user_id'], $access_rule)) {
                        $feed['content']['message'] = new XenForo_Phrase ('estpd_private_content_warning');
                        $feed['content']['private'] = true;
                    }
                    
                    break;
                
                default:
            }
        }
    }
}