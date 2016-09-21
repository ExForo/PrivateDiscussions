<?php
/**
 * Обработчик событий вызова хуков
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_Hooks {

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
	 * @var array
	 */
	protected static $_modelCache = array ( );
    

    /**
     * Обработка хуков
     * @param   string                      $hookName
     * @param   string                      &$contents
     * @param   array                       $hookParams
     * @param   XenForo_Template_Abstract   $template
     * @return  bool
     */
    public static function listen ($hookName, &$contents, array $hookParams, XenForo_Template_Abstract $template) {
        
        $options = XenForo_Application::get('options');
        
        /**
         * Если дополнение не активно, приступить к стандартному выполнению
         */
        if (!$options->estPD_enabled) {
            return true;
        }
        
        switch ($hookName) {
            case 'thread_create_fields_extra':
                self::caseThreadCreate ($contents, $hookParams);
                break;
            case 'page_container_head':
                self::casePageContainerHead ($contents, $hookParams);
                break;
            case 'navigation_visitor_tab_links1':
                self::caseNavigationVisitorTabLinks1 ($contents, $hookParams);
                break;
            case 'admin_forum_edit_forum_options':
                self::caseAdminForumEditForumOptions ($contents, $hookParams);
                break;
            default:
        }
        
        return true;
    }

    
    /**
     * Обработка формы создания нового треда
     * @param   string  &$contents
     * @param   array   $params
     * @return  bool
     */
    public static function caseThreadCreate (&$contents, $params) {
    
        $visitor = XenForo_Visitor::getInstance();
        $options = XenForo_Application::get('options');

        /**
         * Проверка возможности выполнения
         */
        if (!isset ($params['forum']) || !is_array ($params['forum'])) 
            return false;
        if (empty ($params['forum']['estpd_node_type'])) 
            return false;
        
        $node_type = (int)$params['forum']['estpd_node_type'];
        
        $can_set_private = ($node_type & 1) == 1;
        $can_set_limited = ($node_type & 2) == 2;
        
        if ($options->estpd_allow_quoting == 0) {
            $can_set_limited = false;
        } else if ($options->estpd_allow_quoting == 2) {
            $can_set_limited = true;
        }

        if (!$visitor->hasNodePermission($params['forum']['node_id'], 'estpd_can_create') && !$visitor->hasNodePermission($params['forum']['node_id'], 'estpd_can_manage')) {
            $can_set_private = false;
        }
        if (!$visitor->hasNodePermission($params['forum']['node_id'], 'estpd_can_set_limits') && !$visitor->hasNodePermission($params['forum']['node_id'], 'estpd_can_manage')) {
            $can_set_limited = false;
        }
        
        if (!$can_set_private && !$can_set_limited) {
            return false;
        }
        
        $template = new XenForo_Template_Public ('estpd_access_settings_fields', array (
            'forum' => $params['forum'],
            'can_set_limited' => $can_set_limited,
            'can_set_private' => $can_set_private,
            'is_creation_form' => true
        ));
        $contents .= $template->render();
        
        return true;
    }
    
    
    /**
     * Обработка вывода страницы
     * @param   string  &$contents
     * @param   array   $params
     * @return  bool
     */
    public static function casePageContainerHead ($contents, $params) {
        if (!XenForo_Application::isRegistered ('estPD_private_threads')) {
            return false;
        }
        
        $template = new XenForo_Template_Public ('estPD_thread_list', array ( ));
        $contents .= $template->render();
        
        return true;
    }
    
    
    /**
     * Отображение ссылки на страницу персональных приватных тем в меню профиля
     * @param   string  &$contents
     * @param   array   $params
     * @return  bool
     */
    protected static function caseNavigationVisitorTabLinks1 (&$contents, $params) {
        $contents .= sprintf (
            '<li><a href="%s">%s</a></li>',
            XenForo_Link::buildPublicLink('account/private-discussions'),
            new XenForo_Phrase ('estPD_private_discussions')
        );
        return true;
    }
    
    
    /**
     * Отображение ссылки на страницу персональных приватных тем в меню профиля
     * @param   string  &$contents
     * @param   array   $params
     * @return  bool
     */
    protected static function caseAdminForumEditForumOptions (&$contents, $params) {
        $contents .= '<!--@estPD:pane-->';
    }
    
    
	/**
	 * Gets the specified model object from the cache. If it does not exist,
	 * it will be instantiated.
	 * @param string $class Name of the class to load
	 * @return XenForo_Model 0454
	 */
	public static function getModelFromCache($class) {
		if (!isset(self::$_modelCache[$class])) {
			self::$_modelCache[$class] = XenForo_Model::create($class);
		}
		return self::$_modelCache[$class];
	}
    
	/**
	 * Returns the user model
	 * @return XenForo_Model_User
	 */
	protected static function _getThreadUserModel() {
		return self::getModelFromCache('Esthetic_PD_Model_ThreadUser');
	}
}