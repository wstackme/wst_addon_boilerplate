# Addon Boilerplate

XE와 Rhymix 모두에서 사용할 수 있는 애드온 보일러플레이트입니다.

애드온 내에서 자주 사용되는 함수들을 포함하고 있습니다.



# functions.php

`functions.php` 는 본 자료에서 제공하는 함수들을 포함하는 파일입니다.

`AddonFunction` 이라는 이름의 클래스를 제공하며, 애드온 내에서 `AddonFunction::methodName()` 과 같은 형식으로 불러와 사용할 수 있습니다.

여러 애드온에서 불러와도 충돌이 일어나지 않도록, 본 파일은 첫 설치 시 `./files/webstack/boilerplates/addon.php` 에 복사됩니다. 각 애드온 내 `functions.php`의 버전을 비교하여, 복사된 버전보다 상위 버전의 파일을 보유하고 있을 경우 자동으로 해당 경로로 복사됩니다.



# AddonFunction

`AddonFunction` 클래스를 활용하기 위해서는 아래 코드를 통해 `functions.php` 를 로드해야 합니다.

아래 코드는 애드온의 시작부 예시이며, 특별한 이유가 없다면 코드를 그대로 사용할 것을 권장합니다.

```PHP
<?php
if(defined('__XE__'))
{
	exit();
}

require_once(__DIR__ . '/functions.php');
```

## 기초 함수

### isRhymix

현재 코어의 Rhymix 여부를 반환합니다.

```PHP
if(AddonFunction::isRhymix())
{
	echo 'I\'m Rhymix!';
}
```

### isXE

현재 코어의 XE 여부를 반환합니다. !isRhymix() 의 alias 입니다.

```PHP
if(AddonFunction::isXE())
{
	echo 'Yes, I\'m XE!';
}
else
{
	echo 'No, I\'m Rhymix!';
}
```

### getAddonName

함수를 호출한 애드온의 이름을 반환합니다.

addon.php 파일의 경로를 통해 조회하여, 애드온을 복제하여 사용해야 할 경우 요긴하게 사용할 수 있습니다.

```PHP
$query_id = sprintf('addons.%s.getDocument', AddonFunction::getAddonName());
$output = executeQueryArray($query_id, $args);
```

### setDefaultAddonInfo

넘겨받은 `$addon_info` 의 기본값을 `$value_map` 을 참고하여 설정합니다.

```PHP
$addon_info = AddonFunction::setDefaultAddonInfo($addon_info, [
	/**
	 * $addon_info->extra_vars_1 의 기본값은 ''
	 */
	'extra_vars_1' => '',

	/**
	 * $addon_info->extra_vars_2 의 기본값은 'Hello, World!'
	 */
	'extra_vars_2' => 'Hello, World!',

	/**
	 * $addon_info->extra_vars_3 의 값은 true 또는 false
	 * 이미 해당 값이 존재한다면, 'Y'인 경우 true 로 매핑됨.
	 * 값이 존재하지 않는다면 'default' 의 값을 따름.
	 */
	'extra_vars_3' => [
		'type' => 'YN',
		'default' => true
	],

	/**
	 * $addon_info->extra_vars_4 의 값은 int 형
	 * 전달받은 값에 intval() 을 씌워 매핑됨.
	 */
	'extra_vars_4' => [
		'type' => 'INT',
		'default' => 10
	]
]);
```

## 트리거 함수

### addTriggerFunction

함수를 트리거로 등록합니다.

하나의 애드온 내에서 동일한 트리거를 여러개 등록해야 할 경우, 4번째 인자로 트리거의 순서(int)를 전달해 주어야 합니다.

```PHP
AddonFunction::addTriggerFunction('document.insertDocument', 'before', function($obj){
	return true;
});
```

## 캐시 함수

### setCache

캐시를 생성합니다.

기본적으로 `./files/webstack/addons/애드온명/` 경로에 저장되며, 캐시의 경로를 세분화 하고 싶은 경우 첫번째 인자에 경로를 전달하세요.

```PHP
/**
 * files/webstack/addons/wst_addon_boilerplate/test.php
 * 기본 유효시간 600초 적용
 */
AddonFunction::setCache('test', 'Test Cache');

/**
 * files/webstack/addons/wst_addon_boilerplate/sub/test.php
 * 유효시간 300초 적용
 */
AddonFunction::setCache('sub/test', 'Sub Test', 300);

/**
 * 유효시간 없음
 * 데이터 보관용으로도 사용할 수 있음
 */
AddonFunction::setCache('no_expires', 'WAKANDA FOREVER', -1);
```

### getCache

캐시를 불러옵니다.
유효기간이 지난 경우 `null` 을 반환합니다.

```PHP
AddonFunction::getCache('test');
```

### deleteCache

캐시를 삭제합니다.

```PHP
AddonFunction::deleteCache('sub/test');
```

### createCacheDirectory

캐시가 저장될 폴더를 생성하고, 그 경로를 반환합니다. 폴더가 이미 존재할 경우 생성하지 않고 경로만 반환합니다.

setCache, getCache, deleteCache 실행 시 자동으로 폴더를 생성하므로, 특이한 경우를 제외하면 직접 호출할 일은 없습니다.



# 라이선스

본 소프트웨어는 웹스택이 개발/운용하는 웹스택의 재산이나, 공공의 목적으로 개발되어 누구든지 자유롭게 소스코드를 활용할 수 있습니다.

MIT 라이선스를 따르고 있으며, 문의사항은 이슈트래커 또는 아래 채널로 등록해 주세요.

* 웹사이트: https://webstack.me/qna
* 카카오톡 채널: http://pf.kakao.com/_KkxjxaT
* 디스코드: 웹스택#0001