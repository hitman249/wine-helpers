
## Требования

* php 5.4 + 
* xrandr 1.5 +

```bash
# Для Ubuntu, winetricks зависимости:
sudo add-apt-repository ppa:ubuntu-wine/ppa
sudo apt-get update
sudo apt-get install binutils cabextract p7zip-full unrar unzip wget wine zenity
```
* Чтобы использовать Vulkan, его должны поддерживать:
  * Драйвера на видеокарту, утилита `vulkaninfo` должна говорить что всё ок.
  * Сама видеокарта должна поддерживать Vulkan.
  * Wine сборка должна быть не меньше 3.10 и собрана с поддержкой Vulkan, дистрибутивы из репозиториев ОС обычно собраны без Vulkan.
  * Только после этого можно использовать `dxvk` (d3d11)

## Установка

* создать папку (желательно без пробелов в пути и кириллицы)
* скопировать туда папку wine, сборки с поддержкой vulkan можно скачать [отсюда](https://yadi.sk/d/IrofgqFSqHsPu) 
* выполнить:

```bash
wget -q -O start https://raw.githubusercontent.com/hitman249/wine-helpers/master/start && chmod +x start
```

* после чего в папке у вас должны получиться 2 элемента, папка `wine` и рядом файл `start`
* запускаете файл `./start`
* у вас появится директория `game_info`
* редактируете файл `game_info/game_info.txt`
* запускаете `./start cfg` чтобы сконфигурировать версию эмулируемой ОС, либо `./start fm`,
 чтобы запустить файловый менеджер
* через файловый менеджер устанавливаете игру \ приложение
* если требуются дополнительные библиотеки копируете их в папки `dlls` и `dlls64`
  - скрипт создаст симлинки файлов в директорию:
    - `dlls`   -> `windows/system32`
    - `dlls64` -> `windows/syswow64`
* если требуется использовать `*.reg` файлы, копируйте их в папку `regs`
* если требуется `winetricks` используйте команду `./start winetricks d3dx9 dxvk63`
* в папке `additional` можно пробросить папки для сохранений, чтобы они не удалились вместе с `prefix`-ом
* файлы игры \ программы должны быть в папке `data`
* в конце удаляете все лишние файлы и папку `prefix`
* запускаете `./start`, игра \ программа должна запуститься
* если всё корректно запускается, снова удаляете все лишние файлы и папку `prefix`
* готово. Игру \ программу можно запаковывать


## Описание 

Этот скрипт предназначен в первую очередь для облегчения
создания и распространения Wine раздач игр/программ, но может применяться
и в других целях. Работа скрипта гарантируется на всех дистрибутивах
Linux, где установлены стандартные утилиты GNU, оболочка bash и php 5.4+.


## Help

```text
Help:
./start                           - Run game.
./start winetricks d3dx9          - Winetricks install d3dx9.
./start cfg                       - Configure.
./start fm                        - File Manager.
./start regedit (reg)             - Windows Registry Editor.
./start kill                      - Kill this instance Wine.
./start help

./start diff                      - Enable change files analyze from system32, syswow64 folders.
or
./start diff fm
./start diff winetricks d3dx9
./start diff cfg
and others

./start debug                    - Enable debug mode, work analog "diff".
./start wine                     - Get Wine Instance.
./start config game_info1.ini    - Use other config.
./start update                   - Update this script.
./start icon                     - Create desktop icon.
./start icon delete (remove)     - Delete desktop icon.
./start version
```

## Возможности

* Автоматизирует работу с отдельным wine префиксом
* Перед запуском игры сохраняет разрешение, яркость и гамму на каждом мониторе в отдельности.
* После завершения игры восстанавливает только изменившиеся параметры разрешение, яркость и гамму, 
в отдельности по каждому параметру и монитору. Т.е. если изменилась гамма на втором мониторе, то 
только она и будет исправлена.
* При использовании `winetricks` он автоматически выкачивается.
* Показывает недостающие либы wine
* Показывает используемую версию Wine, Vulkan, xrandr, winetricks
* Если после `./start` следует аттрибут `diff` а затем команда, то как только команда отработает 
скрипт покажет изменившиеся файлы в директориях `system32`, `syswow64`
* Использование ini файла для настроек:
```ini
[game]
path = "Program Files/The Super Game"
additional_path = "bin"
exe = "Game.exe"
cmd = "-language=russian"
name = "The Super Game: Deluxe Edition"
version = "1.0.0"

[script]
csmt = 1
dxvk = 0
winetricks = 0

; Not use /home/user directory
sandbox = 1

; Windows version (win7, winxp, win2k)
winver = "win7"

; Set sound driver to PulseAudio or ALSA
pulse = 1

; Auto fixed resolution, brightness, gamma for all monitors
fixres = 1

[wine]
WINEDEBUG = "-all"
WINEARCH = "win32"
WINEDLLOVERRIDES = ""

[window]
enable = 0
title = "Wine"
resolution = "800x600"

;
; Additional dlls folder logic
; Example: dll[name_file.dll] = "nooverride"
;
; Variables:
; "builtin"        - Встроенная
; "native"         - Сторонняя (default)
; "builtin,native" - Встроенная, Сторонняя
; "native,builtin" - Сторонняя, Встроенная
; "nooverride"     - Не заносить в реестр
; "register"       - Зарегистрировать библиотеку через regsvr32
;
; Настройки относятся только к папке dlls, которая создаёт симлинки в папку system32
;
[dlls]
; dll[d3d11.dll] = "nooverride"
; dll[l3codecx.ax] = "register"

;
; Хуки
; after_create_prefix - команды выполняются после создания префикса
; before_run_game - команды выполняются перед запуском игры
; after_exit_game - команды выполняются после завершения игры
;
[hooks]
; after_create_prefix[] = "create.sh"
; before_run_game[] = "before.sh"
; after_exit_game[] = "after.sh"
; after_exit_game[] = "after2.sh"
```  
* Использование нескольких конфигураций
* Выбор между PulseAudio и ALSA
* Автоматическое скачивание последнего dxvk
* Выбор версии windows
* Возможность использования нескольких ini файлов. Для совмещения нескольких игр в одном префиксе.
* Возможность использования хуков после создания префикса, перед запуском и после остановки приложения.
* Выставление для каждой dll индивидуальных настроек.
* Возможность зарегистрировать библиотеку через regsvr32.
* Если в системе не установлен PulseAudio, скрипт автоматически переключит wine на ALSA. 

  Пример:

```text
change system32 files
--------------------
d3d11.dll
dxgi.dll
--------------------


delete system32 files
--------------------
d3d8.dll
d3dcompiler_33.dll
--------------------
```
* Библиотеки из папок `dlls` и `dlls64` применяются сразу без пересоздания префикса.
* Обеспечена корректная установка **.NET Framework**
* "Умное" создание иконки, создаёт в папке `Games` \ `Игры` если она присутствует на рабочем столе, также ищет файл 
без расширения `.desktop` (иногда нужна иконка без расширения), ищет `png` файлы в качестве картинки, в папках `./` и 
`./game_info`, если таковых несколько предлагает выбрать конкретную.
 
 ## Полезные ссылки
 
 * dxvk [GPU driver support](https://github.com/doitsujin/dxvk/wiki/Driver-support)
 * dxvk [releases](https://github.com/doitsujin/dxvk/releases)
 * wine builds for support vulkan [yandex disk](https://yadi.sk/d/IrofgqFSqHsPu) 
 or [google disk](https://drive.google.com/open?id=1fTfJQhQSzlEkY-j3g0H6p4lwmQayUNSR)
 * [wine standalone](https://lutris.net/files/runners/)
 * [Installer Repacks](https://repacks.net/)
 