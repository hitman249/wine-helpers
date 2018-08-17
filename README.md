
## Требования

* xrandr 1.5 +
* zenity

```bash
# Для Ubuntu, winetricks зависимости:
sudo dpkg --add-architecture i386
wget -nc https://dl.winehq.org/wine-builds/Release.key
sudo apt-key add Release.key
sudo apt-add-repository https://dl.winehq.org/wine-builds/ubuntu/
sudo apt-get update
sudo apt-get install binutils cabextract p7zip-full unrar unzip wget wine zenity
```

* Чтобы использовать Vulkan, его должны поддерживать:
  * Драйвера на видеокарту, утилита `vulkaninfo` должна говорить что всё ок.
  * Сама видеокарта должна поддерживать Vulkan.
  * Wine сборка должна быть не меньше 3.10 и собрана с поддержкой Vulkan, дистрибутивы из репозиториев ОС обычно собраны 
  без Vulkan.
  * PS: Vulkan не обязательная опция, необходимая только для поддержки dx11, если игра dx9-10, то Vulkan не требуется, 
  также игру на dx11 можно запускать и вообще без vulkan, vulkan даёт только увеличение производительности.


## Описание 

Этот скрипт предназначен в первую очередь для облегчения создания и распространения Wine раздач игр/программ, но может 
применяться и в других целях. Работа скрипта гарантируется на всех дистрибутивах Linux, где установлены стандартные 
утилиты GNU и оболочка bash.

**Portable PHP 5.6.37** `amd64` идущий в комплекте, протестирован на Ubuntu 18.04, 16.04, Debian 8, Rosa R8, R10, 
CentOS 7, Deepin 15.6, Manjaro 17.1.11.

## Как сделать порт игры

1) Создать папку (желательно без пробелов в пути и кириллицы)

2) Скопировать туда папку wine, сборки с поддержкой vulkan можно скачать [отсюда](https://yadi.sk/d/IrofgqFSqHsPu), выполнить:
```bash
wget -q -O start https://raw.githubusercontent.com/hitman249/wine-helpers/master/start && chmod +x start
```

3) В папке у вас должны получиться 2 элемента, папка `wine` и файл `start`.   
Если папки wine нет, будет использоваться wine установленный в систему.

4) Запускаете файл `./start`

5) Появится директория `game_info`, редактируете файл `game_info/game_info.ini`

6) Через файловый менеджер `./start fm` устанавливаете игру.

7) Если требуются дополнительные библиотеки копируете их в папки `dlls` и `dlls64` скрипт создаст симлинки 
файлов в директорию:
    - `dlls`   -> `windows/system32`
    - `dlls64` -> `windows/syswow64`
    
8) Если требуется использовать `*.reg` файлы, копируйте их в папку `regs`

9) Если требуется `winetricks` используйте команду `./start winetricks d3dx9 dxvk63`

10) Если нужен `dxvk` ставите в конфиге `dxvk = 1` и `dxvk_autoupdate = 1`, в этом случае dxvk скачается сам, и будет 
автоматически обновляться при появлении более новой версии!

11) В папке `additional` можно пробросить папки для сохранений, чтобы они не удалились вместе с `prefix`-ом

12) Файлы игры должны быть в папке `data`

13) В конце удаляете все лишние файлы и папку `prefix`

14) Запускаете `./start`, игра должна запуститься.

15) Если всё корректно запускается, снова удаляете все лишние файлы и папку `prefix`

16) Готово. Игру можно запаковывать.


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
./start info                      - Info about the game.
./start monitor                   - Monitors info.
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
./start check                    - Check script dependencies.
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

* Если в системе не установлен PulseAudio, скрипт автоматически переключит wine на ALSA.

* (Опционально) Автоматически обновляет dxvk до последней версии при каждом запуске игры.

* (Опционально) Автоматически обновляет себя.

* (Опционально) Использование нескольких ini файлов для совмещения нескольких игр в одном префиксе.

* (Опционально) Хуки после создания префикса, перед запуском и после остановки приложения.

* (Опционально) Хуки GPU для внесения специфичных от производителя видеокарты исправлений (после создания префикса).

