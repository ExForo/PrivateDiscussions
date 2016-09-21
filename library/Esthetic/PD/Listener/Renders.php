<?php
/**
 * Обработчик событий вызова рендеров шаблонов
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Listener_Renders {

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
     * Обработка пост-рендеренговых контейнеров
     * @param   string                      $templateName
     * @param   string                      &$content
     * @param   array                       &$containerData
     * @param   XenForo_Template_Abstract   $template
     */
    public static function listen ($templateName, &$content, &$containerData, $template) {
        
        $options = XenForo_Application::get('options');
        
        /**
         * Если дополнение не активно, приступить к стандартному выполнению
         */
        if (!$options->estPD_enabled) {
            return true;
        }
        
        switch ($templateName) {
            case 'forum_edit':
                
                if (!$session = $template->getParam('controllerName')) return false;
                if ($session != 'XenForo_ControllerAdmin_Forum') return false;

                $data = $template->getParam('forum');
                self::caseForumEdit ($content, $containerData, $data);
                break;
                
            default:
        }
        return true;
    }

    /**
     * Обработка формы регистрации категории
     * @param   string  &$contents
     * @param   array   &$containerData
     * @param   array   $data
     */
    public static function caseForumEdit (&$contents, &$containerData, $data) {
        
        $allow_limited = false;
        $allow_private = false;
        if (!empty ($data['estpd_node_type'])) {
            $allow_private = ($data['estpd_node_type'] & 1) == 1;
            $allow_limited = ($data['estpd_node_type'] & 2) == 2;
        }
        
        $template   = new XenForo_Template_Admin ('estPD_forum_node_edit', array (
            'estpd_allow_private_threads' => $allow_private,
            'estpd_allow_access_quoting' => $allow_limited
        ));
        $insert     = $template->render();
        $contents   = preg_replace ('/\<\!\-\-\@estPD\:pane\-\-\>/', $insert, $contents);
        
        return true;
    }
}