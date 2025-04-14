<?php


declare(strict_types=1);

namespace Strukteon\DynamicLoginBackground\Backend\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

#[AsEventListener(
    identifier: 'dynamic-login-background/backend/after-backend-page-render',
    event: ModifyPageLayoutOnLoginProviderSelectionEvent::class,
)]
final class BackendLoginHook
{
    public function __construct(
        private \TYPO3\CMS\Core\Configuration\ExtensionConfiguration $extensionConfiguration,
    )
    {

    }

    public function __invoke(ModifyPageLayoutOnLoginProviderSelectionEvent $event): void
    {
        $conf = $this->extensionConfiguration->get('dynamic_login_background');
        $folderPath = GeneralUtility::getFileAbsFileName($conf["loginBackgroundImageFolder"]);

        $files = GeneralUtility::getFilesInDir($folderPath, 'jpg,png,gif', true);
        if (empty($files)) {
            return;
        }

        $selectedFile = $files[array_rand($files)];
        $path = PathUtility::getAbsoluteWebPath($selectedFile);

        $event->getPageRenderer()->addCssInlineBlock("test", '.typo3-login { background-image: url("' . $path . '"); }', useNonce: true);
    }
}
