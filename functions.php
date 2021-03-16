<?php
// Version 4.0.0
// Copyright 2020 Webstack. All rights reserved.
// 본 파일은 웹스택이 개발/운용하는 웹스택의 재산이나,
// 공공의 목적으로 개발되어 누구든지 자유롭게 소스코드를 활용할 수 있습니다.
// MIT 라이선스를 따르고 있으며, 관련 문의는 Github Repository 로 부탁드립니다.

if(!defined('__XE__'))
{
    exit();
}

$ADDON_BOILERPLATE_VERSION = '4.0.0';
$ADDON_BOILERPLATE_DIR = str_replace('\\', '/', __DIR__);

if(end(explode('/', $ADDON_BOILERPLATE_DIR)) != 'boilerplate')
{
    if(!file_exists(_XE_PATH_ . 'files/webstack/boilerplate/addon.php'))
    {
        if(!is_dir(_XE_PATH_ . 'files/webstack/boilerplate/'))
        {
            mkdir(_XE_PATH_ . 'files/webstack/boilerplate/', 0777, true);
        }

        file_put_contents(_XE_PATH_ . 'files/webstack/boilerplate/addon.php', file_get_contents($ADDON_BOILERPLATE_DIR . '/functions.php'));
    }

    else
    {
        $f_handler = fopen(_XE_PATH_ . 'files/webstack/boilerplate/addon.php', 'r');

        fgets($f_handler);
        $f_line = fgets($f_handler);

        $version = str_replace('// Version ', '', $f_line);
        if(version_compare(trim($version), $ADDON_BOILERPLATE_VERSION, '<'))
        {
            file_put_contents(_XE_PATH_ . 'files/webstack/boilerplate/addon.php', file_get_contents($ADDON_BOILERPLATE_DIR . '/functions.php'));
		}
	}

	require_once(_XE_PATH_ . 'files/webstack/boilerplate/addon.php');
    return;
}

