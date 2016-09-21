<?php
/**
 * Дополнительные функции обработки лимитов
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Helper_Limits {


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
    
    
    protected static $_phrases = array ( );

    
    
    /**
     * Проверка на валидность правила ограничения доступа
     * @param   array   $rule
     * @return  null|boolean
     */
    public static function isValid ($rule) {
        
        /**
         * Формирование маски
         */
        $mask = '';
        if (!empty ($rule['posts'])) {
            $mask .= sprintf ('(posts >= %d) and', (int)$rule['posts']);
        }
        if (!empty ($rule['likes'])) {
            $mask .= sprintf ('(likes >= %d) and', (int)$rule['likes']);
        }
        if (!empty ($rule['days'])) {
            $mask .= sprintf ('(days >= %d) and', (int)$rule['days']);
        }
        if (!empty ($rule['trophies'])) {
            $mask .= sprintf ('(trophies >= %d) and', (int)$rule['trophies']);
        }
        if (!empty ($rule['extended'])) {
            $mask .= sprintf ('(%s)', (string)$rule['extended']);
        } else {
            $mask = preg_replace ('/and$/', '', $mask);
        }
        
        $mask = str_replace (
            array ('and', 'or'),
            array ('&', '|'),
            $mask
        );
        
        $mask = preg_replace ('/[\r\n\s]+/', '', strtolower ($mask));
        
        if ($mask == false) {
            return null;
        }
        
        $mask = str_replace ('==', '=', $mask);
        
        /**
         * Поиск и замена верных правил
         */
        $mask = preg_replace ('/(groups|userids)(\!?\=)((\d+\,)+\d+|\d+)/', '[1]', $mask);
        $mask = preg_replace ('/(posts|likes|trophies|days)\=(\d+)/', '$1>=$2', $mask);
        $mask = preg_replace ('/(posts|likes|trophies|days)\>\=[0]+/', '[0]', $mask);
        $mask = preg_replace ('/(posts|likes|trophies|days)\>\=*\d+/', '[1]', $mask);
        
        /**
         * Ошибка синтаксиса
         */
        if (!preg_match ('/^[0-1\[\]\|\&\(\)]+$/', $mask)) {
            return false;
        }
        
        $mask = str_replace (array ('[', ']'), array ('', ''), $mask);
        
        $runs = 0;
        do {
            
            $mask = str_replace (
                array ('0&0', '0&1', '1&0', '1&1', '0|0', '0|1', '1|0', '1|1'),
                array ('0',   '1',   '1',   '1',   '0',   '0',   '0',   '1'),
                $mask
            );
            
            $mask = str_replace (
                array ('(0)', '(1)'),
                array ('0', '1'),
                $mask
            );
            
            if (strlen ($mask) == 1) {
                break;
            }
            
            $runs++;
            if ($runs > 100) {
                $mask = '?';
                break;
            }
        } while (true);
        
        if ($mask != '0' && $mask != '1') {
            return false;
        }
        
        /**
         * Условие не будет эффективным при использовании, так как в конечном итоге пропустит всех зарегистрированных пользователей
         */
        if ($mask == '0') {
            return null;
        }
        
        return true;
    }
    
    
    /**
     * Применение правила доступа к теме
     * @param   array   $rule
     * @return  array
     */
    public static function apply ($rule) {
        
        self::_preloadPhrases();
        
        $result = array (
            'match'         => false,
            'is_error'      => true,
            'message'       => self::$_phrases['estpd_error_invalid_rule']
        );
        
        $visitor = XenForo_Visitor::getInstance();
    
        /**
         * Формирование маски
         */
        $mask = '';
        if (!empty ($rule['posts'])) {
            $mask .= sprintf ('(posts >= %d) and', (int)$rule['posts']);
        }
        if (!empty ($rule['likes'])) {
            $mask .= sprintf ('(likes >= %d) and', (int)$rule['likes']);
        }
        if (!empty ($rule['days'])) {
            $mask .= sprintf ('(days >= %d) and', (int)$rule['days']);
        }
        if (!empty ($rule['trophies'])) {
            $mask .= sprintf ('(trophies >= %d) and', (int)$rule['trophies']);
        }
        if (!empty ($rule['extended'])) {
            $mask .= sprintf ('(%s)', (string)$rule['extended']);
        } else {
            $mask = preg_replace ('/and$/', '', $mask);
        }
        
        $mask = str_replace (
            array ('and', 'or'),
            array ('&', '|'),
            $mask
        );
        
        $mask = preg_replace ('/[\r\n\s]+/', '', strtolower ($mask));
        $string = $mask;
        
        if ($visitor['user_id']) {
            $days = intval ((XenForo_Application::$time - $visitor['register_date']) / 86400);
        } else {
            $days = 0;
        }
        
        $mask = str_replace (
            array ('posts', 'likes', 'trophies', 'days'),
            array (
                'posts'     => $visitor['message_count'], 
                'likes'     => $visitor['like_count'], 
                'trophies'  => $visitor['trophy_points'], 
                'days'      => $days,
            ),
            $mask
        );
        
        $mask = preg_replace_callback ('/(groups|userids)(\!?\=)((\d+\,)+\d+|\d+)/', array ('Esthetic_PD_Helper_Limits', '_compareSetExpression'), $mask);
        
        if (preg_match ('/^[\(\)\d\&\|\>\<\=]+$/', $mask)) {
            
            $mask = preg_replace_callback ('/(\d+)([\>\=\<]{1,2})(\d+)/', array ('Esthetic_PD_Helper_Limits', '_compareMathExpression'), $mask);
            
            $runs = 0;
            do {
                
                $mask = str_replace (
                    array ('0&0', '0&1', '1&0', '1&1', '0|0', '0|1', '1|0', '1|1'),
                    array ('0', '0', '0', '1', '0', '1', '1', '1'),
                    $mask
                );
                
                $mask = str_replace (
                    array ('(0)', '(1)'),
                    array ('0', '1'),
                    $mask
                );
                
                if (strlen ($mask) == 1) {
                    break;
                }
                
                $runs++;
                if ($runs > 100) {
                    $mask = '?';
                    break;
                }
            } while (true);
            
        } else {
            return $result;
        }
        
        if ($mask == '?') {
            return $result;
        }
        
        $result['is_error'] = false;
        
        if ($mask == '1') {
            $result['match'] = true;
        }
        
        $string = preg_replace ('/\((posts|likes|trophies|days|groups|userids)([\=\>\<]+)([0-9\,]+)\)/', '$1$2$3', $string);
        
        while (preg_match ('/\([^\(\)]+\)/', $string)) {
            $string = preg_replace ('/\(([^\(\)]+)\)/', "<div class=\"estpd-rule-fieldset\">\r\n$1\r\n</div>", $string);
        }
        
        $string = str_replace (
            array ('|', '&'),
            array (
                sprintf (
                    '<span class="estpd-rule-bool-expression">%s</span>', self::$_phrases['estpd_boolean_operation_or']
                ), 
                sprintf (
                    '<span class="estpd-rule-bool-expression">%s</span>', self::$_phrases['estpd_boolean_operation_and']
                )
            ),
            $string
        );
        
        $string = preg_replace_callback ('/(posts|likes|trophies|days)([\>\=\<]{1,2})(\d+)/', array ('Esthetic_PD_Helper_Limits', '_mathStringBuilder'), $string);
        
        $result['message'] = preg_replace_callback ('/(groups|userids)\=([0-9\,]+)/', array ('Esthetic_PD_Helper_Limits', '_setStringBuilder'), $string);
        
        return $result;
    }
    
    
    /**
     * Построение строки информации для ограничений типа userids и groups
     * @param   array   $matches
     * @return  string
     */
    protected static function _setStringBuilder ($matches) {
        
        $type = $matches[1];
        $_ids = explode (',', $matches[2]);
        
        if (empty ($_ids)) {
            return '';
        }
        
        $ids = array ( );
        foreach ($_ids as $id) {
            $ids[$id] = (int)$id;
        }
        
        $visitor = XenForo_Visitor::getInstance();
        
        if ($type == 'groups') {
            
            $groups = XenForo_Model::create('XenForo_Model_UserGroup')->getAllUserGroups();
            ksort ($groups);
            
            $groups_list = '';
            $is_member = false;
            foreach ($groups as $group_id => $group) {
                if (empty ($ids[$group_id])) {
                    continue;
                }
                
                if ($groups_list != false) {
                    $groups_list .= ', ';
                }
                
                $groups_list .= sprintf (
                    '<span class="estpd-rule-set-item-%s">%s</span>',
                    $visitor->isMemberOf($group_id) ? 'matched' : 'unmatched',
                    htmlspecialchars ($group['title'])
                );
                
                if ($visitor->isMemberOf($group_id)) {
                    $is_member = true;
                }
            }
            
            return sprintf (
                '<div class="estpd-rule-atom-%s">%s %s</div>',
                $is_member ? 'matched' : 'unmatched',
                self::$_phrases['estpd_member_of_groups'],
                $groups_list
            );
        }
        
        $users = XenForo_Model::create('XenForo_Model_User')->getUsersByIds($ids);
        
        if (empty ($users)) {
            return '';
        }
        
        $users_list = '';
        $is_member = false;
        
        foreach ($users as $user_id => $user) {
            if ($users_list != false) {
                $users_list .= ', ';
            }
            
            $users_list .= sprintf (
                '<span class="estpd-rule-set-item-%s">%s</span>',
                $visitor['user_id'] == $user_id ? 'matched' : 'unmatched',
                htmlspecialchars ($user['username'])
            );
            
            if ($visitor['user_id'] == $user_id) {
                $is_member = true;
            }
        }
        
        return sprintf (
            '<div class="estpd-rule-atom-%s">%s %s</div>',
            $is_member ? 'matched' : 'unmatched',
            self::$_phrases['estpd_granted_for_users'],
            $users_list
        );
    }
    
    
    /**
     * Построение строки информации исходя из строк ограничений
     * @param   array   $matches
     * @return  string
     */
    protected static function _mathStringBuilder ($matches) {
        
        $visitor = XenForo_Visitor::getInstance();
        
        $param = $matches[1];
        $op = $matches[2];
        $value = (int)$matches[3];
        
        switch ($param) {
            case 'posts':
                $compare = $visitor['message_count'];
                $param_str = self::$_phrases['messages'];
                break;
                
            case 'likes':
                $compare = $visitor['like_count'];
                $param_str = self::$_phrases['likes'];
                break;
                
            case 'trophies':
                $compare = $visitor['trophy_points'];
                $param_str = self::$_phrases['trophies'];
                break;
                
            case 'days':
                if ($visitor['user_id']) {
                    $compare = intval ((XenForo_Application::$time - $visitor['register_date']) / 86400);
                } else {
                    $compare = 0;
                }
                $param_str = self::$_phrases['estpd_days_on_forum'];
                break;
        }
        
        switch ($op) {
            case '<':
                $match = $compare < $value;
                break;
                
            case '<=':
                $match = $compare <= $value;
                break;
                
            case '=':
                $match = $compare == $value;
                break;
                
            case '>=':
                $match = $compare >= $value;
                break;
                
            case '>':
                $match = $compare > $value;
                break;
            
            default:
                $match = false;
        }
        
        return sprintf (
            '<div class="estpd-rule-atom-%s">%s <span class="estpd-rule-math-expression">%s</span> %d</div>',
            $match ? 'matched' : 'unmatched', $param_str, htmlspecialchars ($op), $value
        );
    }
    
    
    /**
     * Сравнение множеств
     * @param   array   $matches
     * @return string
     */
    protected static function _compareSetExpression ($matches) {
        
        $visitor = XenForo_Visitor::getInstance();
        
        $type = $matches[1];
        $op = $matches[2];
        $set = $matches[3];
        
        if ($op != '=' && $op != '!=') {
            return '?';
        }
        
        $check = false;
        switch ($type) {
        
            case 'groups':
                $set = explode (',', $set);

                if (empty ($set)) {
                    return '?';
                }
                
                foreach ($set as $group_id) {
                    if ($visitor->isMemberOf(intval ($group_id))) {
                        $check = true;
                        break;
                    }
                }
                
                break;
            
            case 'userids':
                $check = self::_userIdOneOf($set);
                
                if ($check === null) {
                    return '?';
                }

                break;
                
            default:
                return '?';
        }
        
        if ($op == '=' && !$check) {
            return '0';
        } else if ($op == '!=' && $check) {
            return '0';
        }
        
        return '1';
    }
    
    
    /**
     * Математическое сравнение чисел
     * @param   array   $matches
     * @return string
     */
    protected static function _compareMathExpression ($matches) {
        
        $a = (int)$matches[1];
        $b = (int)$matches[3];
        $op = $matches[2];
        
        if ($op != '<' && $op != '<=' && $op != '=' && $op != '>=' && $op != '>') {
            return '?';
        }
        
        switch ($op) {
            case '<':
                return $a < $b ? '1' : '0';
            case '<=':
                return $a <= $b ? '1' : '0';
            case '=':
                return $a == $b ? '1' : '0';
            case '>=':
                return $a >= $b ? '1' : '0';
            case '>':
                return $a > $b ? '1' : '0';
            default:
                return '?';
        }
        
        return '?';
    }
    
    
    /**
     * Проверка принадлежности идентификатора пользователя к указанной в строке последовательности
     * @param string    $tag_option
     * @return bool|null
     */
    protected static function _userIdOneOf ($tag_option) {
        
        $visitor = XenForo_Visitor::getInstance();
        
        if (empty ($tag_option)) {
            return null;
        }
        
        $tag_option = str_replace (' ', '', $tag_option);
        
        $user_ids = array ( );
        if (preg_match ('/^(\d+\,)+\d+$/', $tag_option)) {
            $user_ids = explode (',', $tag_option);
        } else if (is_numeric ($tag_option)) {
            $user_ids[] = (int)$tag_option;
        } else {
            return null;
        }
        
        if (in_array ($visitor['user_id'], $user_ids)) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * Предварительная загрузка реплик и фраз
     * @return null
     */
    protected static function _preloadPhrases ( ) {
        self::$_phrases['estpd_error_invalid_rule']         = new XenForo_Phrase ('estpd_error_invalid_rule');
        self::$_phrases['messages']                         = new XenForo_Phrase ('messages');
        self::$_phrases['likes']                            = new XenForo_Phrase ('likes');
        self::$_phrases['trophies']                         = new XenForo_Phrase ('trophies');
        self::$_phrases['estpd_days_on_forum']              = new XenForo_Phrase ('estpd_days_on_forum');
        self::$_phrases['estpd_granted_for_users']          = new XenForo_Phrase ('estpd_granted_for_users');
        self::$_phrases['estpd_member_of_groups']           = new XenForo_Phrase ('estpd_member_of_groups');
        self::$_phrases['estpd_boolean_operation_and']      = new XenForo_Phrase ('estpd_boolean_operation_and');
        self::$_phrases['estpd_boolean_operation_or']       = new XenForo_Phrase ('estpd_boolean_operation_or');
    }
}