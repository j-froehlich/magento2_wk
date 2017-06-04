<?php
/**
 * Snowdog
 *
 * @author      Paweł Pisarek <pawel.pisarek@snow.dog>.
 * @category
 * @package
 * @copyright   Copyright Snowdog (http://snow.dog)
 */

namespace Snowdog\Menu\Block\Element;

use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;

class Editor extends TextArea
{
    /**
     * @param Factory           $factoryElement
     * @param CollectionFactory $factoryCollection
     * @param Escaper           $escaper
     * @param array             $data
     */
    public function __construct(
        Factory $factoryElement,
        \Magento\Framework\Data\Form\Element\CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = []
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data);

        if ($this->isEnabled()) {
            $this->setType('wysiwyg');
            $this->setExtType('wysiwyg');
        } else {
            $this->setType('textarea');
            $this->setExtType('textarea');
        }
    }

    /**
     * @return array
     */
    protected function getButtonTranslations()
    {
        $buttonTranslations = [
            'Insert Image...' => $this->translate('Insert Image...'),
            'Insert Media...' => $this->translate('Insert Media...'),
            'Insert File...'  => $this->translate('Insert File...'),
        ];

        return $buttonTranslations;
    }

    /**
     * @return string
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getElementHtml()
    {
        if ($this->isEnabled()) {
            $html = $this->_getButtonsHtml();
            $html .= $this->getTextareaHtml();
            $html .= $this->getPopupJs();
            $html .= $this->getInitJs();

            $html = $this->_wrapIntoContainer($html);
            $html .= $this->getAfterElementHtml();

            return $html;
        } else {
            // Display only buttons to additional features
            if ($this->getConfig('widget_window_url')) {
                $html = $this->_getButtonsHtml();
                $html .= $this->getPopupJs();
                $html .= parent::getElementHtml();

                if ($this->getConfig('add_widgets')) {
                    $html .= $this->getWidgetsJs();
                }
                $html = $this->_wrapIntoContainer($html);

                return $html;
            }

            return parent::getElementHtml();
        }
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        if (!$this->hasData('theme')) {
            return 'simple';
        }

        return $this->_getData('theme');
    }

    /**
     * Return Editor top Buttons HTML
     *
     * @return string
     */
    protected function _getButtonsHtml()
    {
        $buttonsHtml = '<div id="buttons' . $this->getHtmlId() . '" class="buttons-set">';

        if ($this->isEnabled()) {
            $buttonsHtml .= $this->_getToggleButtonHtml($this->isToggleButtonVisible());
            $buttonsHtml .= $this->_getPluginButtonsHtml($this->isHidden());
        } else {
            $buttonsHtml .= $this->_getPluginButtonsHtml(true);
        }
        $buttonsHtml .= '</div>';

        return $buttonsHtml;
    }

    /**
     * Return HTML button to toggling WYSIWYG
     *
     * @param bool $visible
     *
     * @return string
     */
    protected function _getToggleButtonHtml($visible = true)
    {
        $html = $this->_getButtonHtml(
            [
                'title' => $this->translate('Show / Hide Editor'),
                'class' => 'action-show-hide',
                'style' => $visible ? '' : 'display:none',
                'id'    => 'toggle' . $this->getHtmlId(),
            ]
        );

        return $html;
    }

    /**
     * Prepare Html buttons for additional WYSIWYG features
     *
     * @param bool $visible Display button or not
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getPluginButtonsHtml($visible = true)
    {
        $buttonsHtml = '';

        // Button to widget insertion window
        if ($this->getConfig('add_widgets')) {
            $buttonsHtml .= $this->_getButtonHtml(
                [
                    'title'   => $this->translate('Insert Widget...'),
                    'onclick' => "widgetTools.openDialog('" . $this->getConfig(
                            'widget_window_url'
                        ) . "widget_target_id/" . $this->getHtmlId() . "')",
                    'class'   => 'action-add-widget plugin',
                    'style'   => $visible ? '' : 'display:none',
                ]
            );
        }

        // Button to media images insertion window
        if ($this->getConfig('add_images')) {
            $buttonsHtml .= $this->_getButtonHtml(
                [
                    'title'   => $this->translate('Insert Image...'),
                    'onclick' => "MediabrowserUtility.openDialog('" . $this->getConfig(
                            'files_browser_window_url'
                        ) . "target_element_id/" . $this->getHtmlId() . "/" . (null !== $this->getConfig(
                            'store_id'
                        ) ? 'store/' . $this->getConfig(
                                'store_id'
                            ) . '/' : '') . "')",
                    'class'   => 'action-add-image plugin',
                    'style'   => $visible ? '' : 'display:none',
                ]
            );
        }

        if (is_array($this->getConfig('plugins'))) {
            foreach ($this->getConfig('plugins') as $plugin) {
                if (isset($plugin['options']) && $this->_checkPluginButtonOptions($plugin['options'])) {
                    $buttonOptions = $this->_prepareButtonOptions($plugin['options']);

                    if (!$visible) {
                        $configStyle = '';

                        if (isset($buttonOptions['style'])) {
                            $configStyle = $buttonOptions['style'];
                        }
                        $buttonOptions = array_merge($buttonOptions, ['style' => 'display:none;' . $configStyle]);
                    }

                    $buttonsHtml .= $this->_getButtonHtml($buttonOptions);
                }
            }
        }

        return $buttonsHtml;
    }

    /**
     * Prepare button options array to create button html
     *
     * @param array $options
     *
     * @return array
     */
    protected function _prepareButtonOptions($options)
    {
        $buttonOptions = [];
        $buttonOptions['class'] = 'plugin';
        foreach ($options as $name => $value) {
            $buttonOptions[$name] = $value;
        }
        $buttonOptions = $this->_prepareOptions($buttonOptions);

        return $buttonOptions;
    }

    /**
     * Check if plugin button options have required values
     *
     * @param array $pluginOptions
     *
     * @return boolean
     */
    protected function _checkPluginButtonOptions($pluginOptions)
    {
        if (!isset($pluginOptions['title'])) {
            return false;
        }

        return true;
    }

    /**
     * Convert options by replacing template constructions ( like {{var_name}} )
     * with data from this element object
     *
     * @param array $options
     *
     * @return array
     */
    protected function _prepareOptions($options)
    {
        $preparedOptions = [];

        foreach ($options as $name => $value) {
            if (is_array($value) && isset($value['search']) && isset($value['subject'])) {
                $subject = $value['subject'];

                foreach ($value['search'] as $part) {
                    $subject = str_replace('{{' . $part . '}}', $this->getDataUsingMethod($part), $subject);
                }

                $preparedOptions[$name] = $subject;
            } else {
                $preparedOptions[$name] = $value;
            }
        }

        return $preparedOptions;
    }

    /**
     * Return custom button HTML
     *
     * @param array $data Button params
     *
     * @return string
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getButtonHtml($data)
    {
        $html = '<button type="button"';
        $html .= ' class="scalable ' . (isset($data['class']) ? $data['class'] : '') . '"';
        $html .= isset($data['onclick']) ? ' onclick="' . $data['onclick'] . '"' : '';
        $html .= isset($data['style']) ? ' style="' . $data['style'] . '"' : '';
        $html .= isset($data['id']) ? ' id="' . $data['id'] . '"' : '';
        $html .= '>';
        $html .= isset($data['title']) ? '<span><span><span>' . $data['title'] . '</span></span></span>' : '';
        $html .= '</button>';

        return $html;
    }

    /**
     * Wraps Editor HTML into div if 'use_container' config option is set to true
     * If 'no_display' config option is set to true, the div will be invisible
     *
     * @param string $html HTML code to wrap
     *
     * @return string
     */
    protected function _wrapIntoContainer($html)
    {
        if (!$this->getConfig('use_container')) {
            return '<div class="admin__control-wysiwig">' . $html . '</div>';
        }

        $html = '<div id="editor' . $this->getHtmlId() . '"' . ($this->getConfig(
                'no_display'
            ) ? ' style="display:none;"' : '') . ($this->getConfig(
                'container_class'
            ) ? ' class="admin__control-wysiwig ' . $this->getConfig(
                    'container_class'
                ) . '"' : '') . '>' . $html . '</div>';

        return $html;
    }

    /**
     * Editor config retriever
     *
     * @param string $key Config var key
     *
     * @return mixed
     */
    public function getConfig($key = null)
    {
        if (!$this->_getData('config') instanceof \Magento\Framework\DataObject) {
            $config = new \Magento\Framework\DataObject();
            $this->setConfig($config);
        }

        if ($key !== null) {
            return $this->_getData('config')->getData($key);
        }

        return $this->_getData('config');
    }

    /**
     * Translate string using defined helper
     *
     * @param string $string String to be translated
     *
     * @return string
     */
    public function translate($string)
    {
        return (string)new \Magento\Framework\Phrase($string);
    }

    /**
     * Check whether Wysiwyg is enabled or not
     *
     * @return bool
     */
    public function isEnabled()
    {
        $result = false;

        if ($this->getConfig('enabled')) {
            $result = $this->hasData('wysiwyg') ? $result = $this->getWysiwyg() : true;
        }

        return $result;
    }

    /**
     * Check whether Wysiwyg is loaded on demand or not
     *
     * @return bool
     */
    public function isHidden()
    {
        return $this->getConfig('hidden');
    }

    /**
     * @return bool
     */
    protected function isToggleButtonVisible()
    {
        return !$this->getConfig()->hasData('toggle_button') || $this->getConfig('toggle_button');
    }

    /**
     * @return string
     */
    protected function getPopupJs()
    {
        return '
            <script type="text/javascript">
            //<![CDATA[
                openEditorPopup = function(url, name, specs, parent) {
                    if ((typeof popups == "undefined") || popups[name] == undefined || popups[name].closed) {
                        if (typeof popups == "undefined") {
                            popups = new Array();
                        }
                        var opener = (parent != undefined ? parent : window);
                        popups[name] = opener.open(url, name, specs);
                    } else {
                        popups[name].focus();
                    }
                    return popups[name];
                }

                closeEditorPopup = function(name) {
                    if ((typeof popups != "undefined") && popups[name] != undefined && !popups[name].closed) {
                        popups[name].close();
                    }
                }
            //]]>
            </script>';
    }

    /**
     * @return string
     */
    protected function getTextareaHtml()
    {
        return '<textarea name="' .
            $this->getName() .
            '" title="' .
            $this->getTitle() .
            '" ' .
            $this->_getUiId() .
            ' id="' .
            $this->getHtmlId() .
            '"' .
            ' class="textarea' .
            $this->getClass() .
            '" ' .
            $this->serialize(
                $this->getHtmlAttributes()
            ) .
            $this->serializeSingleQuoted(
                $this->getSingleQuoteHtmlAttributes()
            ) .
            ' >' .
            $this->getEscapedValue() .
            '</textarea>';
    }

    /**
     * @return string
     */
    protected function getInitJs()
    {
        $jsSetupObject = 'wysiwyg_' . $this->getHtmlId();

        $forceLoad = '';
        if (!$this->isHidden()) {
            if ($this->getForceLoad()) {
                $forceLoad = $jsSetupObject . '.setup("exact");';
            } else {
                $forceLoad = 'jQuery(window).on("load", ' .
                    $jsSetupObject .
                    '.setup.bind(' .
                    $jsSetupObject .
                    ', "exact"));';
            }
        }
        return '
            <script type="text/javascript">
                //<![CDATA[
                window.tinyMCE_GZ = window.tinyMCE_GZ || {}; 
                window.tinyMCE_GZ.loaded = true;
                require(
                    [
                        "jquery", 
                        "mage/translate", 
                        "mage/adminhtml/events", 
                        "snowWysiwygSetup", 
                        "mage/adminhtml/wysiwyg/widget"], 
                        function(jQuery){' .
            '  (function($) {$.mage.translate.add(' .
            \Zend_Json::encode($this->getButtonTranslations()) .
            ')})(jQuery);
            ' . $jsSetupObject . ' = new tinyMceWysiwygSetup(
            "' . $this->getHtmlId() . '", 
            ' . \Zend_Json::encode($this->getConfig()) .
            ');
            ' . $forceLoad . '
            editorFormValidationHandler = ' . $jsSetupObject . '.onFormValidation.bind(' . $jsSetupObject . ');
            Event.observe(
                "toggle' . $this->getHtmlId() . '",
                "click",
                ' . $jsSetupObject . '.toggle.bind(' . $jsSetupObject . ')
            );
            varienGlobalEvents.attachEventHandler("formSubmit", editorFormValidationHandler);
            varienGlobalEvents.clearEventHandlers("open_browser_callback");
            varienGlobalEvents.attachEventHandler(
                "open_browser_callback",'
                . $jsSetupObject . '.openFileBrowser
            );
                //]]>
                });
            </script>';
    }

    /**
     * @return string
     */
    protected function getWidgetsJs()
    {
        return '
            <script type="text/javascript">
                //<![CDATA[
                require(["jquery", "mage/translate", "mage/adminhtml/wysiwyg/widget"], function(jQuery){
                    (function($) {
                        $.mage.translate.add(' . \Zend_Json::encode($this->getButtonTranslations()) . ')
                    })(jQuery);
                });
                //]]>
            </script>';
    }
}