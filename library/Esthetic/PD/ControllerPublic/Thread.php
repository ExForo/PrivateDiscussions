<?php
/**
 * Контроллер "Thread"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ControllerPublic_Thread extends XFCP_Esthetic_PD_ControllerPublic_Thread {

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
	 * Действие "Index"
	 * @return XenForo_ControllerResponse_View
	 */
	public function actionIndex ( ) {
        
        $response           = parent::actionIndex();

		$thread_id          = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$visitor            = XenForo_Visitor::getInstance();
        
        if (empty ($response->params['forum']) || empty ($response->params['thread'])) {
            return $response;
        }
        
        $ftp_helper = $this->getHelper('ForumThreadPost');
        
        $this->_updateViewParams($response->params);
        
        if (empty ($response->params['estpd'])) {
            return $response;
        }
        
        $settings = $response->params['estpd'];
        
        $accessible = false;
        $limit_type = 'limited';
        $result = false;
        
        if (!$settings['is_private'] && !$settings['is_limited']) {
            $accessible = true;
        } else {
            if ($settings['is_private']) {
                if ($this->_getThreadUserModel()->getUserAsThreadRecepient($thread_id, $visitor['user_id']) !== false || $visitor->hasNodePermission($response->params['forum']['node_id'], 'estpd_can_manage')) {
                    $accessible = true;
                    $limit_type = 'private';
                }
            }
            
            
            if ($settings['is_limited']) {
                $result = Esthetic_PD_Helper_Limits::apply($settings['limits']);
                if ($result['match']) {
                    $accessible = true;
                }
                $response->params['estpd']['limits'] = $result;
            }
        }
        
        if ($visitor['user_id'] == $response->params['thread']['user_id'] || $visitor->hasNodePermission($response->params['forum']['node_id'], 'estpd_can_manage')) {
            $accessible = true;
        }
        
        if (!$accessible) {
            return $this->responseView('Esthetic_PD_ViewPublic_Private_AccessLimit', 'estpd_thread_access_limit', array (
                'forum'             => $response->params['forum'],
                'thread'            => $response->params['thread'],
                'nodeBreadCrumbs'   => $ftp_helper->getNodeBreadCrumbs($response->params['forum']),
                'author'            => $this->_getThreadUserModel()->getUserAsThreadRecepient($thread_id, $response->params['thread']['user_id']),
                'limit_type'        => $limit_type,
                'message'           => empty ($result['message']) ? '' : $result['message']
            ));
        }
        
        return $response;
    }
    
    
	/**
	 * Действие "Preview"
	 * @return XenForo_ControllerResponse_View
	 */
    public function actionPreview ( ) {
        
        $response = parent::actionPreview ();
        
		$thread_id = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$visitor = XenForo_Visitor::getInstance();
		$ftp_helper = $this->getHelper('ForumThreadPost');
		$thread_fetch_options = array (
			'readUserId' => $visitor['user_id'],
			'watchUserId' => $visitor['user_id'],
			'join' => XenForo_Model_Thread::FETCH_AVATAR
		);
		$forum_fetch_options  = array (
			'readUserId' => $visitor['user_id']
		);
		list ($thread, $forum) = $ftp_helper->assertThreadValidAndViewable($thread_id, $thread_fetch_options, $forum_fetch_options);
        
        if ($visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage') || (int)$visitor['user_id'] == (int)$thread['user_id']) {
            return $response;
        }
        
        if (empty ($forum['estpd_node_type'])) {
            $node_type = 0;
        } else {
            $node_type = (int)$forum['estpd_node_type'];
        }
        
        $can_be_private = ($node_type & 1) == 1;
        $can_be_limited = ($node_type & 2) == 2;
        
        $limited = false;
        $message = false;
        
        $author = array (
            'user_id' => $thread['user_id'],
            'username' => $thread['username']
        );
        
        switch ($thread['estpd_thread_type']) {
            
                case 2: // limited
                    
                    $settings = $this->_getThreadSettingsModel()->getSettingsByThreadId($thread_id);
                    
                    if (empty ($settings)) {
                        break;
                    }
                    
                    $rule = Esthetic_PD_Helper_Limits::apply($settings);
                    if (!$rule['match'] && $can_be_limited) {
                        $limited = true;
                        $message = $rule['message'];
                    }
                    
                    break;
                    
                case 1: // private
                    
                    $members = $this->_getThreadUserModel()->getAllThreadRecepients($thread_id);
                    
                    if (empty ($members)) {
                        break;
                    }
                    
                    /**
                     * Проверка параметров темы
                     */
                    if ($can_be_private && !in_array ($visitor['user_id'], array_keys ($members))) {
                        $limited = true;
                        $author = $this->_getThreadUserModel()->getUserAsThreadRecepient($thread_id, $thread['user_id']);
                    }
                    
                    break;
                
                default:
        }
        
        if ($limited) {
            return $this->responseView('Esthetic_PD_ViewPublic_Private_AccessLimit', 'estpd_thread_access_limit', array (
                'forum'             => $forum,
                'thread'            => $thread,
                'message'           => $message,
                'nodeBreadCrumbs'   => $ftp_helper->getNodeBreadCrumbs($forum),
                'author'            => $author
            ));
        }

        return $response;
    }
    
    
    /**
     * Добавление необходимых данных о параметрах доступа
     * @param   array       &$params
     * @return  null
     */
    protected function _updateViewParams (&$params) {
        
        $options = XenForo_Application::get('options');
        $visitor = XenForo_Visitor::getInstance();
        $forum = $params['forum'];
        $thread = $params['thread'];

        if (empty ($forum['estpd_node_type'])) {
            $node_type = 0;
        } else {
            $node_type = (int)$forum['estpd_node_type'];
        }
        
        $private_allowed = ($node_type & 1) == 1;
        $limited_allowed = ($node_type & 2) == 2;
        
        $params['estpd'] = array (
            'can_manage_privacy'    => false,
            'can_manage_limits'     => false,
            'is_private'            => false,
            'is_limited'            => false
        );
        
        if ($visitor['user_id'] == $thread['user_id'] || $visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')) {

            $params['estpd']['can_manage_privacy'] = ($node_type & 1) == 1;
            $params['estpd']['can_manage_limits'] = ($node_type & 2) == 2;
            
            if ($options->estpd_allow_quoting == 0) {
                $params['estpd']['can_manage_limits'] = false;
                $limited_allowed = false;
            } else if ($options->estpd_allow_quoting == 2) {
                $params['estpd']['can_manage_limits'] = true;
                $limited_allowed = true;
            }
            
            if (!$visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage') && !$visitor->hasNodePermission($forum['node_id'], 'estpd_can_create')) {
                $params['estpd']['can_manage_privacy'] = false;
            }
            if (!$visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage') && !$visitor->hasNodePermission($forum['node_id'], 'estpd_can_set_limits')) {
                $params['estpd']['can_manage_limits'] = false;
            }
        }
        
        if (empty ($thread['estpd_thread_type'])) {
            return null;
        }
        
        if ($private_allowed) {
            
            if ($thread['estpd_thread_type'] == 1) {
                $params['estpd']['is_private'] = true;
                $params['estpd']['can_manage_limits'] = false;
            }
            
            if (($visitor['user_id'] == $thread['user_id'] && $visitor->hasNodePermission($forum['node_id'], 'estpd_can_create')) || 
                $visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')
            ) {
                $params['estpd']['can_manage_privacy'] = true;
            }
            
            if ($params['estpd']['is_private']) {
                $_recepients = $this->_getThreadUserModel()->getAllThreadRecepients($thread['thread_id']);
                $recepients = array ( );
                
                foreach ($_recepients as $recepient) {
                    $recepients[] = $recepient;
                }
                
                $params['estpd'] += array (
                    'recepients'        => $recepients,
                    'recepients_count'  => count($recepients) - 1,
                    'can_add_users'     => $params['estpd']['can_manage_privacy'] || $thread['estpd_allow_invitation']
                );
            }
        } else {
            $params['estpd']['is_private'] = false;
        }
        
        if ($limited_allowed) {
            if ($thread['estpd_thread_type'] == 2) {
                $params['estpd']['is_limited'] = true;
                $params['estpd']['limits'] = $this->_getThreadSettingsModel()->getSettingsByThreadId($thread['thread_id']);
            }
        } else {
            $params['estpd']['is_limited'] = false;
        }
    }

    
	/**
	 * Возвращает модель ThreadUser
	 * @return Esthetic_PD_Model_ThreadUser
	 */
	protected function _getThreadUserModel ( ) {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadUser');
	}
    
    
	/**
	 * Возвращает модель ThreadSettings
	 * @return Esthetic_PD_Model_ThreadSettings
	 */
	protected function _getThreadSettingsModel ( ) {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadSettings');
	}
}