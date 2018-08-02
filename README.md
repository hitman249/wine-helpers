
## Требования

* php 5.4 + 
* xrandr 1.5 +

## Установка

* создать папку (желательно без пробелов в пути и кириллицы)
* скопировать туда папку wine, сборки с поддержкой vulkan можно скачать [отсюда](https://yadi.sk/d/IrofgqFSqHsPu) 
* выполнить:

```bash
wget -q -O start https://raw.githubusercontent.com/hitman249/wine-helpers/master/start
chmod +x start
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
./start                     - Run game
./start winetricks d3dx9    - Winetricks install d3dx9
./start cfg                 - Configure
./start fm                  - File Manager
./start regedit (reg)       - Windows Registry Editor
./start kill                - Kill this instance Wine
./start help

./start diff                - Enable change files analyze from system32, syswow64 folders
or 
./start diff fm
./start diff winetricks d3dx9
./start diff cfg
and others

./start wine                - Get Wine Instance
```

## Возможности

* Автоматизирует работу с отдельным wine префиксом
* Перед запуском игры сохраняет разрешение, яркость и гамму на каждом мониторе в отдельности.
* После завершения игры восстанавливает только изменившиеся параметры разрешение, яркость и гамму, 
в отдельности по каждому параметру. Т.е. если изменилась гамма на втором мониторе, то только она 
и будет исправлена.
* При использовании `winetricks` он автоматически выкачивается.
* Показывает недостающие либы wine
* Показывает используемую версию Wine, Vulkan, xrandr, winetricks
* Если после `./start` следует аттрибут `diff` а затем команда, то как только команда отработает 
скрипт покажет изменившиеся файлы в директориях `system32`, `syswow64`

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
 