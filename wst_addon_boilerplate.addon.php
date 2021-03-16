<?php
// Copyright 2020 Webstack. All rights reserved.
// 본 소프트웨어는 웹스택이 개발/운용하는 웹스택의 재산으로, 허가없이 무단 이용할 수 없습니다.
// 무단 복제 또는 배포 등의 행위는 관련 법규에 의하여 금지되어 있으며, 위반시 민/형사상의 처벌을 받을 수 있습니다.
// 관련 문의는 웹사이트<https://webstack.me/> 또는 이메일<admin@webstack.me> 로 부탁드립니다.

if(!defined('__XE__'))
{
	exit();
}

// 라이브러리 로드
require_once(__DIR__ . '/functions.php');

// 애드온 설정 기본값 지정
$addon_info = AddonFunction::setDefaultAddonInfo($addon_info, [
]);