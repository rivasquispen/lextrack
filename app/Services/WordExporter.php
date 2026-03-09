<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class WordExporter
{
    public function saveFromHtml(string $html, string $path): void
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $phpWord->getSettings()->setTrackRevisions(true);

        $section = $phpWord->addSection([
            'marginTop' => 1440,
            'marginRight' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
        ]);

        $cleanHtml = $this->sanitizeHtml($html);

        try {
            Html::addHtml($section, '<div>'.$cleanHtml.'</div>', false, true);
        } catch (\Throwable $exception) {
            $section->addText(strip_tags($html));
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'lextrack-docx');
        if ($tempPath === false) {
            throw new \RuntimeException('No se pudo crear un archivo temporal para generar el Word.');
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        $contents = file_get_contents($tempPath);
        @unlink($tempPath);

        if ($contents === false) {
            throw new \RuntimeException('No se pudo generar el archivo Word.');
        }

        Storage::put($path, $contents);
    }

    private function sanitizeHtml(string $html): string
    {
        $wrapped = '<!DOCTYPE html><html><body>'.$html.'</body></html>';
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        if ($loaded) {
            $this->pruneDisallowedNodes($dom, ['script', 'style', 'link', 'meta', 'colgroup', 'col', 'head']);
            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                $content = '';
                foreach ($body->childNodes as $child) {
                    $content .= $dom->saveHTML($child);
                }
                return $content ?: nl2br(htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            }
        }

        return nl2br(htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    private function pruneDisallowedNodes(\DOMDocument $dom, array $tags): void
    {
        foreach ($tags as $tag) {
            $nodes = $dom->getElementsByTagName($tag);
            while ($nodes->length > 0) {
                $node = $nodes->item(0);
                if ($node && $node->parentNode) {
                    $node->parentNode->removeChild($node);
                } else {
                    break;
                }
            }
        }
    }
}
