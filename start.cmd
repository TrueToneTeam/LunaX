@echo off
TITLE PocketMine-MP server software for Minecraft: Bedrock Edition
cd /d %~dp0

set PHP_BINARY=

where /q php.exe
if %ERRORLEVEL%==0 (
	set PHP_BINARY=php
)

if exist bin\php\php.exe (
	rem always use the local PHP binary if it exists
	set PHPRC=""
	set PHP_BINARY=bin\php\php.exe
)

if "%PHP_BINARY%"=="" (
	echo 시스템 경로 ["%~dp0bin\php"]에서 PHP 파일을 찾을 수 없습니다.
	echo https://doc.pmmp.io/en/rtfd/installation.html 이곳에서 설치 지침을 참고해주세요!
	pause
	exit 1
)

if exist PocketMine-MP.phar (
	set POCKETMINE_FILE=PocketMine-MP.phar
) else if exist src\PocketMine.php (
	set POCKETMINE_FILE=src\PocketMine.php
) else (
	echo PocketMine-MP.phar 또는 PocketMine.php를 찾을 수 없습니다.
	echo https://github.com/pmmp/PocketMine-MP/releases에서 파일을 다운로드 하실 수 있습니다.
	pause
	exit 1
)

if exist bin\mintty.exe (
	start "" bin\mintty.exe -o Columns=88 -o Rows=32 -o AllowBlinking=0 -o FontQuality=3 -o Font="Consolas" -o FontHeight=10 -o CursorType=0 -o CursorBlinks=1 -h error -t "PocketMine-MP" -i bin/pocketmine.ico -w max %PHP_BINARY% %POCKETMINE_FILE% --enable-ansi %*
) else (
	REM pause on exitcode != 0 so the user can see what went wrong
	%PHP_BINARY% %POCKETMINE_FILE% %* || pause
)
