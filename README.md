common_libs
===========


Common library files for Viewer projects

Composer.json:
```json
"require": {
    "smartcrowd/common_libs": "dev-master"
}
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:SmartCrowd/common_libs.git"
    }
]
```
Для работы старых проектов используется замороженная версия "0.4"
Для подключения прокси листов необходимо добавить файлы в каталог ``` /path/to/project/vendor/SmartCrowd/common_libs/helper/data/```

Например:
```/path/to/project/vendor/SmartCrowd/common_libs/helper/data/ru_proxy.list ```


По-умолчанию прокси использоваться не будут.

