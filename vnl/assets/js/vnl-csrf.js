/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — CSRF istemcisi
 *  jQuery'siz isteklere (fetch, XMLHttpRequest, sendBeacon)
 *  CSRF jetonunu ekler.
 *  Authors: Voxnup Authors
 * ============================================================
 */

/**
 * VnlDispatcher /app/* altındaki her POST'ta CSRF jetonu ister (403 VNL_CSRF).
 * vnl.js bunu yalnız jQuery isteklerine ekler (ajaxPrefilter); bu dosya aynı
 * işi jQuery'den bağımsız yapar, çağrı noktası değişmez:
 *
 *   fetch('/app/rapor/kaydet', { method: 'POST', body: fd })   → X-CSRF-Token başlığı
 *   xhr.open('POST', '/app/x/y'); xhr.send(body)               → X-CSRF-Token başlığı
 *   navigator.sendBeacon('/app/iz/kaydet', fd)                  → gövdede _token alanı
 *
 * Kurallar:
 *  - Yalnız AYNI KÖKENE giden istek jeton alır (vnl.js'in crossDomain ölçüsü:
 *    şema + makine + port). Başka kökene jeton hiç gitmez.
 *  - GET, HEAD ve OPTIONS jeton almaz: dağıtıcı /app'te yalnız POST kabul eder,
 *    okuma isteğinin jetonu taşıması sızıntıdan başka bir şey kazandırmaz.
 *  - Jeton her istekte <meta name="csrf-token">'den yeniden okunur; giriş/çıkış
 *    jetonu döndürse de (vnlSetCsrfToken) sonraki istek doğru jetonu taşır.
 *  - Çağıran başlığı zaten koymuşsa (vnl.js'in jQuery ön süzgeci, VNL Table)
 *    dokunulmaz. XHR'da bu önemli: setRequestHeader iki kez çağrılırsa değerler
 *    "a, b" diye birleşir ve sunucu jetonu reddeder.
 *  - sendBeacon başlık taşıyamaz; jeton gövdeye `_token` alanı olarak girer
 *    (vnl_csrf_check o alanı da okur). Yalnız FormData, URLSearchParams ya da
 *    boş gövdede yapılabilir; dizge/Blob gövdeli beacon olduğu gibi gider.
 *
 * Çekirdek görünüm taşımadığı için bu dosyayı projenin <head>'i yükler
 * (şablonda head-standard.php, meta etiketinin hemen altında). jQuery'ye, vnl.js'e
 * ya da sıraya bağlı değildir; iki kez yüklenirse ikincisi bir şey yapmaz.
 * Jeton eskidiyse gelen 403 VNL_CSRF yanıtı çağıranındır — fetch kullanan kod
 * isterse vnl.js'in window.vnlHandleAjaxError(status, json, {}) kancasına verir.
 */
(function () {
    'use strict';

    if (window.__vnlCsrfClient) return;
    window.__vnlCsrfClient = true;

    var HEADER = 'X-CSRF-Token';
    var FIELD  = '_token';

    function token() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? (m.getAttribute('content') || '') : '';
    }

    /* vnl.js aynı fonksiyonu (aynı davranışla) yeniden tanımlar; vnl.js'siz sayfada da bulunsun. */
    if (typeof window.vnlCsrfToken !== 'function') window.vnlCsrfToken = token;

    function sameOrigin(url) {
        try {
            return new URL(String(url), document.baseURI || location.href).origin === location.origin;
        } catch (e) {
            return false;
        }
    }

    function writes(method) {
        var m = String(method || 'GET').toUpperCase();
        return m !== 'GET' && m !== 'HEAD' && m !== 'OPTIONS';
    }

    /* ---- XMLHttpRequest ($.ajax da buradan geçer) ---------------------------- */
    if (window.XMLHttpRequest) {
        var proto     = XMLHttpRequest.prototype;
        var xhrOpen   = proto.open;
        var xhrHeader = proto.setRequestHeader;
        var xhrSend   = proto.send;

        proto.open = function (method, url) {
            this.__vnlCsrf    = writes(method) && sameOrigin(url);
            this.__vnlCsrfSet = false;
            return xhrOpen.apply(this, arguments);
        };
        proto.setRequestHeader = function (name) {
            if (String(name).toLowerCase() === HEADER.toLowerCase()) this.__vnlCsrfSet = true;
            return xhrHeader.apply(this, arguments);
        };
        proto.send = function () {
            if (this.__vnlCsrf && !this.__vnlCsrfSet) {
                var t = token();
                if (t) xhrHeader.call(this, HEADER, t);
            }
            return xhrSend.apply(this, arguments);
        };
    }

    /* ---- fetch ----------------------------------------------------------------- */
    if (typeof window.fetch === 'function') {
        var nativeFetch = window.fetch;

        window.fetch = function (input, init) {
            var req    = (typeof Request !== 'undefined' && input instanceof Request) ? input : null;
            var method = (init && init.method) || (req && req.method) || 'GET';
            var t;

            if (writes(method) && sameOrigin(req ? req.url : input) && (t = token())) {
                /* init.headers verilirse Request'in başlıklarının yerine geçer; verilmediyse onlardan başla. */
                var headers = new Headers((init && init.headers) || (req ? req.headers : undefined));
                if (!headers.has(HEADER)) {
                    headers.set(HEADER, t);
                    init = Object.assign({}, init, { headers: headers });
                }
            }

            return nativeFetch.call(window, input, init);
        };
    }

    /* ---- navigator.sendBeacon ------------------------------------------------- */
    if (window.navigator && typeof navigator.sendBeacon === 'function') {
        var nativeBeacon = navigator.sendBeacon;

        navigator.sendBeacon = function (url, data) {
            var t = sameOrigin(url) ? token() : '';

            if (t) {
                if (data === undefined || data === null) {
                    data = new URLSearchParams();
                    data.set(FIELD, t);
                } else if (typeof URLSearchParams !== 'undefined' && data instanceof URLSearchParams) {
                    if (!data.has(FIELD)) { data = new URLSearchParams(data); data.set(FIELD, t); }
                } else if (typeof FormData !== 'undefined' && data instanceof FormData) {
                    /* Çağıranın nesnesi değiştirilmez: kopyası gider. */
                    if (!data.has(FIELD)) {
                        var copy = new FormData();
                        data.forEach(function (v, k) { copy.append(k, v); });
                        copy.set(FIELD, t);
                        data = copy;
                    }
                }
            }

            return nativeBeacon.call(navigator, url, data);
        };
    }
})();
