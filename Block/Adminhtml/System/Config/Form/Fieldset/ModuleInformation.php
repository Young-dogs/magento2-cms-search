<?php
/**
 * Copyright © YoungDogs. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace YoungDogs\CmsSearch\Block\Adminhtml\System\Config\Form\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Config\Block\System\Config\Form\Fieldset;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\View\Helper\Js;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

/**
 * Renders a plain (non-collapsible) info fieldset with module name, version and logo link.
 */
class ModuleInformation extends Fieldset
{
    private const MODULE_NAME = 'YoungDogs_CmsSearch';
    private const LOGO_URL = 'https://static.young-dogs.com/logo.svg';
    private const HOMEPAGE_URL = 'https://www.young-dogs.com/?utm_source=magento&utm_medium=admin&utm_campaign=cms_search';

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        private readonly ComponentRegistrarInterface $componentRegistrar,
        array $data = [],
        ?SecureHtmlRenderer $secureRenderer = null
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data, $secureRenderer);
    }

    /**
     * @inheritDoc
     *
     * Overridden to drop the default collapsible header link: nesting our logo <a> inside the
     * header's own <a id="..-head"> makes the browser auto-close the outer anchor early, which
     * breaks the layout in two.
     */
    protected function _getHeaderHtml($element)
    {
        $html = '<fieldset class="' . $this->_getFieldsetCss() . ' youngdogs-info-fieldset" id="'
            . $element->getHtmlId() . '">';
        $html .= '<legend>' . $element->getLegend() . '</legend>';
        $html .= $this->_getBrandingHtml();
        $html .= '<table cellspacing="0" class="form-list"><colgroup class="label" /><colgroup class="value" />';
        if ($this->getRequest()->getParam('website') || $this->getRequest()->getParam('store')) {
            $html .= '<colgroup class="use-default" />';
        }
        $html .= '<colgroup class="scope-label" /><colgroup class="" /><tbody>';

        return $html;
    }

    /**
     * @inheritDoc
     */
    protected function _getFooterHtml($element)
    {
        return '</tbody></table></fieldset>';
    }

    private function _getBrandingHtml(): string
    {
        $version = $this->getModuleVersion();

        return '<div class="youngdogs-info-block"><div class="youngdogs-module-version"><span class="version-title">'
            . 'CMS Search'
            . ($version !== '' ? ' <span class="module-version last-version">' . $this->escapeHtml($version) . '</span>' : '')
            . ' by '
            . '<a target="_blank" rel="noopener noreferrer" href="' . $this->escapeUrl(self::HOMEPAGE_URL) . '">'
            . '<img alt="YoungDogs" class="youngdogs-logo" src="' . $this->escapeUrl(self::LOGO_URL) . '" /></a>'
            . '</span></div></div>';
    }

    /**
     * Reads the module version from its own composer.json
     */
    private function getModuleVersion(): string
    {
        $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        if (!$modulePath) {
            return '';
        }

        $composerFile = $modulePath . '/composer.json';
        if (!is_readable($composerFile)) {
            return '';
        }

        $composerData = json_decode((string) file_get_contents($composerFile), true);

        return (string) ($composerData['version'] ?? '');
    }
}