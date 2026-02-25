/**
 * 2007-2026 PrestaShop
 * License: AFL 3.0 (http://opensource.org/licenses/afl-3.0.php)
 * Updates the selected image when the combination changes reading #product-details[data-product].
 */
(function () {
  var DEBUG = false; // set to true for console logs

  function log() {
    if (DEBUG && console && console.log) console.log.apply(console, arguments);
  }
  function qs(sel) {
    return document.querySelector(sel);
  }

  function parseProductJSON() {
    try {
      var el = document.getElementById('product-details');
      if (!el || !el.dataset) return null;
      var raw = el.dataset.product || '';
      return raw ? JSON.parse(raw) : null;
    } catch (e) {
      return null;
    }
  }

  function getProductId() {
    var pj = parseProductJSON();
    if (pj && pj.id_product) return parseInt(pj.id_product) || 0;
    var input = document.querySelector('[name="id_product"]');
    return input ? parseInt(input.value) || 0 : 0;
  }

  function getIPAFromJSON() {
    var pj = parseProductJSON();
    if (!pj) return 0;
    return parseInt(pj.id_product_attribute) || 0;
  }

  function updateSelectedImage(ipa) {
    var img = qs('#selectimage-dynamic');
    if (!img) {
      log('[SelectImage] Missing #selectimage-dynamic');
      return;
    }

    var idProduct = getProductId();
    if (!idProduct) {
      log('[SelectImage] Missing id_product');
      return;
    }

    var type = img.getAttribute('data-image-type') || 'home_default';
    var base =
      (window.prestashop &&
        window.prestashop.urls &&
        window.prestashop.urls.base_url) ||
      '';

    var url =
      base +
      'index.php?fc=module&module=selectimage&controller=ajaxselectimage' +
      '&id_product=' +
      idProduct +
      '&id_product_attribute=' +
      (ipa || 0) +
      '&type=' +
      encodeURIComponent(type);

    fetch(url, { credentials: 'same-origin' })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        log('[SelectImage] Resp:', j);
        if (j && j.ok && j.url && img.getAttribute('src') !== j.url) {
          img.setAttribute('src', j.url);
        }
      })
      .catch(function () {});
  }

  function recalcAndUpdate() {
    var ipa = getIPAFromJSON();
    updateSelectedImage(ipa);

    // retry in case the theme updates data-product a bit later
    setTimeout(function () {
      var late = getIPAFromJSON();
      if (late && late !== ipa) updateSelectedImage(late);
    }, 60);
  }

  // 1) Observer over data-product (key in many themes)
  var pd = document.getElementById('product-details');
  if (pd) {
    var lastIpa = 0;
    try {
      var mo = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var m = mutations[i];
          if (m.type === 'attributes' && m.attributeName === 'data-product') {
            var ipa = getIPAFromJSON();
            if (ipa && ipa !== lastIpa) {
              lastIpa = ipa;
              recalcAndUpdate();
            }
          }
        }
      });
      mo.observe(pd, { attributes: true, attributeFilter: ['data-product'] });
    } catch (e) {}
  }

  // 2) Fallback events (themes differ)
  [
    'updatedProduct',
    'updateProduct',
    'changedCombination',
    'product-variant-change',
    'apUpdatedProduct',
    'apChangeCombination',
  ].forEach(function (ev) {
    document.addEventListener(ev, recalcAndUpdate);
  });

  // 3) Variant UI changes
  var variants = document.querySelector('.product-variants');
  if (variants) {
    ['change', 'input', 'click'].forEach(function (ev) {
      variants.addEventListener(ev, recalcAndUpdate);
    });
  }

  // 4) Soft polling as safety net
  (function softPoll() {
    var last = 0;
    setInterval(function () {
      var cur = getIPAFromJSON();
      if (cur && cur !== last) {
        last = cur;
        recalcAndUpdate();
      }
    }, 300);
  })();

  // 5) First run
  document.addEventListener('DOMContentLoaded', recalcAndUpdate);
})();