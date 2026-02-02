(function () {
    var iframeId = "CalltouchWidgetFrame";
    var buttonId = "MaxMessengerOverlayButton";
    var buttonSize = 64;
    var iframeOpenHeight = 256;
    var iframeClosedHeight = 112;

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

    var showTimer = null;

    function positionButton(iframe, btn) {
        var rect = iframe.getBoundingClientRect();
        var isExpanded = rect.height >= iframeOpenHeight - 8;

        if (!isExpanded) {
            if (showTimer) {
                clearTimeout(showTimer);
                showTimer = null;
            }
            btn.style.display = "none";
            return;
        }

        var top = rect.top;
        var right = rect.right;

        if (!showTimer && btn.style.display !== "block") {
            showTimer = setTimeout(function () {
                btn.style.display = "block";
                btn.style.top = Math.max(8, top - 50) + "px";
                btn.style.left = (right - buttonSize - 24) + "px";
                showTimer = null;
            }, 1000);
            return;
        }

        if (btn.style.display === "block") {
            btn.style.top = Math.max(8, top - 50) + "px";
            btn.style.left = (right - buttonSize - 24) + "px";
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
