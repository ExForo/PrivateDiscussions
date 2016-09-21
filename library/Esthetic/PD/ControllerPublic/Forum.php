<?php
/**
 * Контроллер "Forum"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ControllerPublic_Forum extends XFCP_Esthetic_PD_ControllerPublic_Forum {


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
     * Дополнительные установки диспетчера
     */
    protected function _preDispatch ($action) {
        
        $response = parent::_preDispatch($action);
        
        if (false == $this->_input->filterSingle('estPD_make_private', XenForo_Input::UINT)) {
            return $response;
        }
        
		$forum_id       = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
		$forum_name     = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
		$ftp_helper     = $this->getHelper('ForumThreadPost');
        
        if (!$forum_id && !$forum_name) {
            return $response;
        }
        
		if (!$forum = $ftp_helper->assertForumValidAndViewable($forum_id ? $forum_id : $forum_name)) {
            return $response;
        }

        if (!isset ($forum['node_id'], $forum['estpd_node_type'])) {
            return $response;
        }
        
        return $response;
    }


	/**
	 * Добавление нового треда
	 * @return XenForo_ControllerResponse_View
	 */
	public function actionAddThread ( ) {
        
        $this->_assertPostOnly();
        
        if ($this->_input->filterSingle('thread_type', XenForo_Input::UINT) > 0) {
            
            $privacy_settings = $this->_input->filter(array (
                'thread_type' => Xenforo_Input::UINT,
                'estpd_allow_public_invitation' => XenForo_Input::UINT,
                'posts' => XenForo_Input::UINT,
                'likes' => XenForo_Input::UINT,
                'trophies' => XenForo_Input::UINT,
                'days' => XenForo_Input::UINT,
                'extended' => XenForo_Input::STRING
            ));
            
            $forum_id       = $this->_input->filterSingle('node_id', XenForo_Input::UINT);
            $forum_name     = $this->_input->filterSingle('node_name', XenForo_Input::STRING);
            $ftp_helper     = $this->getHelper('ForumThreadPost');
            $visitor        = XenForo_Visitor::getInstance();
            $options        = XenForo_Application::getOptions();
            
            $forum = false;
            
            if ($forum_id != false || $forum_name != false) {
                $forum = $ftp_helper->assertForumValidAndViewable($forum_id ? $forum_id : $forum_name);
                
                if (empty ($forum['estpd_node_type'])) {
                    $node_type = 0;
                } else {
                    $node_type = (int)$forum['estpd_node_type'];
                }
                
                $can_set_private = ($node_type & 1) == 1;
                $can_set_limited = ($node_type & 2) == 2;
                
                if ($options->estpd_allow_quoting == 0) {
                    $can_set_limited = false;
                } else if ($options->estpd_allow_quoting == 2) {
                    $can_set_limited = true;
                }
                
                if (!$visitor->hasNodePermission($forum['node_id'], 'estpd_can_create') && !$visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')) {
                    $can_set_private = false;
                }
                if (!$visitor->hasNodePermission($forum['node_id'], 'estpd_can_set_limits') && !$visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')) {
                    $can_set_limited = false;
                }

                if ($node_type == 1 && !$can_set_limited) {
                    return $this->responseError(new XenForo_Phrase ('estpd_error_no_permissions_to_make_thread_limited'), 403);
                }
                if ($node_type == 2 && !$can_set_private) {
                    return $this->responseError(new XenForo_Phrase ('estpd_error_no_permissions_to_make_thread_private'), 403);
                }
            }
            
            
            XenForo_Application::set('estpd_thread_privacy_settings', $privacy_settings);
        }
        
        return parent::actionAddThread();
    }

    
	/**
	 * Подготовка списка приватных тредов для дальнейшего использования
	 * @return XenForo_ControllerResponse_View
	 */
    public function actionIndex ( ) {
        $response = parent::actionIndex ();
        
        if (!isset ($response->params['threads']) || !isset ($response->params['forum']['estpd_node_type'])) {
            return $response;
        }
        if (empty ($response->params['forum']['estpd_node_type'])) {
            return $response;
        }
        
        $private = array ( );
        foreach ($response->params['threads'] as $thread) {
            if (!isset ($thread['estpd_thread_type'])) continue;
            if ($thread['estpd_thread_type']) $private[] = $thread['thread_id'];
        }
        
        if (count ($private) > 0) {
            XenForo_Application::set('estPD_private_threads', $private);
        }
        
        return $response;
    }
    
	/**
	 * Returns the user model
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel ( ) {
		return $this->getModelFromCache('XenForo_Model_User');
	}
}