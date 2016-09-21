<?php
/**
 * Менеджер установки/удаления
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Service_Manager {


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
     * Объект менеджера установки/удаления продукта
     * @var Esthetic_SimplePayments_Service_Manager
     */
    private static $_instance;


    /**
     * Получение тела объекта
     * @return Esthetic_SimplePayments_Service_Manager
     */
    public static final function getInstance ( ) {
        if (!self::$_instance) {
            self::$_instance = new self ( );
        }
        return self::$_instance;
    }
    
    
    /**
     * Инсталятор приложения
     * @return true
     */
    public static function install ($existing_addon, $addon_data) {
        
        $from_version = 1;
        if ($existing_addon) {
            $from_version = $existing_addon['version_id'] + 1;
        }
        
        $class = self::getInstance();
        for ($i = $from_version; $i <= $addon_data['version_id']; $i++) {
            $method = '_v_' . $i;
            if (false === method_exists($class, $method)) {
                continue;
            }

            $class->$method();
        }
        
        self::registerApplication();
        
        return true;
    }
    

    /**
     * Регистрация приложения
     * @return  null
     */
    public static function registerApplication ( ) {
    
        $options = XenForo_Application::get('options');
        
    }
    
    
    /**
     * Инсталятор версии 1.0.0
     */
    protected function _v_1000 ( ) {
		$db = XenForo_Application::get('db');
        
        try {
            $db->query("
                ALTER TABLE `xf_node` 
                    ADD `allow_estpd` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Allow Esthetic Private Discussion mode in threads'
            ");
        } catch (Exception $e) { }

        try {
            $db->query("
                ALTER TABLE `xf_thread` 
                    ADD `is_estpd` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'True when private',
                    ADD `estpd_allow_invitation` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'True when public invitation allowed'
            ");
        } catch (Exception $e) { }

 		$db->query("
            CREATE TABLE IF NOT EXISTS `est_pd_thread_users` (
                `thread_id` INT(10) UNSIGNED NOT NULL COMMENT 'ID of the thread',
                `user_id` INT(10) UNSIGNED NOT NULL COMMENT 'ID of the customer',
                `is_ts` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0' COMMENT 'True when user is a thread starter',
                PRIMARY KEY (`user_id`,`thread_id`),
                KEY `thread_id_is_ts` (`thread_id`,`is_ts`)
            ) ENGINE = InnoDB
		");        
 
        return true;
    }
    
    
    /**
     * Инсталятор версии 1.1.2
     */
    protected function _v_1120 ( ) {
        $db = XenForo_Application::get('db');
        
        $db->query("ALTER TABLE `xf_node` CHANGE `allow_estpd` `estpd_node_type` INT(10) UNSIGNED NOT NULL DEFAULT '0'");
        $db->query("ALTER TABLE `xf_thread` CHANGE `is_estpd` `estpd_thread_type` INT(10) UNSIGNED NOT NULL DEFAULT '0'");
        
        $db->query("RENAME TABLE `est_pd_thread_users` TO `estpd_thread_users`");
        
        $db->query("
            CREATE TABLE `estpd_thread_settings` (
                `thread_id` INT(10) UNSIGNED NOT NULL,
                `settings` MEDIUMBLOB NOT NULL,
                `preview_message` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
                PRIMARY KEY (`thread_id`)
            ) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci
        ");
    }
    
    
    /**
     * Удаление приложения
     * @return true
     */
    public static function uninstall ( ) {
		$db = XenForo_Application::get('db');
 		$db->query("
            ALTER TABLE `xf_node` 
                DROP `estpd_node_type`
		");
        
 		$db->query("
            ALTER TABLE `xf_thread` 
                DROP `estpd_thread_type`
		");
        
 		$db->query("
            DROP TABLE `estpd_thread_users`
		");
        
        return true;
    }
}