<?php
// Copyright 2020 Webstack. All rights reserved.
// 본 소프트웨어는 웹스택이 개발/운용하는 웹스택의 재산이나,
// 공공의 목적으로 개발되어 누구든지 자유롭게 소스코드를 활용할 수 있습니다.
// MIT 라이선스를 따르고 있으며, 관련 문의는 Github Repository 로 부탁드립니다.

if(!defined('__XE__'))
{
	exit();
}

// 라이브러리 로드
require_once(__DIR__ . '/functions.php');

// 애드온 설정 기본값 지정
$addon_info = AddonFunction::setDefaultAddonInfo($addon_info, [
]);