<?php
/**
 * Контроллер маршрута "private-discussion"
 * @package     Esthetic_PD
 * @serial      
 * @author      viodele <viodele@gmail.com>
 */
class Esthetic_PD_Route_Prefix_Private implements XenForo_Route_Interface {
    
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
      * Парсинг совпадений маршрута
      * @param  string                          $routePath
      * @param  Zend_Controller_Request_Http    $request
      * @param  XenForo_Router                  $router
      * @return XenForo_RouteMatch|false
      */
    public function match($routePath, Zend_Controller_Request_Http $request, XenForo_Router $router) {
		$action = $router->resolveActionWithIntegerParam($routePath, $request, 'thread_id');
		return $router->getRouteMatch('Esthetic_PD_ControllerPublic_Private', $action);
    }
    

	public function buildLink($originalPrefix, $outputPrefix, $action, $extension, $data, array &$extraParams) {
        return XenForo_Link::buildBasicLinkWithIntegerParam($outputPrefix, $action, $extension, $data, 'thread_id');
	}
}
