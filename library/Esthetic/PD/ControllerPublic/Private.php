<?php
/**
 * Контроллер "Private"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ControllerPublic_Private extends XenForo_ControllerPublic_Abstract {

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
      * Добавление новых пользователей к текущей теме
      * @return     XenForo_ControllerResponse_Abstract
      */
    public function actionAdd ( ) {
        
        $this->_assertRegistrationRequired();
        $this->_assertPostOnly();
        
        $visitor        = XenForo_Visitor::getInstance();
        $thread_id      = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
        $recepients     = $this->_input->filterSingle('estPD_users', XenForo_Input::STRING);
        
        /**
         * Проверка ошибок доступа
         */
        if (false == ($thread = $this->_getThreadModel()->getThreadById($thread_id))) {
            return $this->responseRedirect (
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('forums/')
            );
        }
        
        $can_add = false;
        if (
            (int)$visitor['user_id'] == (int)$thread['user_id'] || 
            $thread['estpd_allow_invitation'] == true || 
            $visitor->hasNodePermission($thread['node_id'], 'estpd_can_manage')
        ) {
            $can_add = true;
        }
        
        /**
         * Проверка параметров доступа
         */
        if (!$can_add || $recepients == false) {
            return $this->responseRedirect (
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('threads/', $thread)
            );
        }
        
        /**
         * Выборка пользователей
         */
		$_users = $this->_getUserModel()->getUsersByNames(
			explode(',', $recepients),
			array(
				'join' => XenForo_Model_User::FETCH_USER_PRIVACY + XenForo_Model_User::FETCH_USER_OPTION,
				'followingUserId' => (int)$visitor['user_id']
			),
			$notFound
		);
        
        /**
         * Пользователи не обнаружены
         */
        if ($notFound) {
            return $this->responseRedirect (
                XenForo_ControllerResponse_Redirect::SUCCESS,
                XenForo_Link::buildPublicLink('threads/', $thread)
            );
        }
        
        $recepients_array = array ( );
        
        $current_recepients = $this->_getThreadUserModel()->getThreadRecepients((int)$thread_id);
        foreach ($current_recepients as $_user) {
            $recepients_array[] = $_user['user_id'];
        }
        
        if (is_array ($_users)) {
            
            $user_ids = array ( );
            
            foreach ($_users as $_user) {
            
                if ((int)$_user['user_id'] == (int)$visitor['user_id']) 
                    continue;
                if (in_array ((int)$_user['user_id'], $recepients_array))
                    continue;

                $user_ids[] = $_user['user_id'];
            }
            
            $this->_getThreadUserModel()->insertThreadUsers($thread['thread_id'], $user_ids);
        }

        return $this->responseRedirect (
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('threads/', $thread)
        );
    }
    
    
    /**
     * Подтверждение удаления пользователя из списка пользователей темы
     * @return     XenForo_ControllerResponse_Abstract
     */
    public function actionRemoveConfirmation ( ) {
        
        $this->_assertRegistrationRequired();
        
        $visitor                = XenForo_Visitor::getInstance();
        $thread_id              = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
        $recepient              = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$ftpHelper              = $this->getHelper('ForumThreadPost');
		list ($thread, $forum)  = $ftpHelper->assertThreadValidAndViewable($thread_id);

        /**
         * Проверка параметров доступа
         */
        $can_manage = false;
        if (
            (int)$visitor['user_id'] == (int)$thread['user_id'] || 
            $visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')
        ) {
            $can_manage = true;
        }
        if (!$can_manage || $recepient == false) {
            return $this->responseError(new XenForo_Phrase('estPD_recepient_access_error'), 403);
        }
        if (false === ($recepient = $this->_getThreadUserModel()->getUserAsThreadRecepient ((int)$thread['thread_id'], $recepient))) {
            return $this->responseError(new XenForo_Phrase('estPD_recepient_access_error'), 403);
        }
    
        return $this->responseView ('Esthetic_PD_ViewPublic_Private_View', 'estPD_remove_user_confirm', array (
            'thread'            => $thread,
            'forum'             => $forum,
            'nodeBreadCrumbs'   => $ftpHelper->getNodeBreadCrumbs($forum),
            'recepient'         => $recepient
        ));
    }
    
    
    /**
     * Удаление пользователя из списка пользователей темы
     * @return     XenForo_ControllerResponse_Abstract
     */
    public function actionRemoveUser ( ) {
    
        $this->_assertRegistrationRequired();
        $this->_assertPostOnly();
        
        $visitor                = XenForo_Visitor::getInstance();
        $thread_id              = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
        $recepient              = $this->_input->filterSingle('user_id', XenForo_Input::UINT);
		$ftpHelper              = $this->getHelper('ForumThreadPost');
		list($thread, $forum)   = $ftpHelper->assertThreadValidAndViewable($thread_id);
        
        /**
         * Проверка параметров доступа
         */
        $can_manage = false;
        if (
            (int)$visitor['user_id'] == (int)$thread['user_id'] || 
            $visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')
        ) {
            $can_manage = true;
        }
        if (!$can_manage || $recepient == false) {
            return $this->responseError(new XenForo_Phrase('estPD_recepient_access_error'), 403);
        }
        
        $this->_getThreadUserModel()->removeUserFromThread ((int)$thread['thread_id'], (int)$recepient);
        
        return $this->responseRedirect (
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('threads/', $thread)
        );
    }
    
    
    /**
     * Вызов диалога настроек приватности
     * @return     XenForo_ControllerResponse_Abstract
     */
    public function actionAccessDialog ( ) {
    
        $this->_assertRegistrationRequired();
        
        $visitor                = XenForo_Visitor::getInstance();
        $options                = XenForo_Application::get('options');
        $thread_id              = $this->_input->filterSingle('thread_id', XenForo_Input::UINT);
		$ftp_helper             = $this->getHelper('ForumThreadPost');
		list ($thread, $forum)  = $ftp_helper->assertThreadValidAndViewable($thread_id);
        
        /**
         * Проверка параметров доступа
         */
        if ((int)$visitor['user_id'] != (int)$thread['user_id'] && !$visitor->hasNodePermission($forum['node_id'], 'estpd_can_manage')) {
            return $this->responseError(new XenForo_Phrase ('estPD_recepient_access_error'), 403);
        }
        
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
        
        if (!$this->_input->filterSingle('confirm', XenForo_INPUT::UINT)) {
            
            $is_private = false;
            $is_limited = false;
            $limits = false;

            if (!empty ($thread['estpd_thread_type'])) {
                if ($thread['estpd_thread_type'] == 1) {
                    $is_private = true;
                } else if ($thread['estpd_thread_type'] == 2) {
                    $is_limited = true;
                    $limits = $this->_getThreadSettingsModel()->getSettingsByThreadId($thread_id);
                }
            }
            
            return $this->responseView ('Esthetic_PD_ViewPublic_Private_Settings', 'estpd_access_settings_dialog', array (
                'thread'            => $thread,
                'forum'             => $forum,
                'node_bread_crumbs' => $ftp_helper->getNodeBreadCrumbs($forum),
                
                'can_set_limited'   => $can_set_limited,
                'can_set_private'   => $can_set_private,
                'is_private'        => $is_private,
                'is_limited'        => $is_limited,
                'limits'            => $limits
            ));
        }
        
        XenForo_Application::set('estpd_thread_privacy_settings', $this->_input->filter(array (
            'thread_type' => Xenforo_Input::UINT,
            'estpd_allow_public_invitation' => XenForo_Input::UINT,
            'posts' => XenForo_Input::UINT,
            'likes' => XenForo_Input::UINT,
            'trophies' => XenForo_Input::UINT,
            'days' => XenForo_Input::UINT,
            'extended' => XenForo_Input::STRING
        )));
        
        /**
         * Установка и сохранение параметров доступа к теме
         */
        $dw = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
        $dw->setExistingData($thread_id);
        $dw->save();
        
        return $this->responseRedirect (
            XenForo_ControllerResponse_Redirect::SUCCESS,
            XenForo_Link::buildPublicLink('threads/', $thread)
        );
    }
    
    
	/**
	 * @return XenForo_Model_Thread
	 */
	protected function _getThreadModel ( ) {
		return $this->getModelFromCache('XenForo_Model_Thread');
	}
    
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel ( ) {
		return $this->getModelFromCache('XenForo_Model_User');
	}
    
	/**
	 * @return Esthetic_PD_Model_ThreadUser
	 */
	protected function _getThreadUserModel ( ) {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadUser');
	}
    
	/**
	 * @return Esthetic_PD_Model_ThreadSettings
	 */
	protected function _getThreadSettingsModel ( ) {
		return $this->getModelFromCache('Esthetic_PD_Model_ThreadSettings');
	}
}