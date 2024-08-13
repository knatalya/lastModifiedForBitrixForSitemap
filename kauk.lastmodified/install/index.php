<?php
use Bitrix\Main\ModuleManager;
use Bitrix\Main\EventManager;

class kauk_lastmodified extends CModule
{
    public $MODULE_ID = "kauk.lastmodified";
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    public function __construct()
    {
        $arModuleVersion = array();
        include(__DIR__ . "/../.version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        $this->MODULE_NAME = GetMessage("KAUK_LASTMODIFIED_MODULE_NAME");
        $this->MODULE_DESCRIPTION = GetMessage("KAUK_LASTMODIFIED_MODULE_DESC");
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);

        // Регистрируем обработчик события OnPageStart
        EventManager::getInstance()->registerEventHandler(
            "main",
            "OnPageStart",
            $this->MODULE_ID,
            '\Kauk\LastModified\LastModified',
            'onPageStartHandler'
        );
    }

    public function DoUninstall()
    {
        // Удаляем обработчик события
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnPageStart",
            $this->MODULE_ID,
            '\Kauk\LastModified\LastModified',
            'onPageStartHandler'
        );

        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
