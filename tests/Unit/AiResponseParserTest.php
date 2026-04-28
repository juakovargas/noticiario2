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
        $this->assertSame('Presenter script line.', $parsed['items'][0]['script']);
        $this->assertSame(['Reuters', 'https://example.com/source'], $parsed['items'][0]['source_hints']);
        $this->assertNotContains('missing_sources', $parsed['warnings']);
    }

    #[Test]
    public function it_parses_concrete_sample_with_four_news_items(): void
    {
        $text = "TITLE:\nBoletín\n\nINTRO:\nArrancamos.\n\nNEWS ITEMS:\n1. HEADLINE:\nUno\nSUMMARY:\nS1\nSCRIPT:\nTexto de guion 1\nSOURCE HINTS:\n- https://one.test\n\n2. HEADLINE:\nDos\nSUMMARY:\nS2\nSCRIPT:\nTexto de guion 2\nSOURCE HINTS:\n- EFE\n\nHEADLINE:\nTres\nSUMMARY:\nS3\nSCRIPT:\nTexto de guion 3\nSOURCE HINTS:\n- requiere verificación\n\nHEADLINE:\nCuatro\nSUMMARY:\nS4\nSCRIPT:\nTexto de guion 4\n\nOUTRO:\nCierre\n\nNOTES:\nNota final";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(4, $parsed['items']);
        $this->assertSame('Texto de guion 1', $parsed['items'][0]['script']);
        $this->assertSame('Texto de guion 4', $parsed['items'][3]['script']);
        $this->assertContains('missing_sources', $parsed['warnings']);
    }

    #[Test]
    public function it_parses_spanish_labels(): void
    {
        $text = "TITULO:\nResumen de la tarde\n\nINTRODUCCION:\nEstas son las noticias clave.\n\nNOTICIAS:\n1. TITULAR:\nSube el empleo\n\nRESUMEN:\nMejora del mercado laboral.\n\nGUIÓN:\nGuion para presentador.\n\nENFOQUE EDITORIAL:\nImpacto social inmediato.\n\nPISTAS DE FUENTES:\n- El País\n\nCIERRE:\nHasta aquí el boletín.\n\nNOTAS:\nPendiente confirmar cifra regional.";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertSame('Resumen de la tarde', $parsed['title']);
        $this->assertSame('Estas son las noticias clave.', $parsed['intro']);
        $this->assertSame('Hasta aquí el boletín.', $parsed['outro']);
        $this->assertSame('Sube el empleo', $parsed['items'][0]['headline']);
        $this->assertSame('Guion para presentador.', $parsed['items'][0]['script']);
    }

    #[Test]
    public function it_handles_missing_source_hints(): void
    {
        $text = "TITLE:\nBrief\n\nINTRO:\nI\n\nNEWS ITEMS:\nHEADLINE:\nOnly\nSUMMARY:\nS\nSCRIPT:\nMain narration\n\nOUTRO:\nO";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(1, $parsed['items']);
        $this->assertSame([], $parsed['items'][0]['source_hints']);
        $this->assertContains('missing_sources', $parsed['warnings']);
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
        $this->assertContains('unstructured_response', $parsed['warnings']);
    }
}
