(function () {
    if (/timesheet|internship|hour/i.test(location.pathname || '')) {
        return;
    }

    function formatText(text) {
        if (!text || text.indexOf('.00') === -1 || /hour|hrs/i.test(text)) {
            return text;
        }
        return text.replace(/(^|[^\d.,])(-?\d{1,3}(?:,\d{3})*|-?\d+)\.00(?![\d%])/g, function (match, prefix, number) {
            var amount = Math.round(Number(String(number).replace(/,/g, '')));
            if (!isFinite(amount)) {
                return match;
            }
            return prefix + amount.toLocaleString('en-US');
        });
    }

    function skip(node) {
        var el = node.parentElement;
        if (!el) {
            return true;
        }
        var tag = el.tagName;
        if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CODE') {
            return true;
        }
        if (el.closest && el.closest('script, style, textarea, input')) {
            return true;
        }
        var hint = ((el.className || '') + ' ' + (el.id || '')).toLowerCase();
        return /hour|timesheet|filesize|file-size/.test(hint);
    }

    function formatNode(node) {
        if (!node || node.nodeType !== 3 || skip(node)) {
            return;
        }
        var next = formatText(node.nodeValue);
        if (next !== node.nodeValue) {
            node.nodeValue = next;
        }
    }

    function formatElement(el) {
        if (!el) {
            return;
        }
        if (el.nodeType === 3) {
            formatNode(el);
            return;
        }
        if (el.nodeType !== 1 || !document.createTreeWalker) {
            return;
        }
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, null);
        var node;
        while ((node = walker.nextNode())) {
            formatNode(node);
        }
    }

    function start() {
        if (!document.body) {
            return;
        }
        formatElement(document.body);
        if (!window.MutationObserver) {
            return;
        }
        new MutationObserver(function (records) {
            records.forEach(function (record) {
                if (record.type === 'characterData') {
                    formatNode(record.target);
                }
                Array.prototype.forEach.call(record.addedNodes || [], function (node) {
                    if (node.nodeType === 3) {
                        formatNode(node);
                    } else if (node.nodeType === 1) {
                        formatElement(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true, characterData: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
