# SelectImage (PrestaShop 8)

**SelectImage** is a lightweight PrestaShop 8 module that provides a front-office hook to **embed a configurable product image inside the product page**.
The image to display is selected from the module configuration in the Back Office (by image order/position).

---

## Features

* Adds the `{hook h='selectImage' ...}` hook for product pages.
* Lets you choose **which image number** to display (1st, 2nd, 3rd, etc.) from the Back Office.
* Optional fallback to the **cover image** if the selected index does not exist.
* Supports combination images (if the selected combination has images); otherwise falls back to product images.
* Includes an AJAX controller and a small JS script to update the image when the combination changes.

---

## Requirements

* PrestaShop **8.0+**
* PHP **8.1+** (recommended)

---

## Installation

1. Copy the module folder into your shop:

   ```
   /modules/selectimage/
   ```
2. Go to **Back Office → Modules → Module Manager**.
3. Search for **SelectImage** and click **Install**.
4. Click **Configure** and set:

   * **Image number to display**
   * **Fallback to cover image**

---

## Usage (Theme Integration)

Add this snippet in your product template (for example `themes/your-theme/templates/catalog/product.tpl`):

```smarty
{* SelectImage Module *}
<div class="col-lg-3 col-md-4 col-sm-12">
  {hook h='selectImage'
        product=$product
        id_product_attribute=$product.currentCombination.id_product_attribute|default:0
        type='home_default'
        lazy=true
        show_meta_title=true}
</div>
```

### Parameters

* `product` *(required)*: the product object/array.
* `id_product_attribute` *(optional)*: current combination id.
* `type` *(optional)*: image type (e.g. `home_default`, `large_default`). Default: `large_default`.
* `lazy` *(optional)*: enable lazy loading in template (boolean).
* `show_meta_title` *(optional)*: prints the product meta title (boolean).

---

## Front Controller (AJAX)

The module exposes an AJAX endpoint used by `selectimage.js`:

* `index.php?fc=module&module=selectimage&controller=ajaxselectimage`

It returns JSON:

```json
{ "ok": true, "url": "https://..." }
```

---

## File Structure

* Main module: `modules/selectimage/selectimage.php`
* Template: `modules/selectimage/views/templates/hook/selectimage.tpl`
* JS: `modules/selectimage/views/js/selectimage.js`
* AJAX controller: `modules/selectimage/controllers/front/ajaxselectimage.php`

---

## Notes

* The displayed image is selected **by image position order** (as defined in the product images list).
* If a combination has its own images, those are used first (also sorted by position).
* If the selected index is out of bounds, the module can optionally fallback to the cover image.

---

## License

AFL 3.0

---

## Changelog

### 1.0.0

* Initial release: configurable product image embed hook + AJAX update on combination changes.
