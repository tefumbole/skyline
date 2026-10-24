<?php

namespace App\Http\Middleware;

use App\Support\Money;

class FormatDisplayedMoney
{
    public function handle($request, $next)
    {
        $response = $next($request);
        if (! method_exists($response, 'getContent') || ! method_exists($response, 'headers')) {
            return $response;
        }

        $type = (string) $response->headers->get('Content-Type', '');
        if ($type !== '' && stripos($type, 'text/html') === false) {
            return $response;
        }

        $path = $request->path();
        if (preg_match('/timesheet|internship|hour/i', $path)) {
            return $response;
        }

        $content = $response->getContent();
        if (! is_string($content) || $content === '' || (strpos($content, '.00') === false && ! preg_match('/>\s*-?\d{4,}\s*</', $content))) {
            return $response;
        }

        $parts = preg_split('/(<(?:script|style|textarea)\b[^>]*>.*?<\/(?:script|style|textarea)>)/is', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return $response;
        }

        foreach ($parts as $index => $part) {
            if ($index % 2 === 1) {
                continue;
            }
            $parts[$index] = preg_replace_callback('/<([a-zA-Z0-9:-]+)(\s[^>]*)?>([^<]*)/', function ($match) {
                $hint = $match[1].' '.($match[2] ?? '');

                return '<'.$match[1].($match[2] ?? '').'>'.$this->formatText($match[3], $hint);
            }, $part);
        }

        $response->setContent(implode('', $parts));

        return $response;
    }

    protected function formatText($text, $hint = '')
    {
        if (! is_string($text) || $text === '' || preg_match('/hour|hrs/i', $text)) {
            return $text;
        }

        $formatted = $text;
        if (strpos($text, '.00') !== false) {
            $replaced = preg_replace_callback('/(?<![\d.,])(-?\d{1,3}(?:,\d{3})*|-?\d+)\.00(?![\d%])/', function ($match) {
                return Money::format($match[1]);
            }, $text);
            $formatted = $replaced === null ? $text : $replaced;
        }

        $trimmed = trim($formatted);
        if (preg_match('/^-?\d{4,}$/', $trimmed) && $this->looksLikeMoney($hint)) {
            $amount = (int) $trimmed;
            if ($amount < 1900 || $amount > 2099) {
                $formatted = str_replace($trimmed, Money::format($amount), $formatted);
            }
        }

        return $formatted;
    }

    protected function looksLikeMoney($hint)
    {
        if (preg_match('/\b(id|code|phone|qty|quantity|reference|rate|hour|date)\b/i', $hint)) {
            return false;
        }

        return (bool) preg_match('/price|total|amount|paid|balance|cost|grand|subtotal|owing|deposit|discount|salary|fee|revenue|expense/i', $hint);
    }
}
