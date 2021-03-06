<?php
/**
 * SitemapComponent Controller
 *
 * Pretty much just baked admin actions except add/edit use generateTreeList()
 * for finding the parents so you see the hierarchy.
 *
 * @author Juan Gimenez <neojoda@gmail.com>
 * @link http://www.montcat.org/portfolio
 * @copyright (c) 2014 Juan Gimenez
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php *
 */

//http://webdesign.about.com/od/localization/l/bllanguagecodes.htm
App::uses('Component', 'Controller');

class SitemapComponent extends Component {
	
	private $urls = array();
	private static $defaultExcludeActionsControllers = array("beforeFilter","__construct","__isset","__get","__set","setRequest","invokeAction","implementedEvents","constructClasses","getEventManager","startupProcess","shutdownProcess","httpCodes","loadModel","redirect","header","set","setAction","validate","validateErrors","render","referer","disableCache","flash","postConditions","paginate","beforeRender","beforeRedirect","afterFilter","beforeScaffold","afterScaffoldSave","afterScaffoldSaveError","scaffoldError","toString","requestAction","dispatchMethod","_stop","log","_set","_mergeVars");
	private $alternateLoc = array();
	private $modelToUse;
	
	/**
	 * 
	 * @param string $controllerName
	 * @param array $excludeActions
	 * @param array $alternateLoc
	 */
	public function addController($controllerName, $excludeActions = array(), $includeActions = array()) {
		
		$excludeActions = array_merge($excludeActions, self::$defaultExcludeActionsControllers);
		
		if ($controllerName == 'Pages') {
			$excludeActions[] = 'display';
		}
		
		App::import('Controller', $controllerName);
		$controllerMethods = get_class_methods($controllerName."Controller");
		
		foreach ($controllerMethods as $key => $action) {
			if (!in_array($action, $excludeActions) ) {
				$url['controller'] = strtolower($controllerName);
				$url['action'] = $action;
				$url['plugin'] = false;
				$this->_setUri($url);
			}
		}
		
		if(!empty($includeActions)) {
			foreach ($includeActions as $action) {
				$url['controller'] = strtolower($controllerName);
				$url['action'] = $action;
				$url['plugin'] = false;
				$this->_setUri($url);
			}
		}
	}
	
	/**
	 * 
	 * It gets all the records of a model
	 * $allArticles = $modelName->find('all');
	 * The default action to build the url is view
	 * 
	 * @param string $modelName
	 * @param string $controllerName
	 * @param string $action
	 * @param array $alternateLoc
	 */
	public function addModel ($modelName, $controllerName, $action = 'view', $idField = 'id', $conditions = array()) {
			
		$this->modelToUse = ClassRegistry::init($modelName);
		
		
		$results = $this->modelToUse->find("all", array("fields"=>array($modelName.".".$idField),
														"conditions" => $conditions));

		foreach($results as $result) {
			$url['controller'] = strtolower($controllerName);
			$url['plugin'] = false;
			$url['action'] = $action."/".$result[$modelName][$idField];
			$this->_setUri($url);
		}
		
	}
	
	/**
	 * Returns the sitemap XML content 
	 */
	public function getSitemap($includeHome = true) {
		
		$this->alternateLoc = Configure::read("Sitemapcake2.AlternateLoc");
		
		if ($includeHome) {
			$this->_setUri(Router::parse('/'), true);
		}
		
		$this->_loadControllers();
		$this->_loadModels();
		$this->_loadManuals();
		
		return $this->urls;
	}
	
	private function _loadControllers() {
		$controllers = Configure::read("Sitemapcake2.Controller");
		if ( ($controllers !== null) && is_array($controllers)) {
			foreach ($controllers as $controller) {
				$excludeActions = (isset($controller['excludeActions']) ? $controller['excludeActions'] : array());
				$includeActions = (isset($controller['includeActions']) ? $controller['includeActions'] : array());
		
				$this->addController($controller['name'], $excludeActions, $includeActions);
			}
		}
	}
	
	
	private function _loadModels() {
		$models = Configure::read("Sitemapcake2.Model");
		if ( ($models !== null) && is_array($models)) {
			foreach ($models as $model) {
		
				$view = (isset($model['action']) ? $model['action'] : 'view');
				$idField = (isset($model['idField']) ? $model['idField'] : 'id');
				$conditions = (isset($model['conditions']) ? $model['conditions'] : array());
				
				$this->addModel($model['name'], $model['controller'], $view, $idField, $conditions);
			}
		}
	}
	
	private function _loadManuals() {
		$manuals = Configure::read("Sitemapcake2.Manual");
		if (!empty($manuals)) {
			foreach ($manuals as $manual) {
				$keyAltLoc = 0;
				$key = count($this->urls);
				if (isset($manual['alternateLocs'])) {
					foreach ($manual['alternateLocs'] as $altLoc) {
						$this->urls[$key]['altLoc'][$keyAltLoc]['uri'] = Router::url($altLoc['uri'], true);
						if(isset($altLoc['lang'])) {
							$this->urls[$key]['altLoc'][$keyAltLoc]['hreflang'] = $altLoc['lang'];
						}
						$keyAltLoc++;
					}
				}
				$this->urls[$key]['url']= Router::url($manual['uri'], true);
			}
		}
	}
	
	private function _setUri($uri, $isHome = false) {
		$key = count($this->urls);
		
		if(isset($this->alternateLoc['altLocs'])) {	
			$keyAltLoc = 0;
			foreach ($this->alternateLoc['altLocs'] as $altLoc) {
			    $uriAltLoc = array_merge($uri,$altLoc['params']);
				$this->urls[$key]['altLoc'][$keyAltLoc]['uri'] = Router::url($uriAltLoc, true);
				if(isset($altLoc['lang'])) {
					$this->urls[$key]['altLoc'][$keyAltLoc]['hreflang'] = $altLoc['lang'];
				}
				$keyAltLoc++;
			}
		}
		
		if($isHome) {
			$this->urls[$key]['url']= Router::url("/", true);
		} else {
			$this->urls[$key]['url']= Router::url($uri, true);
		}
	}
	
}
