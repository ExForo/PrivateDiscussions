<?php
/**
 * Модель Search
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Model_Search extends XFCP_Esthetic_PD_Model_Search {

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
     * Подготовка для отображения результатов поиска
     * @param   array       $results
     * @param   array|null  $viewing_user
     * @return  array Keys: results, handlers
     */
    public function getSearchResultsForDisplay (array $results, array $viewing_user = null) {
    
        $visitor    = XenForo_Visitor::getInstance();
        $results    = parent::getSearchResultsForDisplay($results, $viewing_user);
        
        if (empty ($results['results'])) {
            return $results;
        }
        
        
        /**
         * Парсинг идентификаторов контента
         */
        $threads = array ( );
        foreach ($results['results'] as $result) {
            if (empty ($result['content'])) {
                continue;
            }
            if (!empty ($result['content']['thread_id'])) {
                $threads[$result['content']['thread_id']] = $result['content']['thread_id'];
            }
        }
        
        if (!empty ($threads)) {
            $access_settings = $this->_getThreadModel()->getAccessSettingsByIds($threads);
        } else {
            $access_settings = array ( );
        }
        
        foreach ($results['results'] as &$result) {
            
            if (empty ($result['content']) || empty ($result['content']['thread_id'])) {
                continue;
            }
            if (empty ($access_settings[$result['content']['thread_id']])) {
                continue;
            }
            
            $settings = $access_settings[$result['content']['thread_id']];
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
                
                    $rule = Esthetic_PD_Helper_Limits::apply($settings['access_rule']);
                    if (!$rule['match'] && $can_be_limited) {
                        $result['content']['message'] = new XenForo_Phrase ('estpd_limited_content_warning');
                        $result['content']['private'] = true;
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
                        $result['content']['message'] = new XenForo_Phrase ('estpd_private_content_warning');
                        $result['content']['private'] = true;
                    }
                    
                    break;
                
                default:
            }
        }
        
        return $results;
    }
    
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel ( ) {
		return $this->getModelFromCache('XenForo_Model_User');
	}
    
    
	/**
	 * @return XenForo_Model_Thread
	 */
	protected function _getThreadModel ( ) {
		return $this->getModelFromCache('XenForo_Model_Thread');
	}
}