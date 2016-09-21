<?php
/**
 * Контроллер "Account"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ControllerPublic_Account extends XFCP_Esthetic_PD_ControllerPublic_Account {

    /**
     * Номер версии скрипта
     * @var     int
     */
    public static $est_version_id   = 1110;
    
    /**
     * Идентификатор продукта
     * @var     string
     */
    public static $est_addon_id     = 'estpd';
    

    
    
    /**
     * Вывод информации о приватных темах пользователя
     * @return     XenForo_ControllerResponse_View
     */
    public function actionPrivateDiscussions ( ) {
    
        $this->_assertRegistrationRequired();
        
        return $this->responseView(
            'Esthetic_PD_ViewPublic_Private_Personal',
            'estPD_private_personal',
            array (
                'threads'           => $this->_getUserModel()->getUsersPrivateThreadsSortedArray(XenForo_Visitor::getUserId())
            )
        );
    }
    
    
	/**
	 * @return XenForo_Model_User
	 */
	protected function _getUserModel ( ) {
		return $this->getModelFromCache('XenForo_Model_User');
	}
}