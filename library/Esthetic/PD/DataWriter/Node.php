<?php
/**
 * Шаблон модели Node
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_DataWriter_Node extends XFCP_Esthetic_PD_DataWriter_Node {
    
    
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
	protected function _getFields() {
        $response   = parent::_getFields();
        $response['xf_node']['estpd_node_type'] = array('type' => self::TYPE_UINT, 'default' => 0);
        return $response;
    }

    /**
     * Обработка параметра allow_estpd перед сохранением
     * @return  null
     */
    protected function _preSave ( ) {
    
        if (!XenForo_Application::isRegistered('estpd_admin_data')) {
            return parent::_preSave();
        }
        
        $data = XenForo_Application::get('estpd_admin_data');
        
        if (!empty ($data['estpd_node_type'])) {
            $this->set('estpd_node_type', (int)$data['estpd_node_type']);
        } else {
            $this->set('estpd_node_type', 0);
        }
        
        return parent::_preSave();
    }
}