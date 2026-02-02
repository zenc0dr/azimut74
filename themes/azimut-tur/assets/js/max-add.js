(function () {
    var iframeId = "CalltouchWidgetFrame";
    var buttonId = "MaxMessengerOverlayButton";
    var backdropId = "MaxMessengerOverlayBackdrop";
    var buttonSize = 64;
    var iframeOpenHeight = 256;
    var iframeClosedHeight = 112;
    var mobileBackdropHeight = 74;
    var mobileBackdropOffset = 228;

    function ensureButton() {
        var existing = document.getElementById(buttonId);
        if (existing) return existing;

        var btn = document.createElement("a");
        btn.id = buttonId;
        btn.href = "https://max.ru/id6454120758_bot";
        btn.target = "_blank";
        btn.rel = "noopener noreferrer";
        btn.setAttribute("aria-label", "MAX");

        btn.style.position = "fixed";
        btn.style.width = buttonSize + "px";
        btn.style.height = buttonSize + "px";
        btn.style.borderRadius = "50%";
        btn.style.display = "none";
        btn.style.backgroundColor = "#ffffff";
        btn.style.backgroundImage = "url(/themes/azimut-tur-pro/assets/images/icons/max-messenger-sign-logo.svg)";
        btn.style.backgroundRepeat = "no-repeat";
        btn.style.backgroundPosition = "center";
        btn.style.backgroundSize = "60%";
        btn.style.boxShadow = "0 6px 16px rgba(0, 0, 0, 0.2)";
        btn.style.zIndex = "2147483647";
        btn.style.cursor = "pointer";

        document.body.appendChild(btn);
        return btn;
    }

    function ensureBackdrop() {
        var existing = document.getElementById(backdropId);
        if (existing) return existing;

        var backdrop = document.createElement("div");
        backdrop.id = backdropId;
        backdrop.style.position = "fixed";
        backdrop.style.width = "100%";
        backdrop.style.left = "0";
        backdrop.style.bottom = mobileBackdropOffset + "px";
        backdrop.style.height = mobileBackdropHeight + "px";
        backdrop.style.borderRadius = "0";
        backdrop.style.maxHeight = "100%";
        backdrop.style.background = "linear-gradient(90deg, rgba(31, 40, 44, 0.5), rgba(31, 40, 44, 0.5))";
        backdrop.style.display = "none";
        backdrop.style.zIndex = "2147483646";

        document.body.appendChild(backdrop);
        return backdrop;
    }

    function isMobileMode() {
        var ua = navigator.userAgent || "";
        if (/Android|iPhone|iPad|iPod|Mobile/i.test(ua)) return true;
        if (window.matchMedia && window.matchMedia("(pointer: coarse)").matches) return true;
        return false;
    }

    var showTimer = null;

    function applyPosition(iframe, btn, mobile) {
        var rect = iframe.getBoundingClientRect();
        var top = rect.top;
        var right = rect.right;

        if (mobile) {
            var backdrop = ensureBackdrop();
            var desiredLeft = right - buttonSize - 10;
            var clampedLeft = Math.min(window.innerWidth - buttonSize - 8, Math.max(8, desiredLeft));

            backdrop.style.display = "block";
            btn.style.display = "block";
            btn.style.left = clampedLeft + "px";
            btn.style.top = "";
            btn.style.bottom = mobileBackdropOffset + "px";
            return;
        }

        btn.style.display = "block";
        btn.style.top = Math.max(8, top - 50) + "px";
        btn.style.left = (right - buttonSize - 24) + "px";
    }

    function positionButton(iframe, btn) {
        var rect = iframe.getBoundingClientRect();
        var isExpanded = rect.height >= iframeOpenHeight - 8;
        var mobile = isMobileMode();

        if (!isExpanded) {
            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }
            btn.style.display = "none";
            var backdrop = document.getElementById(backdropId);
            if (backdrop) backdrop.style.display = "none";
            return;
        }

        if (!showTimer && btn.style.display !== "block") {
            showTimer = setTimeout(function () {
                showTimer = null;
                applyPosition(iframe, btn, mobile);
            }, 1000);
            return;
        }

        if (btn.style.display === "block") {
            applyPosition(iframe, btn, mobile);
        }
    }

    function bindToIframe(iframe) {
        var btn = ensureButton();
        var update = function () { positionButton(iframe, btn); };

        update();

        if (window.ResizeObserver) {
            var ro = new ResizeObserver(update);
            ro.observe(iframe);
        }

        if (window.MutationObserver) {
            var mo = new MutationObserver(update);
            mo.observe(iframe, { attributes: true, attributeFilter: ["style"] });
        }

        window.addEventListener("scroll", update);
        window.addEventListener("resize", update);
    }

    function waitForIframe() {
        var iframe = document.getElementById(iframeId);
        if (iframe) {
            bindToIframe(iframe);
            return;
        }

        var tries = 0;
        var timer = setInterval(function () {
            tries += 1;
            iframe = document.getElementById(iframeId);
            if (iframe || tries >= 60) {
                clearInterval(timer);
                if (iframe) bindToIframe(iframe);
            }
        }, 500);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", waitForIframe);
    } else {
        waitForIframe();
    }
})();
