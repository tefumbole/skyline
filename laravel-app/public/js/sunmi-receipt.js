/*
 * SUNMI receipt adapter.
 * Uses the official UMD build from https://h5.sunmi.com/printer-sdk/dist/bundle.umd.js
 * Methods used are the ones the official demo calls. This file does not talk to the server.
 */
(function (window) {
    var sdk = null;
    var ready = null;
    var busy = false;

    function paperDots(mm) {
        return Number(mm) === 58 ? 384 : 576;
    }

    function columns(mm) {
        return Number(mm) === 58 ? 32 : 48;
    }

    function textOf(value) {
        if (value == null) {
            return '';
        }
        if (typeof value === 'string') {
            return value;
        }
        try {
            return JSON.stringify(value);
        } catch (e) {
            return String(value);
        }
    }

    function saysPrinted(payload) {
        if (payload && typeof payload === 'object') {
            if (payload.printed === true || payload.printFinished === true) {
                return true;
            }
            var status = payload.status || payload.result || payload.message;
            if (typeof status === 'string' && /print(ed| finished| complete)/i.test(status)) {
                return true;
            }
            return false;
        }
        return typeof payload === 'string' && /^\s*printed\s*$/i.test(payload);
    }

    function outcome(payload) {
        return saysPrinted(payload) ? 'Printed' : 'Sent to printer.';
    }

    function failText(error) {
        var detail = textOf(error && error.message ? error.message : error);
        if (!detail) {
            detail = 'The printer did not answer.';
        }
        return detail + ' The terminal must open sunmi://com.sunmi:8888/websdk and accept ws://localhost:7070/ws. The installed POS app does not provide that service. Reprint only if you press Reprint.';
    }

    function connect() {
        if (ready) {
            return ready;
        }
        ready = new Promise(function (resolve, reject) {
            if (typeof window.SUNMI !== 'function') {
                reject(new Error('The SUNMI printer script did not load.'));
                return;
            }
            try {
                sdk = new window.SUNMI();
            } catch (e) {
                reject(e);
                return;
            }
            sdk.launchPrinterService().then(function () {
                sdk.init();
                var started = Date.now();
                (function wait() {
                    if (sdk.socketManager && sdk.socketManager.connected && sdk.printer) {
                        resolve(sdk);
                        return;
                    }
                    if (Date.now() - started > 4000) {
                        reject(new Error('Socket not connected'));
                        return;
                    }
                    setTimeout(wait, 100);
                })();
            }).catch(reject);
        }).catch(function (error) {
            ready = null;
            sdk = null;
            throw error;
        });
        return ready;
    }

    function padLine(left, right, width) {
        left = String(left || '');
        right = String(right || '');
        if (left.length + right.length + 1 > width) {
            var room = Math.max(1, width - right.length - 1);
            left = left.slice(0, room);
        }
        var gap = width - left.length - right.length;
        if (gap < 1) {
            gap = 1;
        }
        return left + new Array(gap + 1).join(' ') + right;
    }

    function receiptLines(receipt, mm) {
        var width = columns(mm);
        var lines = [];
        function add(text) {
            var value = String(text || '');
            if (value.length <= width) {
                lines.push(value);
                return;
            }
            while (value.length > width) {
                lines.push(value.slice(0, width));
                value = value.slice(width);
            }
            if (value) {
                lines.push(value);
            }
        }
        add(receipt.hotel || 'Skyline Hotel');
        add(receipt.title || 'Sales Invoice');
        if (receipt.reference) {
            add('Ref ' + receipt.reference);
        }
        if (receipt.date) {
            add(receipt.date);
        }
        if (receipt.cashier) {
            add('Cashier ' + receipt.cashier);
        }
        if (receipt.customer) {
            add(receipt.customer);
        }
        if (receipt.status) {
            add('Status ' + receipt.status);
        }
        lines.push(' ');
        (receipt.items || []).forEach(function (item) {
            add(item.name || 'Item');
            add(padLine((item.qty || '') + ' x ' + (item.unit || ''), item.total || '', width));
        });
        (receipt.totals || []).forEach(function (row) {
            add(padLine(row.label || '', row.value || '', width));
        });
        (receipt.payments || []).forEach(function (row) {
            add(padLine(row.method || 'Payment', row.amount || '', width));
            if (row.change) {
                add(padLine('Change', row.change, width));
            }
        });
        if (receipt.thanks) {
            lines.push(' ');
            add(receipt.thanks);
        }
        return lines;
    }

    function sendLines(client, lines, mm) {
        var lineApi = client.printer.lineApi;
        var base = client.class.BaseStyle.getStyle().setWidth(paperDots(mm));
        var left = client.class.TextStyle.getStyle().setAlign(client.ENUM.Align.LEFT);
        var center = client.class.TextStyle.getStyle().setAlign(client.ENUM.Align.CENTER).setEnableBold(true);
        return lineApi.enableTransMode(true).then(function () {
            return lineApi.initLine(base);
        }).then(function () {
            var chain = Promise.resolve();
            lines.forEach(function (text, index) {
                chain = chain.then(function () {
                    var style = index === 0 ? center : left;
                    return lineApi.addText(text, style);
                });
            });
            return chain;
        }).then(function () {
            return lineApi.printDividingLine(client.ENUM.DividingLine.DOTTED, 0);
        }).then(function () {
            return lineApi.autoOut();
        }).then(function () {
            return lineApi.printTrans();
        });
    }

    function run(mm, lines, hooks) {
        if (busy) {
            return Promise.resolve({ skipped: true });
        }
        busy = true;
        if (hooks.onBusy) {
            hooks.onBusy(true);
        }
        if (hooks.onStatus) {
            hooks.onStatus('Connecting to the SUNMI print service…', 'info');
        }
        return connect().then(function (client) {
            return client.printer.queryApi.getStatus().then(function (status) {
                if (hooks.onStatus) {
                    hooks.onStatus('Printer status: ' + textOf(status), 'info');
                }
                return sendLines(client, lines, mm).then(function (result) {
                    var message = outcome(result);
                    if (hooks.onStatus) {
                        hooks.onStatus(message, saysPrinted(result) ? 'ok' : 'info');
                    }
                    return { ok: true, message: message, reprint: !saysPrinted(result) };
                });
            });
        }).catch(function (error) {
            var message = failText(error);
            if (/Socket not connected|did not load/i.test(message)) {
                ready = null;
                sdk = null;
            }
            if (hooks.onStatus) {
                hooks.onStatus(message, 'error');
            }
            return { ok: false, message: message, reprint: true };
        }).then(function (result) {
            busy = false;
            if (hooks.onBusy) {
                hooks.onBusy(false);
            }
            return result;
        });
    }

    window.SkylineSunmi = {
        paperDots: paperDots,
        printReceipt: function (receipt, hooks) {
            hooks = hooks || {};
            var mm = Number(receipt && receipt.paperMm) === 58 ? 58 : 80;
            return run(mm, receiptLines(receipt || {}, mm), hooks);
        },
        printTest: function (options, hooks) {
            hooks = hooks || {};
            options = options || {};
            var mm = Number(options.paperMm) === 58 ? 58 : 80;
            var lines = [
                options.hotel || 'Skyline Hotel',
                'SUNMI printer test',
                'Paper ' + mm + ' mm',
                'The web print service accepted this slip.'
            ];
            return run(mm, lines, hooks);
        }
    };
})(window);
