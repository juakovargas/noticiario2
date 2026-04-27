<?php

namespace Tests\Unit;

use App\Services\EditorialScheduling\AiResponseParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiResponseParserTest extends TestCase
{
    #[Test]
    public function it_parses_structured_response_format(): void
    {
        $text = "TITLE:\nMorning Brief\n\nINTRO:\nTop headlines now.\n\nNEWS ITEMS:\n1. HEADLINE:\nEnergy prices cool\n\nSUMMARY:\nRates eased in major markets.\n\nSCRIPT:\nPresenter script line.\n\nEDITORIAL ANGLE:\nImpacts household budgets.\n\nSOURCE HINTS:\n- Reuters\n- https://example.com/source\n\nOUTRO:\nThat is all for now.\n\nNOTES:\nVerify last data point.";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertSame('Morning Brief', $parsed['title']);
        $this->assertSame('Top headlines now.', $parsed['intro']);
        $this->assertSame('That is all for now.', $parsed['outro']);
        $this->assertSame('Verify last data point.', $parsed['notes']);
        $this->assertCount(1, $parsed['items']);
        $this->assertSame('Energy prices cool', $parsed['items'][0]['headline']);
        $this->assertSame(['Reuters', 'https://example.com/source'], $parsed['items'][0]['source_hints']);
    }

    #[Test]
    public function it_parses_spanish_labels(): void
    {
        $text = "TÍTULO:\nResumen de la tarde\n\nENTRADILLA:\nEstas son las noticias clave.\n\nNOTICIAS:\n1. TITULAR:\nSube el empleo\n\nRESUMEN:\nMejora del mercado laboral.\n\nGUION:\nGuion para presentador.\n\nENFOQUE EDITORIAL:\nImpacto social inmediato.\n\nFUENTES:\n- El País\n\nCIERRE:\nHasta aquí el boletín.\n\nNOTAS:\nPendiente confirmar cifra regional.";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertSame('Resumen de la tarde', $parsed['title']);
        $this->assertSame('Estas son las noticias clave.', $parsed['intro']);
        $this->assertSame('Hasta aquí el boletín.', $parsed['outro']);
        $this->assertSame('Sube el empleo', $parsed['items'][0]['headline']);
        $this->assertSame('Guion para presentador.', $parsed['items'][0]['script']);
    }

    #[Test]
    public function it_handles_missing_sections_gracefully(): void
    {
        $parsed = (new AiResponseParser())->parse("TITLE:\nQuick title");

        $this->assertSame('Quick title', $parsed['title']);
        $this->assertNull($parsed['intro']);
        $this->assertNull($parsed['outro']);
        $this->assertSame([], $parsed['items']);
    }

    #[Test]
    public function it_returns_full_response_as_body_when_unstructured(): void
    {
        $text = 'Completely unstructured response from model.';
        $parsed = (new AiResponseParser())->parse($text);

        $this->assertNull($parsed['title']);
        $this->assertNull($parsed['intro']);
        $this->assertNull($parsed['outro']);
        $this->assertSame($text, $parsed['body']);
        $this->assertSame([], $parsed['items']);
    }

    #[Test]
    public function it_extracts_multiple_news_items(): void
    {
        $text = "TITLE:\nBulletin\n\nNEWS ITEMS:\n1. HEADLINE:\nFirst\n\nSUMMARY:\nOne\n\nSCRIPT:\nScript one\n\nSOURCE HINTS:\n- https://a.test\n\n2. HEADLINE:\nSecond\n\nSUMMARY:\nTwo\n\nSCRIPT:\nScript two\n\nSOURCE HINTS:\n- BBC\n\nOUTRO:\nEnd";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(2, $parsed['items']);
        $this->assertSame('First', $parsed['items'][0]['headline']);
        $this->assertSame('Second', $parsed['items'][1]['headline']);
        $this->assertSame(['https://a.test'], $parsed['items'][0]['source_hints']);
        $this->assertSame(['BBC'], $parsed['items'][1]['source_hints']);
    }
}
