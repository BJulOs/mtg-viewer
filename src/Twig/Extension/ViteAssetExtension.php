<?php

namespace App\Twig\Extension;

use App\Twig\Runtime\ViteAssetExtensionRuntime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ViteAssetExtension extends AbstractExtension
{
    public function __construct(
        private readonly bool $isDev,
        private readonly string $manifestPath,
        private array $manifestData = []
    ) {
    }

    private function readManifest(): array
    {
        if (empty($this->manifestData)) {
            $this->manifestData = json_decode(file_get_contents($this->manifestPath), true);
        }
        return $this->manifestData;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_script_tags', [$this, 'renderViteScriptTags'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_link_tags', [$this, 'renderViteLinkTags'], ['is_safe' => ['html']]),
        ];
    }

    /* Generate script tags */
    private function generateViteScriptTagsDev(string $entrypoint): string
    {
        return "
            <script type=\"module\" src=\"http://localhost:3000/build/@vite/client\"></script>
            <script type=\"module\" src=\"http://localhost:3000/build/$entrypoint\"></script>
        ";
    }

    private function generateViteScriptTagsProd(string $entrypoint): string
    {
        $asset = $this->readManifest()[$entrypoint]['file'];

        return "
            <script type=\"module\" src=\"build/$asset\"></script>
        ";
    }

    public function renderViteScriptTags(string $entrypoint): string
    {
        return $this->isDev ? $this->generateViteScriptTagsDev($entrypoint) : $this->generateViteScriptTagsProd($entrypoint);
    }

    private function generateViteLinkTagsProd(string $entrypoint): string
    {
        $scripts = $this->readManifest()[$entrypoint]['file'];

        return "<link rel=\"stylesheet\" href=\"/build/$scripts\">";
    }

    public function renderViteLinkTags(string $entrypoint): string
    {
        return $this->isDev ? '' : $this->generateViteLinkTagsProd($entrypoint);
    }
}
