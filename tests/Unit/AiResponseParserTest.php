<?php

namespace Tests\Unit;

use App\Services\EditorialScheduling\AiResponseParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiResponseParserTest extends TestCase
{
    #[Test]
    public function it_parses_complete_english_structured_response(): void
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
    public function it_parses_concrete_sample_style_with_four_news_items(): void
    {
        $text = "TITLE:\nBoletín\n\nINTRO:\nArrancamos.\n\nNEWS ITEMS:\n1. HEADLINE:\nUno\nSUMMARY:\nS1\nSCRIPT:\nTexto de guion 1\nEDITORIAL ANGLE:\nÁngulo 1\nSOURCE HINTS:\n- https://one.test\n\n2. HEADLINE:\nDos\nSUMMARY:\nS2\nSCRIPT:\nTexto de guion 2\nEDITORIAL ANGLE:\nÁngulo 2\nSOURCE HINTS:\n- EFE\n\nHEADLINE:\nTres\nSUMMARY:\nS3\nSCRIPT:\nTexto de guion 3\nEDITORIAL ANGLE:\nÁngulo 3\nSOURCE HINTS:\n- requiere verificación\n\nHEADLINE:\nCuatro\nSUMMARY:\nS4\nSCRIPT:\nTexto de guion 4\nEDITORIAL ANGLE:\nÁngulo 4\nSOURCE HINTS:\n- https://four.test\n\nOUTRO:\nCierre\n\nNOTES:\nNota final";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(4, $parsed['items']);
        $this->assertSame('Texto de guion 1', $parsed['items'][0]['script']);
        $this->assertSame('Texto de guion 4', $parsed['items'][3]['script']);
        $this->assertSame('Boletín', $parsed['title']);
        $this->assertSame('Arrancamos.', $parsed['intro']);
        $this->assertSame('Cierre', $parsed['outro']);
        $this->assertSame('Nota final', $parsed['notes']);
    }

    #[Test]
    public function it_parses_spanish_labels(): void
    {
        $text = "TÍTULO:\nResumen de la tarde\n\nINTRODUCCIÓN:\nEstas son las noticias clave.\n\nNOTICIAS:\n1. TITULAR:\nSube el empleo\n\nRESUMEN:\nMejora del mercado laboral.\n\nGUION:\nGuion para presentador.\n\nENFOQUE EDITORIAL:\nImpacto social inmediato.\n\nFUENTES:\n- El País\n\nCIERRE:\nHasta aquí el boletín.\n\nNOTAS:\nPendiente confirmar cifra regional.";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertSame('Resumen de la tarde', $parsed['title']);
        $this->assertSame('Estas son las noticias clave.', $parsed['intro']);
        $this->assertSame('Hasta aquí el boletín.', $parsed['outro']);
        $this->assertSame('Sube el empleo', $parsed['items'][0]['headline']);
        $this->assertSame('Guion para presentador.', $parsed['items'][0]['script']);
    }

    #[Test]
    public function it_parses_markdown_bolded_headings_and_numbered_items(): void
    {
        $text = "**TITLE:** Noticias de la Mañana en España\n\n**INTRO:** Buenos días...\n\n**NEWS ITEMS:**\n1. **HEADLINE:** Economía Española\n**SUMMARY:** Resumen 1\n**SCRIPT:** Narración 1\n**EDITORIAL ANGLE:** Ángulo 1\n**SOURCE HINTS:**\n- https://one.test\n\n2. **HEADLINE:** Medio Ambiente\n**SUMMARY:** Resumen 2\n**SCRIPT:** Narración 2\n**EDITORIAL ANGLE:** Ángulo 2\n**SOURCE HINTS:**\n- EFE\n\n3. **HEADLINE:** Política\n**SUMMARY:** Resumen 3\n**SCRIPT:** Narración 3\n**EDITORIAL ANGLE:** Ángulo 3\n**SOURCE HINTS:**\n- https://three.test\n\n4. **HEADLINE:** Cultura\n**SUMMARY:** Resumen 4\n**SCRIPT:** Narración 4\n**EDITORIAL ANGLE:** Ángulo 4\n**SOURCE HINTS:**\n- RTVE\n\n**OUTRO:** Hasta luego\n\n**NOTES:** Nota final";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertSame('Noticias de la Mañana en España', $parsed['title']);
        $this->assertSame('Buenos días...', $parsed['intro']);
        $this->assertCount(4, $parsed['items']);
        $this->assertSame('Economía Española', $parsed['items'][0]['headline']);
        $this->assertSame('Narración 1', $parsed['items'][0]['script']);
        $this->assertSame(['https://one.test'], $parsed['items'][0]['source_hints']);
        $this->assertSame('Hasta luego', $parsed['outro']);
        $this->assertSame('Nota final', $parsed['notes']);
        $this->assertNotContains('unstructured_response', $parsed['warnings']);
    }

    #[Test]
    public function it_handles_unstructured_response_without_exceptions(): void
    {
        $text = 'Completely unstructured response from model.';
        $parsed = (new AiResponseParser())->parse($text);

        $this->assertNull($parsed['title']);
        $this->assertNull($parsed['intro']);
        $this->assertNull($parsed['outro']);
        $this->assertSame([], $parsed['items']);
        $this->assertSame($text, $parsed['raw']);
        $this->assertContains('unstructured_response', $parsed['warnings']);
    }

    #[Test]
    public function it_warns_when_script_blocks_are_missing(): void
    {
        $text = "TITLE:\nBrief\n\nINTRO:\nI\n\nNEWS ITEMS:\nHEADLINE:\nOnly\nSUMMARY:\nS\nEDITORIAL ANGLE:\nAngle\nSOURCE HINTS:\n- Reuters\n\nOUTRO:\nO";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(1, $parsed['items']);
        $this->assertContains('no_script_blocks', $parsed['warnings']);
    }

    #[Test]
    public function it_warns_when_source_hints_are_missing(): void
    {
        $text = "TITLE:\nBrief\n\nINTRO:\nI\n\nNEWS ITEMS:\nHEADLINE:\nOnly\nSUMMARY:\nS\nSCRIPT:\nMain narration\n\nOUTRO:\nO";

        $parsed = (new AiResponseParser())->parse($text);

        $this->assertCount(1, $parsed['items']);
        $this->assertSame([], $parsed['items'][0]['source_hints']);
        $this->assertContains('missing_sources', $parsed['warnings']);
    }
}