* (Опционально) Можно автоматически подменять ширину и высоту в конфигурационных файлах игры при создании префикса 
(изменяет перед применением *.reg файлов).

* (Опционально) Отображение диалога с выбором что запускать, если обнаруживается несколько *.ini файлов.

 ![Settings](2.png)

* (Опционально) Если в конфиге задан хук `settings[] = "settings.sh"`, будет отображён диалог выбора запуска настроек или игры.
Также настройку игры можно оформить в виде отдельного *.ini файла. 
 
 ![Settings](1.png)


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
autoupdate = 1

; Not use /home/user directory
sandbox = 1

; Download latest d3d11.dll and dxgi.dll
dxvk = 0
dxvk_autoupdate = 1

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

[dlls]
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

; dll[d3d11.dll] = "nooverride"
; dll[l3codecx.ax] = "register"

[hooks]
;
; Хуки
; after_create_prefix - команды выполняются после создания префикса
; before_run_game - команды выполняются перед запуском игры
; after_exit_game - команды выполняются после завершения игры
;

; after_create_prefix[] = "create.sh"
; before_run_game[] = "before.sh"
; after_exit_game[] = "after.sh"
; after_exit_game[] = "after2.sh"
; gpu_amd[] = "gpu/amd.sh"
; gpu_nvidia[] = "gpu/nvidia.sh"
; gpu_intel[] = "gpu/intel.sh"
; settings[] = "settings.sh"

[export]
;
; Экспорт дополнительных переменных к команде запуска игры
; Примеры:
;

; DXVK_HUD=fps
; DXVK_HUD=1
; DXVK_HUD=fps,devinfo,memory
; DXVK_HUD=fps,devinfo,frametimes,memory
; DXVK_HUD=fps,devinfo,frametimes,submissions,drawcalls,pipelines,memory

[replaces]
;
; При создании префикса ищет и заменяет в указанных файлах теги.
; Путь относительно позиции файла ./start
; Выполняется ДО регистрации *.reg файлов
;
; {WIDTH} - ширина монитора по умолчанию в пикселях (число)
; {HEIGHT} - высота монитора по умолчанию в пикселях (число)
;

; file[] = "game_info/data/example.conf"
``` 

 ## Полезные советы
 
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
  
* Библиотеки из папок `dlls`, `dlls64` применяются сразу, без пересоздания префикса.

* Игру в режиме дебага можно запустить добавив `debug` к команде.   
Пример: `./start debug`

* Команды `cfg`, `fm`, `regedit` всегда работают в режиме дебага, к ним `debug` приписывать не нужно.

* Можно отловить изменения в папках `system32`, `syswow64` также через установку из `fm` добавив в команду `diff`  
Пример: `./start diff fm`

* "Умное" создание иконки (команда: `./start icon`), создаёт в папке `Games` \ `Игры` если она присутствует на рабочем 
  столе, также ищет файл без расширения `.desktop` (иногда нужна иконка без расширения), ищет `png` файлы в качестве 
  картинки, в папках `./`, `./game_info`, `./game_info/data`, если таковых несколько предлагает выбрать конкретную.
  
* В ini файлах можно пробросить дополнительные переменные для ENV окружения, секция `[export]`.
 
* Если не использовать php интерпретатор который идёт из коробки.
 
 ```bash
 # Для Ubuntu, зависимости самого скрипта
 sudo apt-get install x11-xserver-utils wget wine zenity php php-cli php-readline php-curl php-gd php-common php-bz2 php-json php-mbstring php-timer php-zip
 ```
 
 ## Полезные ссылки
 
 * dxvk [GPU driver support](https://github.com/doitsujin/dxvk/wiki/Driver-support)
 * dxvk [releases](https://github.com/doitsujin/dxvk/releases)
 * wine builds for support vulkan [yandex disk](https://yadi.sk/d/IrofgqFSqHsPu) 
 or [google disk](https://drive.google.com/open?id=1fTfJQhQSzlEkY-j3g0H6p4lwmQayUNSR)
 * [wine standalone](https://lutris.net/files/runners/)
 * [Installer Repacks](https://repacks.net/)
 