if(!class_exists('AddonFunction'))
{
    class AddonFunction
    {
        /**
         * isRhymix
         * 
         * 현재 코어의 Rhymix 여부를 반환합니다.
         * 
         * @return bool
         */
        public static function isRhymix()
        {
            return defined('RX_BASEDIR');
        }

        /**
         * isXE
         * 
         * 현재 코어의 XE 여부를 반환합니다.
         * 
         * @return bool
         */
        public static function isXE()
        {
            return !AddonFunction::isRhymix();
        }

        /**
         * getAddonName
         * 
         * 현재 작동중인 애드온의 이름을 반환합니다.
         * addon.php 파일의 경로를 통해 조회하여, 복제된 애드온에서도 정상적으로 작동합니다.
         * 
         * @param int   $trace    함수 호출 trace 값
         * 
         * @return string
         */
        public static function getAddonName($trace = 0)
        {
            $backtrace = debug_backtrace();
            $dirname = str_replace('\\', '/', dirname($backtrace[$trace]['file']));
            $addon_name = end(explode('/', $dirname));

            return $addon_name;
        }

        /**
         * addTriggerFunction
         * 
         * 함수를 트리거로 등록합니다.
         * Rhymix 환경에서는 추가적으로 지원되는 함수를 활용하여 처리합니다.
         * 
         * @param string    $trigger_name       추가하려는 트리거의 이름
         * @param string    $trigger_position   추가하려는 트리거의 호출 순서
         * @param function  $trigger_func       추가하려는 트리거 함수
         * @param int       $trigger_count      동일한 애드온에서 동일한 트리거에 함수를 등록할 경우, 트리거의 순서
         */
        public static function addTriggerFunction($trigger_name, $trigger_position, $trigger_func, $trigger_count = 0)
        {
            $trigger_registered = false;

            // Rhymix 환경일 경우
            if(AddonFunction::isRhymix())
            {
                // addTriggerFunction 메소드가 존재할 경우
                $oModuleController = getController('module');
                if(method_exists($oModuleController, 'addTriggerFunction'))
                {
                    $oModuleController->addTriggerFunction($trigger_name, $trigger_position, $trigger_func);
                    $trigger_registered = true;
                }
            }

            // 트리거가 등록되지 않았다면
            if(!$trigger_registered)
            {
                $addon_name = AddonFunction::getAddonName(1);
                $trigger_code = sha1(sprintf('%s_%s_%s_%s', $addon_name, $trigger_name, $trigger_position, $trigger_count));
                $class_name = 'addon_boilerplate_' . $trigger_code;

                // 클래스 생성
                $class_string = sprintf('class %s { public function triggerFunction(&$args){ return $GLOBALS["WEBSTACK"]["addons"]["%s"]["functions"]["%s"]($args); } }', $class_name, $addon_name, $trigger_code);
                eval($class_string);

                // 트리거 함수 및 클래스 등록
                $GLOBALS['WEBSTACK']['addons'][$addon_name]['functions'][$trigger_code] = $trigger_func;
                $GLOBALS['_loaded_module'][$class_name]['controller']['svc'] = new $class_name;

                // 트리거 객체 생성 및 등록
                $trigger = new stdClass();
                $trigger->trigger_name = $trigger_name;
                $trigger->called_position = $trigger_position;
                $trigger->module = $class_name;
                $trigger->type = 'controller';
                $trigger->called_method = 'triggerFunction';

                $GLOBALS['__triggers__'][$trigger_name][$trigger_position][] = $trigger;
            }
        }

        /**
         * setCache
         * 
         * 캐시를 생성합니다.
         * 
         * @param string    $path           캐시 파일의 이름
         * @param mixed     $data           캐시 파일에 저장할 데이터
         * @param int       $expire_time    캐시 파일의 유효 시간 (초 단위)
         */
        public static function setCache($path, $data, $expire_time = 600)
        {
            // 캐시 파일 경로 조회
            $cache_dir = AddonFunction::createCacheDirectory();
			$cache_path = $cache_dir . $path . '.php';
			
			// 캐시 파일 내 슬래시 포함 시 폴더 생성
			if(strpos($path, '/') !== false)
			{
				$path_t = explode('/', $path);
				array_pop($path_t);

				$cache_dir_t = $cache_dir . implode('/', $path_t);
				if(!is_dir($cache_dir_t))
				{
					mkdir($cache_dir_t, 0777, true);
				}
			}

            // 유효시간 계산
            $expired_at = (time() + $expire_time);
            if($expire_time == -1)
            {
                $expired_at = -1;
            }

            // 캐시 파일 작성 내용 생성
            $file_content = '<?php ';
            $file_content .= 'if(!defined("__XE__")) exit();';
            $file_content .= '$expired_at = ' . $expired_at . ';';
            $file_content .= 'if($expired_at != -1 && time() > $expired_at) return;';
            $file_content .= 'return unserialize(base64_decode("' . base64_encode(serialize($data)) . '"));';

            // 캐시 파일 작성
            file_put_contents($cache_path, $file_content);
            return true;
        }

        /**
         * getCache
         * 
         * 캐시를 불러옵니다.
         * 
         * @param string    $path   캐시 파일의 이름
         * 
         * @return mixed
         */
        public static function getCache($path)
        {
            // 캐시 파일 경로 조회
            $cache_dir = AddonFunction::createCacheDirectory();
            $cache_path = $cache_dir . $path . '.php';

            // 캐시 파일 존재 여부 확인
            if(!file_exists($cache_path))
            {
                return;
            }

            // 캐시 파일 유효기간 확인 후 유효기간 초과 시 캐시파일 삭제
            $data = include($cache_path);
            if($data === null)
            {
                unlink($cache_path);
            }

            return $data;
        }

        /**
         * deleteCache
         * 
         * 캐시를 삭제합니다.
         * 
         * @param string    $path   캐시 파일의 이름
         */
        public static function deleteCache($path)
        {
            // 캐시 파일 경로 조회
            $cache_dir = AddonFunction::createCacheDirectory();
            $cache_path = $cache_dir . $path . '.php';

            unlink($cache_path);
        }

        /**
         * createCacheDirectory
         *
         * 애드온의 캐시 폴더를 생성하고 그 경로를 반환합니다.
         * 폴더가 이미 존재할 경우 생성하지 않고 경로만 반환합니다.
         *
         * @return string
         */
        public static function createCacheDirectory()
        {
            $cache_dir = './files/webstack/addons/' . AddonFunction::getAddonName(2) . '/';
            if(!is_dir($cache_dir))
            {
                mkdir($cache_dir, 0777, true);
            }

            return $cache_dir;
        }

        /**
         * setDefaultAddonInfo
         *
         * 넘겨받은 $addon_info 의 기본값을 설정합니다.
         *
         * @param object $addon_info    현재 애드온의 $addon_info
         * @param array  $value_map     기본값에 대한 매핑 배열 { $key => $value }
         *
         * @return object
         */
        public static function setDefaultAddonInfo($addon_info, $value_map)
        {
            foreach($value_map as $key => $value)
            {
                if(!isset($addon_info->$key) || trim($addon_info->$key) == '')
                {
                    // 설정 타입 지정 시
                    if(is_array($value))
                    {
                        if(isset($value['type']))
                        {
                            switch($value['type'])
                            {
                                case 'YN':
                                    if(is_bool($value['default']))
                                    {
                                        $value = $value['default'];
                                    }
                                    elseif(in_array($value['default'], ['Y', 'N']))
                                    {
                                        $value = ($value['default'] == 'Y');
                                    }
                                break;

                                case 'INT':
                                    $value = intval($value['default']);
                                break;
                            }
                        }
                    }

                    $addon_info->$key = $value;
                }
                else
                {
                    // 설정 타입 지정 시
                    if(is_array($value))
                    {
                        if(isset($value['type']))
                        {
                            switch($value['type'])
                            {
                                case 'YN':
                                    $addon_info->$key = ($addon_info->$key == 'Y');
                                break;

                                case 'INT':
                                    $addon_info->$key = intval($addon_info->$key);
                                break;
                            }
                        }
                    }
                }
			}
			
            return $addon_info;
        }

        /**
         * compareAct
         * 
         * 현재 act 의 값을 비교합니다.
         * 
         * @param string|array $act     비교할 act 값
         * 
         * @return bool
         */
        public static function compareAct($act)
        {
            $current_act = Context::get('act');
            if(!is_array($act))
            {
                return $current_act == $act;
            }
            else
            {
                return in_array($current_act, $act);
            }
        }

        /**
         * compareAddonAct
         * 
         * 현재 addon_act 의 값을 비교합니다.
         * 
         * @param string|array $addon_act     비교할 addon_act 값
         * 
         * @return bool
         */
        public static function compareAddonAct($addon_act)
        {
            $current_act = Context::get('addon_act');
            if(!is_array($addon_act))
            {
                return $current_act == $addon_act;
            }
            else
            {
                return in_array($current_act, $addon_act);
            }
        }

        /**
         * compareSubAct
         * 
         * 현재 sub_act 의 값을 비교합니다.
         * 
         * @param string|array $sub_act     비교할 sub_act 값
         * 
         * @return bool
         */
        public static function compareSubAct($sub_act)
        {
            $current_act = Context::get('sub_act');
            if(!is_array($sub_act))
            {
                return $current_act == $sub_act;
            }
            else
            {
                return in_array($current_act, $sub_act);
            }
        }

        /**
         * getDocument
         * 
         * 게시글 객체를 가져옵니다.
         * document_srl 이 지정되지 않았을 경우, 현재 활성화된 게시글을 가져옵니다.
         * 
         * @param int   $document_srl
         * 
         * @return documentItem
         */
        public static function getDocument($document_srl = null)
        {
            if($document_srl === null)
            {
                $oDocument = Context::get('oDocument');
                if($oDocument !== null)
                {
                    return $oDocument;
                }

                $document_srl = Context::get('document_srl');
                if($document_srl == null)
                {
                    return null;
                }
            }

            $oDocumentModel = getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            return $oDocument;
		}
		
        /**
         * setGlobalAddonInfo
         * 
         * $addon_info 를 $GLOBALS 에 등록합니다.
         * 함수 등에서 $addon_info 로 접근할 수 없을 때 사용할 수 있습니다.
         * 
         * @param object $addon_info
         * @param string $addon_name   애드온 이름
         */
        public static function setGlobalAddonInfo($addon_info, $addon_name = null)
        {
            if($addon_name === null)
            {
                $addon_name = AddonFunction::getAddonName(1);
            }

            AddonFunction::setGlobal('_addon_info', $addon_info, $addon_name);
        }

        /**
         * getGlobalAddonInfo
         * 
         * setGlobalAddonInfo 로 등록한 글로벌 $addon_info 를 반환합니다.
         * 
         * @param string $addon_name   애드온 이름
         * 
         * @return object
         */
        public static function getGlobalAddonInfo($addon_name = null)
        {
            if($addon_name === null)
            {
                $addon_name =  AddonFunction::getAddonName(1);
            }

            return AddonFunction::getGlobal('_addon_info', null, $addon_name);
        }

        /**
         * setGlobal
         * 
         * 변수를 $GLOBALS 에 등록합니다.
         * 함수 등에서 외부 변수에 접근해야 할 때 이용할 수 있습니다.
         * 
         * @param string $key           변수 키
         * @param string $value         변수 값
         * @param string $addon_name    애드온 이름
         */
        public static function setGlobal($key, $value = null, $addon_name = null)
        {
            if($addon_name === null)
            {
                $addon_name =  AddonFunction::getAddonName(1);
            }

            $GLOBALS['WEBSTACK']['addons'][$addon_name]['variables'][$key] = $value;
        }

        /**
         * getGlobal
         * 
         * 변수를 $GLOBALS 에서 가져옵니다.
         * 함수 등에서 외부 변수에 접근해야 할 때 이용할 수 있습니다.
         * 
         * @param string $key           변수 키
         * @param string $addon_name    애드온 이름
         */
        public static function getGlobal($key, $default = null, $addon_name = null)
        {
            if($addon_name === null)
            {
                $addon_name =  AddonFunction::getAddonName(1);
            }

            return $GLOBALS['WEBSTACK']['addons'][$addon_name]['variables'][$key] ? : $default;
		}
		
		/**
		 * setGlobalVariable [LEGACY]
		 * 
		 * setGlobal 의 alias
		 */
		public static function setGlobalVariable($key, $value = null, $addon_name = null)
		{
			return AddonFunction::setGlobal($key, $value, $addon_name);
		}

		/**
		 * getGlobalVariable [LEGACY]
		 * 
		 * getGlobal 의 alias
		 */
		public static function getGlobalVariable($key,  $addon_name = null)
		{
			return AddonFunction::getGlobal($key, null, $addon_name);
		}

        /**
         * setSession
         * 
         * 애드온 전용 세션값을 설정합니다.
         * 
         * @param string    $key    세션 키
         * @param mixed     $value  저장할 세션 값
         */
        public static function setSession($key, $value, $addon_name = null)
        {
			if(is_null($addon_name))
			{
				$addon_name = AddonFunction::getAddonName(1);
			}

            $_SESSION['WEBSTACK']['addons'][$addon_name][$key] = $value;
        }
        
        /**
         * getSession
         * 
         * setSession 으로 설정된 애드온 전용 세션값을 반환합니다.
         * 
         * @param string    $key    세션 키
         * 
         * @return mixed
         */
        public static function getSession($key, $default = null, $addon_name = null)
        {
            if(is_null($addon_name))
			{
				$addon_name = AddonFunction::getAddonName(1);
			}

            $session_block = $_SESSION['WEBSTACK']['addons'][$addon_name];
            if(isset($session_block[$key]))
            {
                return $session_block[$key];
            }

            return null;
        }

        /**
         * deleteSession
         * 
         * setSession 으로 설정된 애드온 전용 세션값을 삭제합니다.
         *
         * @param string    $key    세션 키
         */
        public static function deleteSession($key, $addon_name = null)
        {
            if(is_null($addon_name))
			{
				$addon_name = AddonFunction::getAddonName(1);
			}

            unset($_SESSION['WEBSTACK']['addons'][$addon_name]);
		}
		
		/**
		 * setAddonSession [LEGACY]
		 * 
		 * setSession 의 alias
		 */
		public static function setAddonSession($key, $value)
		{
			$addon_name = AddonFunction::getAddonName(1);
			return AddonFunction::setSession($key, $value, $addon_name);
		}

		/**
		 * getAddonSession [LEGACY]
		 * 
		 * getSession 의 alias
		 */
		public static function getAddonSession($key)
		{
			$addon_name = AddonFunction::getAddonName(1);
			return AddonFunction::getSession($key, null, $addon_name);
		}

		/**
		 * deleteAddonSession [LEGACY]
		 * 
		 * deleteSession 의 alias
		 */
		public static function deleteAddonSession($key)
		{
			$addon_name = AddonFunction::getAddonName(1);
			return AddonFunction::deleteSession($key, $addon_name);
		}

        /**
         * setJSLocation
         * 
         * location.href 값을 설정하는 스크립트를 출력합니다.
         * 
         * @param string    $url	이동할 URL
         */
        public static function setJSLocation($url)
        {
            $script = '<script>';
            $script .= 'location.href = "' . $url . '";';
            $script .= '</script>';

            echo $script;
        }

        /**
         * replaceJSLocation
         * 
         * location.replace 를 실행하는 스크립트를 출력합니다.
         * 
         * @param string    $url	이동할 URL
         */
        public static function replaceJSLocation($url)
        {
            $script = '<script>';
            $script .= 'location.replace("' . $url . '");';
            $script .= '</script>';

            echo $script;
        }

        /**
         * setJSVariable
         * 
         * 변수 묶음을 JS 로 전달합니다.
         * JS 파일에서 window.wst_addon_name 형식으로 접근할 수 있습니다.
         * 
         * @param (object|array) $variable	JS로 전달할 변수 묶음
         */
        public static function setJSVariable($variable)
        {
            $raw_data = base64_encode(json_encode($variable));
            $script = '<script> window.' . AddonFunction::getAddonName(1) . ' = JSON.parse(atob("' . $raw_data . '")); </script>';
            Context::addHtmlHeader($script);
        }

        /**
         * responseJSON
         * 
         * JSON 형식으로 응답을 발송합니다.
         * 
         * @param (object|array) $json_body	응답할 객체
         */
        public static function responseJSON($json_body)
        {
            $json_raw = json_encode($json_body);

            header('Content-Type: application/json');
            print($json_raw);
            exit();
        }

        /**
         * loadFile
         * 
         * CSS/JS 파일을 첨부합니다.
         * 
         * @param string    $path
         * @param boolean   $is_body
         */
        public static function loadFile($path, $is_body = false)
        {
            if(end(explode('.', $path)) == 'css')
            {
                Context::loadFile($path);
            }
            else
            {
                Context::loadFile(array($path, $is_body ? 'body' : 'head', '', null), true);
            }
        }

		/**
		 * compileTemplate
		 * 
		 * 템플릿을 컴파일합니다.
		 * 
		 * @param string	$template_dir
		 * @param string	$template_file
		 */
		public static function compileTemplate($template_dir, $template_file)
		{
			if(!file_exists($template_dir . '/' . $template_file))
			{
				return sprintf('Specified template file(\'%s\') not exists.', $template_dir . '/' . $template_file);
			}

			$oTemplate = TemplateHandler::getInstance();
			$template_compiled = $oTemplate->compile($template_dir, $template_file);

			return $template_compiled;
		}
	}	
}
