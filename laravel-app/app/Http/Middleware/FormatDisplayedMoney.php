<?php

namespace App\Http\Middleware;

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
        if (! is_string($content) || strpos($content, '.00') === false) {
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
            $parts[$index] = preg_replace_callback('/>([^<]*)</', function ($match) {
                return '>'.$this->formatText($match[1]).'<';
            }, $part);
        }

        $response->setContent(implode('', $parts));

        return $response;
    }

    protected function formatText($text)
    {
        if (! is_string($text) || strpos($text, '.00') === false || preg_match('/hour|hrs/i', $text)) {
            return $text;
        }

        $formatted = preg_replace_callback('/(?<![\d.,])(-?\d{1,3}(?:,\d{3})*|-?\d+)\.00(?![\d%])/', function ($match) {
            $amount = (int) round((float) str_replace(',', '', $match[1]));

            return number_format($amount, 0, '.', ',');
        }, $text);

        return $formatted === null ? $text : $formatted;
    }
}
