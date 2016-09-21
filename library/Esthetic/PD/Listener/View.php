<?php
/**
 * Обработчик событий вызова рендеров отображения
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_View {
    

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
            case 'XenForo_ViewPublic_Member_RecentActivity':
                $extend[] = 'Esthetic_PD_ViewPublic_Member_RecentActivity';
                break;
            default:
        }
        
        return true;
    }
}