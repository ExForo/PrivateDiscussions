<?php
/**
 * Обработчик событий вызова контроллеров
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_Controller {
    
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
     * Обработка вызова класса
     * @param   string  &$class
     * @param   array   &$extends
     * @return  bool
     */
    public static function listen ($class, array &$extend) {
        
        $options = XenForo_Application::get('options');
        
        /**
         * Если дополнение не активно, приступить к стандартному выполнению
         */
        if (!$options->estPD_enabled) {
            return true;
        }
        
        switch ($class) {
            case 'XenForo_ControllerAdmin_Forum':
                $extend[] = 'Esthetic_PD_ControllerAdmin_Forum';
                break;
            case 'XenForo_ControllerPublic_Forum':
                $extend[] = 'Esthetic_PD_ControllerPublic_Forum';
                break;
            case 'XenForo_ControllerPublic_Thread':
                $extend[] = 'Esthetic_PD_ControllerPublic_Thread';
                break;
            case 'XenForo_ControllerPublic_Account':
                $extend[] = 'Esthetic_PD_ControllerPublic_Account';
                break;
            default:
        }
        return true;
    }
}