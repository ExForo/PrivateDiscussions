<?php
/**
 * Обработчик событий вызова моделей
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_Model {

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
            case 'XenForo_Model_Search':
                $extend[] = 'Esthetic_PD_Model_Search';
                break;
            case 'XenForo_Model_User':
                $extend[] = 'Esthetic_PD_Model_User';
                break;
            case 'XenForo_Model_Thread':
                $extend[] = 'Esthetic_PD_Model_Thread';
                break;
            default:
        }
        
        return true;
    }
}