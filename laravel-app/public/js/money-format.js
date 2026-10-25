(function () {
    function stripCommas(value) {
        return String(value == null ? '' : value).replace(/,/g, '');
    }

    var nativeParseFloat = window.parseFloat;
    window.parseFloat = function (value) {
        if (typeof value === 'string') {
            value = stripCommas(value);
        }
        return nativeParseFloat(value);
    };

    var nativeParseInt = window.parseInt;
    window.parseInt = function (value, radix) {
        if (typeof value === 'string' && (radix === undefined || radix === 10)) {
            value = stripCommas(value);
        }
        return nativeParseInt(value, radix);
    };

    function stripGrouped(text) {
        return String(text).replace(/(-?\d{1,3}(?:,\d{3})+)(\.\d+)?/g, function (_, number, decimal) {
            return number.replace(/,/g, '') + (decimal || '');
        });
    }

    if (window.jQuery && window.jQuery.param) {
        var nativeParam = window.jQuery.param;
        window.jQuery.param = function (obj, traditional) {
            return stripGrouped(nativeParam.call(this, obj, traditional));
        };
        window.jQuery.ajaxPrefilter(function (options) {
            if (typeof options.data === 'string') {
                options.data = stripGrouped(options.data);
            }
        });
    }

    document.addEventListener('submit', function (event) {
        var fields = event.target && event.target.querySelectorAll ? event.target.querySelectorAll('input, textarea') : [];
        Array.prototype.forEach.call(fields, function (field) {
            if (field.value && field.value.indexOf(',') !== -1) {
                field.value = stripGrouped(field.value);
            }
        });
    }, true);

    if (/timesheet|internship|hour/i.test(location.pathname || '')) {
        return;
    }

    function moneyHint(el) {
        if (!el) {
            return '';
        }
        var blob = '';
        var node = el;
        for (var i = 0; i < 4 && node; i++) {
            blob += ' ' + (node.className || '') + ' ' + (node.id || '') + ' ' + (node.name || '');
            node = node.parentElement;
        }
        return blob;
    }

    function looksLikeMoney(hint) {
        if (/\b(id|code|phone|qty|quantity|reference|rate|hour|date|search|email)\b/i.test(hint)) {
            return false;
        }
        return /price|total|amount|paid|balance|cost|grand|subtotal|owing|deposit|discount|salary|fee|revenue|expense|change/i.test(hint);
    }

    function grouped(amount) {
        return Math.round(amount).toLocaleString('en-US');
    }

    function formatText(text, hint) {
        if (!text || /hour|hrs/i.test(text)) {
            return text;
        }
        var formatted = text;
        if (text.indexOf('.00') !== -1) {
            formatted = text.replace(/(^|[^\d.,])(-?\d{1,3}(?:,\d{3})*|-?\d+)\.00(?![\d%])/g, function (match, prefix, number) {
                var amount = Math.round(Number(stripCommas(number)));
                if (!isFinite(amount)) {
                    return match;
                }
                return prefix + grouped(amount);
            });
        }
        var trimmed = formatted.trim();
        if (/^-?\d{4,}$/.test(trimmed) && looksLikeMoney(hint || '')) {
            var whole = Number(trimmed);
            if (isFinite(whole) && (whole < 1900 || whole > 2099)) {
                formatted = formatted.replace(trimmed, grouped(whole));
            }
        }
        return formatted;
    }

    function skipText(node) {
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
        if (!node || node.nodeType !== 3 || skipText(node)) {
            return;
        }
        var next = formatText(node.nodeValue, moneyHint(node.parentElement));
        if (next !== node.nodeValue) {
            node.nodeValue = next;
        }
    }

    function displayForInput(el, value) {
        if (value == null || el == null) {
            return value;
        }
        var type = String(el.type || '').toLowerCase();
        if (type === 'hidden' || type === 'checkbox' || type === 'radio' || type === 'file' || type === 'password' || type === 'date' || type === 'datetime-local' || type === 'email' || type === 'search') {
            return value;
        }
        var hint = moneyHint(el);
        if (/qty|quantity|phone|code|reference|hour|date|search|email/i.test(hint)) {
            return value;
        }
        var text = String(value);
        if (text.indexOf('.00') === -1 && !(/^-?\d{4,}(\.0+)?$/.test(text.trim()) && looksLikeMoney(hint))) {
            return value;
        }
        if (type === 'number') {
            if (/\.00$/.test(text.trim())) {
                return String(Math.round(Number(stripCommas(text))));
            }
            return value;
        }
        return formatText(text, hint);
    }

    function formatInput(el) {
        if (!el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
            return;
        }
        var next = displayForInput(el, el.value);
        if (next !== el.value) {
            el.value = next;
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
        if (el.nodeType !== 1) {
            return;
        }
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            formatInput(el);
        }
        if (!document.createTreeWalker || el.tagName === 'SCRIPT' || el.tagName === 'STYLE') {
            return;
        }
        var walker = document.createTreeWalker(el, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, null);
        var node;
        while ((node = walker.nextNode())) {
            if (node.nodeType === 3) {
                formatNode(node);
            } else if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
                formatInput(node);
            }
        }
    }

    if (window.jQuery) {
        var textFn = window.jQuery.fn.text;
        window.jQuery.fn.text = function (value) {
            if (!arguments.length || typeof value === 'function') {
                return textFn.apply(this, arguments);
            }
            var hint = moneyHint(this[0]);
            return textFn.call(this, formatText(String(value), hint));
        };
        var valFn = window.jQuery.fn.val;
        window.jQuery.fn.val = function (value) {
            if (!arguments.length || typeof value === 'function') {
                return valFn.apply(this, arguments);
            }
            var incoming = value;
            return valFn.call(this, function () {
                return displayForInput(this, incoming);
            });
        };
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
                if (record.type === 'attributes' && record.target) {
                    formatInput(record.target);
                }
                Array.prototype.forEach.call(record.addedNodes || [], function (node) {
                    formatElement(node);
                });
            });
        }).observe(document.body, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['value']
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
