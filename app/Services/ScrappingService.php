<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use HeadlessChromium\Browser;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Page;
use Illuminate\Support\Str;

class ScrappingService
{
    private Browser $browser;

    public function __construct()
    {
        $this->browser = (new BrowserFactory())->createBrowser([
            'headless' => true,
        ]);
    }

    public function __destruct()
    {
        $this->browser->close();
    }

    public function fetchRecursive(string $url, int $maxExecutionTime = 100, int $maxStackLength = 100): array
    {
        $linkStack = $processedLinks = [$url];
        $data = [];

        try {
            $start = time();

            while (!empty($linkStack)) {
                if (time() - $start > $maxExecutionTime) {
                    break;
                }

                $currentLink = array_shift($linkStack);
                $linkData = $this->fetch($currentLink);
                $data[] = [
                    'url'       => $currentLink,
                    'content'   => $linkData['content'],
                ];

                $linksToAdd = array_diff($linkData['urls'], $processedLinks);

                if (count($linksToAdd) > 0 && count($linkStack) < $maxStackLength) {
                    $linkStack = array_merge($linkStack, $linksToAdd);
                    $processedLinks = array_merge($processedLinks, $linksToAdd);
                }
            }

            return $data;
        } catch (Exception $e) {
            return $data;
        }
    }

    public function fetch(string $url, bool $fetchLinks = true): array|string
    {
        $html    = $this->getHtml($url);
        $content = $this->getCleanText($html);

        if (!$fetchLinks) {
            return $content;
        }

        $links = $this->getFilteredLinks($html, $url);

        return [
            'content' => $content,
            'urls'    => $links
        ];
    }

    private function getHtml(string $url): string
    {
        $page = $this->browser->createPage();
        $navigation = $page->navigate($url);
        $navigation->waitForNavigation(Page::DOM_CONTENT_LOADED);

        return $page->evaluate('document.body.innerHTML')->getReturnValue();
    }

    private function getFilteredLinks(string $html, string $currentUrl): array
    {
        $pattern = '/<a\s+(?:[^>]*?\s+)?href="([^"]*)"/';
        preg_match_all($pattern, $html, $matches);

        $links = $matches[1] ?? [];
        $filteredLinks = [];

        foreach ($links as $link) {
            $filteredLink = $this->sanitizeLink($link, $currentUrl);
            if ($filteredLink !== null && !in_array($filteredLink, $filteredLinks)) {
                $filteredLinks[] = $filteredLink;
            }
        }

        return $filteredLinks;
    }

    private function sanitizeLink(string $link, string $currentUrl): ?string
    {
        $parsedLink = parse_url($link);
        $linkScheme = $parsedLink["scheme"] ?? "";
        $linkHost = $parsedLink["host"] ?? "";
        $linkPath = $parsedLink["path"] ?? "";

        $linkHost = Str::startsWith($linkHost, "www.")
            ? Str::after($linkHost, "www.")
            : $linkHost;

        $currentUrlParsed = parse_url($currentUrl);
        $currentUrlScheme = $currentUrlParsed["scheme"];
        $currentUrlHost = $currentUrlParsed["host"];

        $currentUrlHost = Str::startsWith($currentUrlHost, "www.")
            ? Str::after($currentUrlHost, "www.")
            : $currentUrlHost;

        if (($linkScheme === "http" || $linkScheme === "https") && $linkHost === $currentUrlHost) {
            $filteredLink = $linkScheme . "://" . $linkHost . $linkPath;
            return rtrim($filteredLink, "/");
        } elseif (Str::startsWith($link, "/") && !Str::startsWith($link, "/#")) {
            $newLink = $currentUrlScheme . "://" . $currentUrlHost . $link;
            return rtrim($newLink, "/");
        }

        return null;
    }

    private function getCleanText(string $content): string
    {
        $content = preg_replace('/<header\b[^>]*>(.*?)<\/header>/is', "", $content);
        $content = preg_replace('/<footer\b[^>]*>(.*?)<\/footer>/is', "", $content);
        $content = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $content);
        $content = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $content);
        $content = strip_tags($content);

        $content = html_entity_decode($content);

        return preg_replace('/\s+/', ' ', $content);
    }
}
