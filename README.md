## Описание 

![Settings](1.png)
![Settings](2.png)
![Settings](3.png)
![Settings](4.png)
![Settings](5.png)
![Settings](6.png)
![Settings](7.png)

Этот скрипт предназначен в первую очередь для облегчения портирования Windows игр/программ под Linux, но может 
применяться и в других целях. Работа скрипта гарантируется на всех дистрибутивах Linux, где установлены стандартные 
утилиты GNU и оболочка bash.

**Portable PHP 5.6.38** `amd64` идущий в комплекте, протестирован на Ubuntu 18.04, 16.04, Debian 8, Rosa R8, R10, 
CentOS 7, Deepin 15.6, Manjaro 17.1.11.

### Как запустить игру

1) Распаковать ```chmod +x ./extract.sh && ./extract.sh```
2) Запустить `./start`

### Как сделать порт игры

1) Создать папку (желательно без пробелов в пути и кириллицы)

2) Выполнить
```bash
wget -q -O start https://raw.githubusercontent.com/hitman249/wine-helpers/master/start && chmod +x start
```

3) В папке у вас должны получиться 2 элемента, папка `wine` и файл `start`.   
Если папки wine нет, будет использоваться wine установленный в систему.

4) Запускаете файл `./start`

5) Появится директория `./game_info`, редактируете файл `./game_info/game_info.ini`

6) Через файловый менеджер устанавливаете игру.

7) Если требуются дополнительные библиотеки копируете их в папки `dlls` и `dlls64` скрипт создаст симлинки 
файлов в директорию:
    - `dlls`   -> `windows/system32`
    - `dlls64` -> `windows/syswow64`
    
8) Если требуется использовать `*.reg` файлы, копируйте их в папку `regs`

9) Если требуется `winetricks` используйте команду `./start winetricks wmp9`, также можно отредактировать параметр  
`winetricks_to_install = ""`, в этом случае пакеты автоматически установятся во время создания префикса

10) Если нужен `dxvk` ставите в конфиге `dxvk = 1`, скачивается сам!

11) В папке `./game_info/additional` можно пробросить папки для сохранений, чтобы они не удалились вместе с `prefix`-ом

12) Файлы игры должны быть в папке `./game_info/data`

13) В конце удаляете все лишние файлы и папку `prefix`

14) Запускаете `./start`, игра должна запуститься.

15) Если всё корректно запускается, снова удаляете все лишние файлы и папку `prefix`

16) Готово. Игру можно запаковывать.

#### Рекомендации

* Игры внутри префикса рекомендуется всегда ставить в папку **C:/Games**/Folder. Это откроет вам _дополнительные_ возможности скрипта.

### Использование squashfs

* Если вам хочется чтобы ваша игра занимала как можно меньше места и при этом в неё можно было играть, в скрипте 
предусмотрена автоматическая упаковка директорий `./wine` и `./game_info/data`
  
  Для этого запустите скрипт в режиме GUI командой `./start gui` и перейдите в **Tools > Pack**


* Рядом с указанными директориями создадутся файлы `./wine.squashfs` и `./game_info/data.squashfs`.  

  > Будьте внимательны, папки с именами `./wine` и `./game_info/data` имеют **больший** приоритет над файлами 
    `wine.squashfs` и `data.squashfs`.


* Часто игры могут писать в собственную папку, поэтому нужно вынести данные файлы и папки в директорию 
`./game_info/additional`, а в папке с игрой создать для них симлинки.

  Делается это в GUI: 

  - **Tools > Symlink**, скрипт сам предложит поддерживаемые папки. 
  

* Когда всё будет готово можно сделать сборку игры командой:

  - **Tools > Build** 

  Которая создаст одноимённую папку, и скопирует туда методом hardlink все необходимые файлы.
  > **hardlink** - вместо копирования делает hard ссылку на файл, в итоге процесс копирования сводится только к созданию 
  ссылок.


## Help

```text
Help:
./start             - Run game.
./start gui         - Graphical user interface.
./start kill        - Kill this instance Wine.
./start winetricks  - Winetricks install d3dx9 (./start winetricks d3dx9).
./start wine        - Get Wine Instance.
./start help
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

* Использование ini файла `./game_info/game_info.ini` для настроек:

```ini
[game]
path = "Games"
additional_path = "The Super Game/bin"
exe = "Game.exe"
cmd = "-language=russian"
name = "The Super Game: Deluxe Edition"
version = "1.0.0"

[script]
autoupdate = 1

; Download latest d3d11.dll and dxgi.dll
dxvk = 0
dxvk_autoupdate = 1

; Required for determining display manner FPS
dxvk_d3d10 = 0

; winetricks_to_install = "d3dx9 xact"
winetricks_to_install = ""

; Windows version (win7, winxp, win2k)
winver = "win7"

csmt = 1

; Not use /home/user directory
sandbox = 1

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
; GALLIUM_HUD=simple,fps
; WINEESYNC=1
; PBA_DISABLE=1
; MESA_GLTHREAD=true
; __GL_THREADED_OPTIMIZATIONS=1
;
; Если в игре хрипит звук можно попробовать
; PULSE_LATENCY_MSEC=60

WINEESYNC=1
PBA_DISABLE=1

[replaces]
;
; При создании префикса ищет и заменяет в указанных файлах теги.
; Путь относительно позиции файла ./start
; Выполняется ДО регистрации *.reg файлов
;
; {WIDTH} - ширина монитора по умолчанию в пикселях (число)
; {HEIGHT} - высота монитора по умолчанию в пикселях (число)
; {USER} - имя пользователя
;

; file[] = "game_info/data/example.conf"
``` 

 ## Полезные ссылки
 
 * dxvk [GPU driver support](https://github.com/doitsujin/dxvk/wiki/Driver-support)
 * dxvk [releases](https://github.com/doitsujin/dxvk/releases)
 * wine builds for support vulkan [yandex disk](https://yadi.sk/d/IrofgqFSqHsPu) 
 or [google disk](https://drive.google.com/open?id=1fTfJQhQSzlEkY-j3g0H6p4lwmQayUNSR)
 * [wine standalone](https://lutris.net/files/runners/)
 * [Installer Repacks](https://repacks.net/)
 * Gamepad [dumbxinputemu](https://github.com/kozec/dumbxinputemu/releases)
 * Vulkan [vulkan.lunarg.com](https://vulkan.lunarg.com/sdk/home#linux)
 * [Wine + Gallium Nine](https://launchpad.net/~commendsarnex/+archive/ubuntu/winedri3)
 * [Управление тактовой частотой процессора под Linux](http://www.michurin.net/tools/cpu-frequency.html)
 * [Nightly builds dxvk](https://haagch.frickel.club/files/dxvk/)
 * [Performance Tweaks](https://github.com/lutris/lutris/wiki/Performance-Tweaks)