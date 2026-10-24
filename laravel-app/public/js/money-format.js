(function () {
    if (/timesheet|internship|hour/i.test(location.pathname || '')) {
        return;
    }

    function moneyHint(el) {
        var blob = '';
        var node = el;
        for (var i = 0; i < 4 && node; i++) {
            blob += ' ' + (node.className || '') + ' ' + (node.id || '');
            node = node.parentElement;
        }
        return blob;
    }

    function looksLikeMoney(hint) {
        if (/\b(id|code|phone|qty|quantity|reference|rate|hour|date)\b/i.test(hint)) {
            return false;
        }
        return /price|total|amount|paid|balance|cost|grand|subtotal|owing|deposit|discount|salary|fee|revenue|expense/i.test(hint);
    }

    function formatText(text, hint) {
        if (!text || /hour|hrs/i.test(text)) {
            return text;
        }
        var formatted = text;
        if (text.indexOf('.00') !== -1) {
            formatted = text.replace(/(^|[^\d.,])(-?\d{1,3}(?:,\d{3})*|-?\d+)\.00(?![\d%])/g, function (match, prefix, number) {
                var amount = Math.round(Number(String(number).replace(/,/g, '')));
                if (!isFinite(amount)) {
                    return match;
                }
                return prefix + amount.toLocaleString('en-US');
            });
        }
        var trimmed = formatted.trim();
        if (/^-?\d{4,}$/.test(trimmed) && looksLikeMoney(hint || '')) {
            var whole = Number(trimmed);
            if (isFinite(whole) && (whole < 1900 || whole > 2099)) {
                formatted = formatted.replace(trimmed, whole.toLocaleString('en-US'));
            }
        }
        return formatted;
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
        var next = formatText(node.nodeValue, moneyHint(node.parentElement));
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
