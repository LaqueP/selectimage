{*
 * 2007-2026 PrestaShop
 * License: AFL 3.0 (http://opensource.org/licenses/afl-3.0.php)
*}

<img id="selectimage-dynamic"
     class="product-selected-image"
     src="{$second_image_url|escape:'html':'UTF-8'}"
     alt="{$second_image_name|escape:'html':'UTF-8'}"
     data-image-type="home_default"
     loading="{if isset($lazy) && $lazy}lazy{else}eager{/if}"
     decoding="async"
     fetchpriority="low" />

{if isset($show_meta_title) && $show_meta_title}
  <div class="selectimage-meta-title" style="margin-top:.5rem;">
    {$second_image_meta_title|escape:'html':'UTF-8'}
  </div>
{/if}