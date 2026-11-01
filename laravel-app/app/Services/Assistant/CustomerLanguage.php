<?php

namespace App\Services\Assistant;

class CustomerLanguage
{
    public function resolve($text, $previous = null)
    {
        $text = trim((string) $text);
        $previous = $previous !== null && $previous !== '' ? (string) $previous : null;
        if ($text === '' || $this->isNeutral($text)) {
            return $previous;
        }

        return $this->detect($text);
    }

    public function isNeutral($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return true;
        }
        if (preg_match('/^(ok|okay|k|yes|no|oui|non|d\'accord|merci|thanks|thank you)$/iu', $text)) {
            return true;
        }

        return preg_replace('/[^\p{L}]+/u', '', $text) === '';
    }

    public function detect($text)
    {
        $text = (string) $text;
        if (preg_match('/\p{Arabic}/u', $text)) {
            return 'ar';
        }
        if (preg_match('/\p{Han}/u', $text)) {
            return 'zh';
        }
        $french = $this->hits($text, ['bonjour', 'bonsoir', 'merci', 'prix', 'boisson', 'paiement', 'réservation', 'reservation', 'voudrais', 'combien', 'salut', 'table', 'vous', "s'il", 'quelle', 'quel']);
        $english = $this->hits($text, ['hello', 'please', 'price', 'drink', 'payment', 'book', 'thanks', 'table', 'what', 'how']);
        if ($french === 0 && $english === 0) {
            return null;
        }
        if ($french === $english) {
            return null;
        }

        return $french > $english ? 'fr' : 'en';
    }

    protected function hits($text, array $needles)
    {
        $hay = mb_strtolower($text);
        $score = 0;
        foreach ($needles as $needle) {
            $pattern = '/(?<![\p{L}])'.preg_quote($needle, '/').'(?![\p{L}])/u';
            if (preg_match($pattern, $hay)) {
                $score++;
            }
        }

        return $score;
    }
}
