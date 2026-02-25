<?php
/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer versions in the future.
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Selectimage extends Module
{
    public function __construct()
    {
        $this->name = 'selectimage';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Tu Nombre / Empresa';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Select product image', [], 'Modules.Selectimage.Admin');
        $this->description = $this->trans(
            'Provides a hook to embed a selected product image inside the product page, configurable from the back office.',
            [],
            'Modules.Selectimage.Admin'
        );

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('selectImage')
            && $this->registerHook('displayHeader')
            // 1 = first image, 2 = second image (default)
            && Configuration::updateValue('SELECTIMAGE_INDEX', 2)
            // fallback to cover if chosen index does not exist
            && Configuration::updateValue('SELECTIMAGE_FALLBACK_COVER', 1);
    }

    public function uninstall()
    {
        return parent::uninstall()
            && Configuration::deleteByName('SELECTIMAGE_INDEX')
            && Configuration::deleteByName('SELECTIMAGE_FALLBACK_COVER');
    }

    public function hookDisplayHeader()
    {
        if ('product' === $this->context->controller->php_self) {
            $this->context->controller->registerJavascript(
                'module-selectimage',
                'modules/' . $this->name . '/views/js/selectimage.js',
                ['position' => 'bottom', 'priority' => 150]
            );
        }
    }

    /**
     * Back office configuration page
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitSelectimageConfig')) {
            $index = (int) Tools::getValue('SELECTIMAGE_INDEX');
            if ($index < 1) {
                $index = 1;
            }

            $fallbackCover = (int) Tools::getValue('SELECTIMAGE_FALLBACK_COVER');

            Configuration::updateValue('SELECTIMAGE_INDEX', $index);
            Configuration::updateValue('SELECTIMAGE_FALLBACK_COVER', $fallbackCover);

            $output .= $this->displayConfirmation($this->trans('Settings updated.', [], 'Admin.Notifications.Success'));
        }

        return $output . $this->renderForm();
    }

    protected function renderForm()
    {
        $defaultLang = (int) $this->context->language->id;

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Select image settings', [], 'Modules.Selectimage.Admin'),
                    'icon'  => 'icon-picture',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Image number to display', [], 'Modules.Selectimage.Admin'),
                        'name' => 'SELECTIMAGE_INDEX',
                        'class' => 'fixed-width-sm',
                        'desc' => $this->trans(
                            '1 = first image, 2 = second image, etc. Based on image position order.',
                            [],
                            'Modules.Selectimage.Admin'
                        ),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Fallback to cover image', [], 'Modules.Selectimage.Admin'),
                        'name' => 'SELECTIMAGE_FALLBACK_COVER',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                        'desc' => $this->trans(
                            'If the chosen image does not exist, use the cover image when available.',
                            [],
                            'Modules.Selectimage.Admin'
                        ),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitSelectimageConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value = [
            'SELECTIMAGE_INDEX' => (int) Configuration::get('SELECTIMAGE_INDEX', null, null, null, 2),
            'SELECTIMAGE_FALLBACK_COVER' => (int) Configuration::get('SELECTIMAGE_FALLBACK_COVER', null, null, null, 1),
        ];

        return $helper->generateForm([$fieldsForm]);
    }

    /**
     * Hook usage example in product.tpl:
     * {hook h='selectImage' product=$product id_product_attribute=$product.currentCombination.id_product_attribute type='large_default'}
     *
     * Params:
     * - product (array|object) optional
     * - id_product_attribute (int) optional
     * - type (string) image type, default 'large_default'
     * - lazy (bool) optional for template
     * - show_meta_title (bool) optional for template
     */
    public function hookSelectImage($params)
    {
        $context = $this->context;
        $idLang  = (int) $context->language->id;

        // Resolve product
        $product = null;
        if (isset($params['product'])) {
            if (is_array($params['product']) && isset($params['product']['id_product'])) {
                $product = new Product((int) $params['product']['id_product'], false, $idLang);
            } elseif (is_object($params['product']) && isset($params['product']->id)) {
                $product = new Product((int) $params['product']->id, false, $idLang);
            }
        } elseif ((int) Tools::getValue('id_product')) {
            $product = new Product((int) Tools::getValue('id_product'), false, $idLang);
        }

        if (!$product || !Validate::isLoadedObject($product)) {
            return '';
        }

        // Resolve combination (IPA)
        $ipa = 0;
        if (isset($params['id_product_attribute'])) {
            $ipa = (int) $params['id_product_attribute'];
        } elseif ((int) Tools::getValue('id_product_attribute')) {
            $ipa = (int) Tools::getValue('id_product_attribute');
        }

        if ($ipa <= 0) {
            $ipa = (int) $product->getDefaultIdProductAttribute();
        }

        // All images (position + cover)
        $allImages = $product->getImages($idLang);
        if (!$allImages) {
            return '';
        }

        $posMap = [];
        $coverId = 0;
        foreach ($allImages as $im) {
            $idImg = (int) $im['id_image'];
            $posMap[$idImg] = isset($im['position']) ? (int) $im['position'] : 0;
            if (!empty($im['cover'])) {
                $coverId = $idImg;
            }
        }

        // Combination images
        $combImages = [];
        $combMap = $product->getCombinationImages($idLang); // [ipa => [ [id_image], ... ] ]

        if (isset($combMap[$ipa]) && is_array($combMap[$ipa]) && count($combMap[$ipa]) > 0) {
            $combImages = $combMap[$ipa];
        }

        // Fallback: use all product images
        if (!$combImages) {
            $combImages = array_map(static function ($im) {
                return ['id_image' => (int) $im['id_image']];
            }, $allImages);
        }

        // Sort by position
        usort($combImages, function ($a, $b) use ($posMap) {
            $pa = $posMap[(int) $a['id_image']] ?? 0;
            $pb = $posMap[(int) $b['id_image']] ?? 0;
            return $pa <=> $pb;
        });

        // Configured index (1-based in BO)
        $wantedIndex1Based = (int) Configuration::get('SELECTIMAGE_INDEX', null, null, null, 2);
        if ($wantedIndex1Based < 1) {
            $wantedIndex1Based = 1;
        }
        $wantedIndex = $wantedIndex1Based - 1;

        $useCoverFallback = (bool) Configuration::get('SELECTIMAGE_FALLBACK_COVER', null, null, null, 1);

        // Pick image by index + fallbacks
        $chosenId = 0;
        if (isset($combImages[$wantedIndex]['id_image'])) {
            $chosenId = (int) $combImages[$wantedIndex]['id_image'];
        } elseif ($useCoverFallback && $coverId) {
            $chosenId = $coverId;
        } elseif (isset($combImages[0]['id_image'])) {
            $chosenId = (int) $combImages[0]['id_image'];
        }

        if ($chosenId <= 0) {
            return '';
        }

        $type = (isset($params['type']) && is_string($params['type'])) ? pSQL($params['type']) : 'large_default';
        $url  = $context->link->getImageLink($product->link_rewrite, $chosenId, $type);

        // Reuso nombres para no obligarte a cambiar el tpl si vienes del módulo anterior:
        $context->smarty->assign([
            'second_image_url'        => $url,
            'second_image_name'       => $product->name,
            'second_image_meta_title' => !empty($product->meta_title) ? $product->meta_title : $product->name,
            'id_product_attribute'    => $ipa,
            'lazy'                    => isset($params['lazy']) ? (bool) $params['lazy'] : true,
            'show_meta_title'         => isset($params['show_meta_title']) ? (bool) $params['show_meta_title'] : true,
            'selectimage_index'       => $wantedIndex1Based,
        ]);

        // Plantilla:
        // modules/selectimage/views/templates/hook/selectimage.tpl
        return $this->display(__FILE__, 'views/templates/hook/selectimage.tpl');
    }
}