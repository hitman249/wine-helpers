
## Требования

* php 5.4 + 
* xrandr 1.5 +

```bash
# Для Ubuntu, winetricks зависимости:
sudo add-apt-repository ppa:ubuntu-wine/ppa
sudo apt-get update
sudo apt-get install binutils cabextract p7zip-full unrar unzip wget wine zenity
```

```bash
# Для Ubuntu, зависимости самого скрипта
sudo apt-get install x11-xserver-utils wget php php-cli php-readline php-curl php-gd php-common php-bz2 php-json php-mbstring php-timer php-zip
```

```bash
# Для Ubuntu, проще установить wine из репозиториев, тогда необходимые зависимости установятся сами.
# Остальное подскажет сам скрипт в процессе использования.

sudo apt-get install wine
```

* Чтобы использовать Vulkan, его должны поддерживать:
  * Драйвера на видеокарту, утилита `vulkaninfo` должна говорить что всё ок.
  * Сама видеокарта должна поддерживать Vulkan.
  * Wine сборка должна быть не меньше 3.10 и собрана с поддержкой Vulkan, дистрибутивы из репозиториев ОС обычно собраны без Vulkan.
  * Только после этого можно использовать `dxvk` (d3d11)
  * PS: Vulkan не обязательная опция, необходимая только для поддержки dx11, если игра dx9-10, то Vulkan не требуется.

## Установка

1) Создать папку (желательно без пробелов в пути и кириллицы)

2) Скопировать туда папку wine, сборки с поддержкой vulkan можно скачать [отсюда](https://yadi.sk/d/IrofgqFSqHsPu), выполнить:
```bash
wget -q -O start https://raw.githubusercontent.com/hitman249/wine-helpers/master/start && chmod +x start
```
3) После чего в папке у вас должны получиться 2 элемента, папка `wine` и рядом файл `start`

4) Запускаете файл `./start`

5) Появится директория `game_info`, редактируете файл `game_info/game_info.ini`

6) Запускаете `./start cfg` чтобы сконфигурировать версию эмулируемой ОС, либо `./start fm`, чтобы запустить 
файловый менеджер.

7) Через файловый менеджер `./start fm` устанавливаете игру.

8) Если требуются дополнительные библиотеки копируете их в папки `dlls` и `dlls64` скрипт создаст симлинки 
файлов в директорию:
    - `dlls`   -> `windows/system32`
    - `dlls64` -> `windows/syswow64`
    
9) Если требуется использовать `*.reg` файлы, копируйте их в папку `regs`

10) Если требуется `winetricks` используйте команду `./start winetricks d3dx9 dxvk63`

11) В папке `additional` можно пробросить папки для сохранений, чтобы они не удалились вместе с `prefix`-ом

12) Файлы игры должны быть в папке `data`

13) В конце удаляете все лишние файлы и папку `prefix`

14) Запускаете `./start`, игра должна запуститься.

15) Если всё корректно запускается, снова удаляете все лишние файлы и папку `prefix`

16) Готово. Игру можно запаковывать.


## Описание 

Этот скрипт предназначен в первую очередь для облегчения
создания и распространения Wine раздач игр/программ, но может применяться
и в других целях. Работа скрипта гарантируется на всех дистрибутивах
Linux, где установлены стандартные утилиты GNU, оболочка bash и php 5.4+.


## Help

```text
Help:
./start                           - Run game.
./start settings                  - Settings game.
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

* Выбор между PulseAudio и ALSA
```ini
[script]
; Set sound driver to PulseAudio or ALSA
pulse = 1
```

* Автоматическое скачивание последнего dxvk
```ini
[script]
; Download latest d3d11.dll and dxgi.dll
dxvk = 1
```

* Выбор версии windows
```ini
[script]
; Windows version (win7, winxp, win2k)
winver = "win7"
```

* Возможность использования нескольких ini файлов. Для совмещения нескольких игр в одном префиксе.
```bash
./start config game_info1.ini
```

* Возможность использования хуков после создания префикса, перед запуском и после остановки приложения.
```ini
[hooks]
after_create_prefix[] = "create.sh"
before_run_game[] = "before.sh"
after_exit_game[] = "after.sh"
after_exit_game[] = "after2.sh"
```

* Выставление для каждой dll индивидуальных настроек.
```ini
[dlls]
dll[d3d11.dll] = "nooverride"
dll[d3d12.dll] = "builtin"
dll[d3d13.dll] = "builtin,native"
dll[d3d14.dll] = "native,builtin"
dll[d3d15.dll] = "native"
```

* Возможность зарегистрировать библиотеку через regsvr32.
```ini
[dlls]
dll[l3codecx.ax] = "register"
```

* Если в системе не установлен PulseAudio, скрипт автоматически переключит wine на ALSA.

* Библиотеки из папок `dlls` и `dlls64` применяются сразу без пересоздания префикса.

* Обеспечена корректная установка **.NET Framework**

* "Умное" создание иконки, создаёт в папке `Games` \ `Игры` если она присутствует на рабочем столе, также ищет файл 
без расширения `.desktop` (иногда нужна иконка без расширения), ищет `png` файлы в качестве картинки, в папках `./` и 
`./game_info`, если таковых несколько предлагает выбрать конкретную.

* Если в конфиге задан хук settings, будет отображён диалог выбора запуска настроек или игры.  

![Settings]( data:image/png;base64,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)

* Хуки для GPU применяются только один раз, после создания префикса и после хуков на создание префикса.
```ini
[hooks]
gpu_amd[] = "gpu/amd.sh"
gpu_nvidia[] = "gpu/nvidia.sh"
gpu_intel[] = "gpu/intel.sh"
```

* Хук для настроек игры при выполнении команды `./start settings`, выполнится файл заданный в конфигурации секции: 
```ini
[hooks]
settings[] = "settings.sh"
```

* Использование ini файла `./game_info/game_info.ini` для настроек:
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
winetricks = 0
dialogs = 1

; Not use /home/user directory
sandbox = 1

; Download latest d3d11.dll and dxgi.dll
dxvk = 0

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
; gpu_amd[] = "gpu/amd.sh"
; gpu_nvidia[] = "gpu/nvidia.sh"
; gpu_intel[] = "gpu/intel.sh"
; settings[] = "settings.sh"
```  
* Если после `./start` следует аттрибут `diff` а затем команда, то как только команда отработает 
скрипт покажет изменившиеся файлы в директориях `system32`, `syswow64`
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
 
 ## Полезные ссылки
 
 * dxvk [GPU driver support](https://github.com/doitsujin/dxvk/wiki/Driver-support)
 * dxvk [releases](https://github.com/doitsujin/dxvk/releases)
 * wine builds for support vulkan [yandex disk](https://yadi.sk/d/IrofgqFSqHsPu) 
 or [google disk](https://drive.google.com/open?id=1fTfJQhQSzlEkY-j3g0H6p4lwmQayUNSR)
 * [wine standalone](https://lutris.net/files/runners/)
 * [Installer Repacks](https://repacks.net/)
 