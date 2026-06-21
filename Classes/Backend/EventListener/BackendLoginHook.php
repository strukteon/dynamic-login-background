<?php

declare(strict_types=1);

namespace Strukteon\DynamicLoginBackground\Backend\EventListener;

use TYPO3\CMS\Backend\LoginProvider\Event\ModifyPageLayoutOnLoginProviderSelectionEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

#[AsEventListener(
    identifier: 'dynamic-login-background/backend/after-backend-page-render',
    event: ModifyPageLayoutOnLoginProviderSelectionEvent::class,
)]
final class BackendLoginHook
{
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        private readonly PageRenderer $pageRenderer,
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
    ) {}

    public function __invoke(ModifyPageLayoutOnLoginProviderSelectionEvent $event): void
    {
        $conf = $this->extensionConfiguration->get('dynamic_login_background');
        $folderSetting = $conf['loginBackgroundImageFolder'] ?? '';
        if ($folderSetting === '') {
            return;
        }

        $folderPath = GeneralUtility::getFileAbsFileName($folderSetting);
        if ($folderPath === '') {
            return;
        }

        $files = glob(rtrim($folderPath, '/') . '/*.{jpg,jpeg,png,gif,JPG,JPEG,PNG,GIF}', GLOB_BRACE);
        if (empty($files)) {
            return;
        }

        $selectedFile = $files[array_rand($files)];
        $filename = basename($selectedFile);

        $path = '';
        if (str_starts_with($folderSetting, 'EXT:')) {
            $resourceIdentifier = rtrim($folderSetting, '/') . '/' . $filename;
            try {
                $resource = $this->systemResourceFactory->createPublicResource($resourceIdentifier);
                $path = (string)$this->resourcePublisher->generateUri($resource, $event->getRequest());
            } catch (\Exception) {
                // Fallback if resource factory fails
            }
        }

        if ($path === '') {
            // Fallback: If not starting with EXT: or resource factory fails, use PathUtility as a last resort
            if (class_exists(PathUtility::class)) {
                $path = PathUtility::getAbsoluteWebPath($selectedFile);
            }
        }

        if ($path !== '') {
            $this->pageRenderer->addCssInlineBlock(
                'dynamic-login-background',
                '.typo3-login { background-image: url("' . $path . '"); }',
                useNonce: true
            );
        }
    }
}
