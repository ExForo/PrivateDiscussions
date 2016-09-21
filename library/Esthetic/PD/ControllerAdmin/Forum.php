<?php
/**
 * Контроллер "Forum"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_ControllerAdmin_Forum extends XFCP_Esthetic_PD_ControllerAdmin_Forum {


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
     * Замена функции сохранения настроек
     * @return  bool
     */
	public function actionSave ( ) {
        
        $allow_private = $this->_input->filterSingle('estpd_allow_private_threads', XenForo_Input::UINT);
        $allow_limited = $this->_input->filterSingle('estpd_allow_access_quoting', XenForo_Input::UINT);
        
        XenForo_Application::set('estpd_admin_data', array ('estpd_node_type' => $allow_limited * 2 + $allow_private));
        
        return parent::actionSave();
    }
}