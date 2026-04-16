(function () {
    var SESSION_KEY = 'promoPopupShown';
    var COOKIE_KEY = 'promo_popup_shown';
    var initialized = false;

    function hasSessionFlag() {
        try {
            if (window.sessionStorage && sessionStorage.getItem(SESSION_KEY) === '1') {
                return true;
            }
        } catch (e) {}

        return document.cookie.indexOf(COOKIE_KEY + '=1') !== -1;
    }

    function setSessionFlag() {
        try {
            if (window.sessionStorage) {
                sessionStorage.setItem(SESSION_KEY, '1');
            }
        } catch (e) {}

        document.cookie = COOKIE_KEY + '=1; path=/; SameSite=Lax';
    }

    function closePopup(popup) {
        popup.classList.remove('is-visible');
        popup.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('promo-popup-lock');
        document.body.classList.remove('promo-popup-lock');
    }

    function openPopup(popup) {
        if (hasSessionFlag()) {
            return;
        }

        setSessionFlag();
        popup.classList.add('is-visible');
        popup.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('promo-popup-lock');
        document.body.classList.add('promo-popup-lock');
    }

    function initPopup() {
        if (initialized) {
            return;
        }

        var popup = document.getElementById('promo-popup');
        if (!popup) {
            return;
        }

        initialized = true;

        popup.querySelectorAll('[data-promo-close]').forEach(function (node) {
            node.addEventListener('click', function () {
                closePopup(popup);
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && popup.classList.contains('is-visible')) {
                closePopup(popup);
            }
        });

        var delayAttr = popup.getAttribute('data-delay');
        var delayMs = parseInt(delayAttr, 10);
        if (isNaN(delayMs) || delayMs < 0) {
            delayMs = 60000;
        }

        setTimeout(function () {
            openPopup(popup);
        }, delayMs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPopup);
    } else {
        initPopup();
    }
})();
