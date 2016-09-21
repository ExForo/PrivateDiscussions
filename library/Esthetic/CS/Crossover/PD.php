<?php
/**
 * Интеграция дополнения Esthetic Private Discussions
 * @package     Esthetic_CS
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_CS_Crossover_PD {

    /**
     * Номер версии скрипта
     * @var     int
     */
    public static $est_version_id   = 1140;
    
    /**
     * Идентификатор продукта
     * @var     string
     */
    public static $est_addon_id     = 'estcs';
    
    
    
    /**
     * Проверка доступности темы для присоединения
     * @param   array   $shopping
     * @param   int     $thread
     * @return  mixed
     */
    public static function threadValidAndAvailable ($shopping, $thread_id) {
        
        $db = XenForo_Application::getDb();
        $visitor = XenForo_Visitor::getInstance();
        
        $thread = XenForo_Model::create('XenForo_Model_Thread')->getThreadById($thread_id);
        
        if (empty ($thread['estpd_thread_type']) || $thread['estpd_thread_type'] != 1) {
            return new XenForo_Phrase ('estcs_error_not_private_thread');
        }
        
        if ($thread['user_id'] != $visitor['user_id'] && !$visitor->hasPermission('estcs', 'estcs_can_manage')) {
            return new XenForo_Phrase ('estcs_error_pd_thread_not_owned_by_user');
        }
        
        return true;
    }
    
    
    /**
     * Проверка разрешения на автоматическое создание приватного обсуждения
     * @return  bool
     */
    public static function isAutoCreateThreadAvailable ( ) {
        
        $options = XenForo_Application::get('options');
        
        if (empty ($options->estcs_private_thread_id)) {
            return false;
        }
        
        $forum = XenForo_Model::create('XenForo_Model_Forum')->getForumById((int)$options->estcs_private_thread_id);
        
        if (empty ($forum['estpd_node_type'])) {
            $node_type = 0;
        } else {
            $node_type = (int)$forum['estpd_node_type'];
        }
        
        return ($node_type & 1) == 1;
    }
    
    
    /**
     * Автоматическое создание приватной дискуссии
     * @param   array   $shopping
     * @param   array   $thread
     * @return  mixed
     */
    public static function autoCreateThread ($shopping, $thread) {

        $options = XenForo_Application::get('options');
        $visitor = XenForo_Visitor::getInstance();
        
        if (empty ($options->estcs_private_thread_id)) {
            return new XenForo_Phrase ('estcs_error_private_discussion_no_node');
        }
        
        $forum = XenForo_Model::create('XenForo_Model_Forum')->getForumById((int)$options->estcs_private_thread_id);
        
        if (empty ($forum['estpd_node_type'])) {
            $node_type = 0;
        } else {
            $node_type = (int)$forum['estpd_node_type'];
        }
        
        if (($node_type & 1) != 1) {
            return new XenForo_Phrase ('estcs_error_private_discussion_node_fail');
        }
        
        if (empty ($options->estcs_pd_ignore_rules) && !$visitor->hasPermission('estPD', 'estPD_can_create')) {
            return new XenForo_Phrase ('estcs_error_private_discussion_no_permission');
        }
        
        $writer = XenForo_DataWriter::create('XenForo_DataWriter_Discussion_Thread');
        $writer->bulkSet(array (
            'user_id'           => $visitor['user_id'],
            'username'          => $visitor['username'],
            'title'             => $thread['title'],
            'node_id'           => $forum['node_id'],
            'estpd_thread_type' => 1
        ));

        $writer->set('discussion_state', 'visible');
        $writer->set('discussion_open', 1);
        $writer->set('sticky', 0);

        $post_writer = $writer->getFirstMessageDw();
        $post_writer->set('message', htmlspecialchars_decode (new XenForo_Phrase ('estcs_pd_thread_first_message', array (
            'title'     => $thread['title'],
            'link'      => XenForo_Link::buildPublicLink('full:threads', $thread)
        ))));
        $post_writer->setExtraData(XenForo_DataWriter_DiscussionMessage_Post::DATA_FORUM, $forum);

        $writer->setExtraData(XenForo_DataWriter_Discussion_Thread::DATA_FORUM, $forum);
        $writer->preSave();
        $writer->save();

        $thread = $writer->getMergedData();
        
        if (!empty ($thread)) {
            $writer = XenForo_DataWriter::create('Esthetic_PD_DataWriter_ThreadUser');
            $writer->bulkSet(array (
                'thread_id'     => $thread['thread_id'],
                'user_id'       => $thread['user_id'],
                'is_ts'         => 1
            ));
            
            $writer->save();
        }
        
        return $thread['thread_id'];
    }
    
    
    /**
     * Проверка списков участников обсуждения
     * @param   int     $thread_id
     * @param   array   $shopping
     * @param   int     $access_type
     * @param   bool    $extend_control
     */
    public static function doParticipantsCheck ($thread_id, $shopping, $access_type, $extend_control = false) {
        
        $db = XenForo_Application::getDb();
        
        /**
         * Если используется система полного контроля доступа, списки формируются "с чистого листа". Соответственно,
         * предварительно необходимо уничтожить всю текущую информацию о доступе к теме.
         */
        if ($extend_control) {
            $db->query(
                'DELETE pd FROM `estpd_thread_users` AS pd
                    WHERE pd.thread_id = ?
                        AND pd.is_ts = 0', array ($thread_id)
            );
        }
        
        /**
         * Установка условий записи
         */
        switch ($access_type) {
            case 2:
                $where_clause = ' AND p.is_payed = 1 AND p.is_delivered = 1';
                break;
            
            case 1:
                $where_clause = ' AND p.is_payed = 1';
                break;
                
            default:
                $where_clause = '';
        }
        
        $db->query(
            'INSERT INTO `estpd_thread_users`(`thread_id`, `user_id`, `is_ts`)
                SELECT ?, p.user_id, 0
                    FROM `estcs_participant` AS p
                        WHERE p.shopping_id = ?' . $where_clause . '
                ON DUPLICATE KEY UPDATE `thread_id` = ?', array ($thread_id, $shopping['shopping_id'], $thread_id)
        );
        
        /**
         * Дополнительная запись в список организатора
         */
        $db->query(
            'INSERT INTO `estpd_thread_users`(`thread_id`, `user_id`, `is_ts`)
                VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE `thread_id` = ?', array ($thread_id, $shopping['organizer_id'], $thread_id)
        );
        
        return true;
    }
    
    
    /**
     * Добавление пользователя к обсуждению
     * @param   int     $thread_id
     * @param   int     $user_id
     * @return  true
     */
    public static function addUserToDiscussion ($thread_id, $user_id) {
        XenForo_Application::getDb()->query(
            'INSERT INTO `estpd_thread_users`(`thread_id`, `user_id`, `is_ts`)
                VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE `thread_id` = ?', array ($thread_id, $user_id, $thread_id)
        );
        return true;
    }
    
    
    /**
     * Удаление пользователя из списка участников обсуждения
     * @param   int     $thread_id
     * @param   int     $user_id
     * @return  true
     */
    public static function removeUserFromDiscussion ($thread_id, $user_id) {
        XenForo_Application::getDb()->query(
            'DELETE pd FROM `estpd_thread_users` AS pd
                WHERE pd.thread_id = ?
                    AND pd.user_id = ?
                    AND pd.is_ts = 0', array ($thread_id, $user_id)
        );
        return true;
    }
    
    
    /**
     * Добавление пользователя в список участников обсуждения
     * @param   array   $shopping
     * @param   int     $user_id
     * @return  bool
     */
    public static function addUserByRule ($shopping, $user_id) {
        
        if (empty ($shopping['extended_data'])) {
            return false;
        }
        
        if (empty ($shopping['extended_data']['private_thread_id'])) {
            return false;
        }
        
        $access_type = 0;
        if (!empty ($shopping['extended_data']['private_thread_access_type'])) {
            $access_type = $shopping['extended_data']['private_thread_access_type'];
        }
        
        /**
         * Установка условий записи
         */
        switch ($access_type) {
            case 2:
                $where_clause = ' AND p.is_payed = 1 AND p.is_delivered = 1';
                break;
            
            case 1:
                $where_clause = ' AND p.is_payed = 1';
                break;
                
            default:
                $where_clause = '';
        }
        
        if (is_array ($user_id)) {
            XenForo_Application::getDb()->query(
                'INSERT INTO `estpd_thread_users`(`thread_id`, `user_id`, `is_ts`)
                    SELECT ?, p.user_id, 0
                        FROM `estcs_participant` AS p
                            WHERE p.shopping_id = ? AND p.user_id IN (' . implode (',', $user_id) . ')' . $where_clause . '
                    ON DUPLICATE KEY UPDATE `thread_id` = ?', array (
                        intval ($shopping['extended_data']['private_thread_id']), 
                        $shopping['shopping_id'], 
                        intval ($shopping['extended_data']['private_thread_id'])
            ));
        } else {
            XenForo_Application::getDb()->query(
                'INSERT INTO `estpd_thread_users`(`thread_id`, `user_id`, `is_ts`)
                    SELECT ?, p.user_id, 0
                        FROM `estcs_participant` AS p
                            WHERE p.shopping_id = ? AND p.user_id = ?' . $where_clause . '
                    ON DUPLICATE KEY UPDATE `thread_id` = ?', array (
                        intval ($shopping['extended_data']['private_thread_id']), 
                        $shopping['shopping_id'], 
                        $user_id, 
                        intval ($shopping['extended_data']['private_thread_id'])
            ));
        }
        
        return true;
    }
}