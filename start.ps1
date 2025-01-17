﻿[CmdletBinding(PositionalBinding=$false)]
param (
	[string]$php = "",
	[switch]$Loop = $true,
	[string]$file = "",
	[string][Parameter(ValueFromRemainingArguments)]$extraPocketMineArgs
)

if($php -ne ""){
	$binary = $php
}elseif(Test-Path "bin\php\php.exe"){
	$env:PHPRC = ""
	$binary = "bin\php\php.exe"
}elseif((Get-Command php -ErrorAction SilentlyContinue)){
	$binary = "php"
}else{
	echo "시스템 경로 [$pwd\bin\php]에서 PHP 파일을 찾을 수 없습니다."
	echo "https://doc.pmmp.io/en/rtfd/installation.html 이곳에서 설치 지침을 참고해주세요!"
	pause
	exit 1
}

if($file -eq ""){
	if(Test-Path "PocketMine-MP.phar"){
	    $file = "PocketMine-MP.phar"
	}elseif(Test-Path "src\PocketMine.php"){
	    $file = "src\PocketMine.php"
	}else{
	    echo "PocketMine-MP.phar 또는 PocketMine.php를 찾을 수 없습니다."
	    echo "https://github.com/pmmp/PocketMine-MP/releases에서 파일을 다운로드 하실 수 있습니다."
	    pause
	    exit 1
	}
}

function StartServer{
	$command = "powershell -NoProfile " + $binary + " " + $file + " " + $extraPocketMineArgs
	iex $command
}

$loops = 0

StartServer

while($Loop){
	if($loops -ne 0){
		echo ("구동기 실행 후 재부팅된 횟수: " + $loops + "번")
	}
	$loops++
	echo "잠시후 서버가 자동으로 다시 시작됩니다."
	Start-Sleep 1
	StartServer
}