<?php
/**
 * Обработчик событий вызова шаблонов моделей
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_DataWriter {
    
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
            case 'XenForo_DataWriter_Forum':
                $extend[] = 'Esthetic_PD_DataWriter_Node';
                break;
            case 'XenForo_DataWriter_Discussion_Thread':
                $extend[] = 'Esthetic_PD_DataWriter_Discussion_Thread';
                break;
            default:
        }
        return true;
    }
}