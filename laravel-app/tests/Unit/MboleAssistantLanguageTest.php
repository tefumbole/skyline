<?php

namespace Tests\Unit;

use App\Services\Assistant\CustomerLanguage;
use App\Services\WhatsApp\WaSenderEventParser;
use App\Services\WhatsApp\WhatsAppAudioTranscriber;
use Tests\TestCase;

class MboleAssistantLanguageTest extends TestCase
{
    public function test_language_follows_the_latest_substantive_message()
    {
        $language = new CustomerLanguage();

        $english = $language->resolve('What is the price of a table?', null);
        $french = $language->resolve('Quel est le prix de la table ?', $english);
        $arabic = $language->resolve('كم سعر الطاولة؟', $french);
        $neutral = $language->resolve('OK', $arabic);
        $emoji = $language->resolve('👍', $arabic);
        $mixed = $language->resolve('Combien coûte une table et quelle boisson, and what is the payment method?', 'en');
        $unknown = $language->resolve('Wie viel kostet ein Tisch?', 'en');

        $this->assertSame('en', $english);
        $this->assertSame('fr', $french);
        $this->assertSame('ar', $arabic);
        $this->assertSame('ar', $neutral);
        $this->assertSame('ar', $emoji);
        $this->assertSame('fr', $mixed);
        $this->assertNull($unknown);
        $this->assertFalse($language->isNeutral('Wie viel kostet ein Tisch?'));
    }

    public function test_voice_note_is_parsed_and_secrets_are_removed_before_the_assistant()
    {
        $parsed = (new WaSenderEventParser())->parse([
            'event' => 'messages.received',
            'data' => [
                'messages' => [
                    'key' => ['id' => 'MSG1', 'remoteJid' => '237600000000@s.whatsapp.net', 'fromMe' => false],
                    'message' => [
                        'audioMessage' => [
                            'url' => 'https://mmg.whatsapp.net/encrypted-audio',
                            'mimetype' => 'audio/ogg; codecs=opus',
                            'mediaKey' => 'secret-media-key',
                            'fileSha256' => 'sha',
                            'fileEncSha256' => 'enc',
                            'fileLength' => '12000',
                            'seconds' => 8,
                            'ptt' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame('AUDIO', $parsed['message_type']);
        $this->assertSame('secret-media-key', $parsed['media']['media_key']);
        $this->assertTrue($parsed['media']['ptt']);

        $transcriber = new WhatsAppAudioTranscriber();
        $this->assertTrue($transcriber->acceptedMime('audio/ogg; codecs=opus'));
        $this->assertFalse($transcriber->acceptedMime('application/pdf'));
        $this->assertFalse($transcriber->withinSize(9000000));
        $safe = $transcriber->forAssistant($parsed['media']);
        $this->assertArrayNotHasKey('media_key', $safe);
        $this->assertArrayNotHasKey('url', $safe);
        $this->assertArrayNotHasKey('file_sha256', $safe);
    }
}
