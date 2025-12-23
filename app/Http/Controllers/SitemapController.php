<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;

class SitemapController extends Controller
{
    private $baseUrl;
    private $visited = [];
    private $urls = [];
    private $maxDepth;
    private $maxCount;
    private $crawlId;

    public function generate(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $this->crawlId = $request->input('crawl_id', uniqid());
        $this->baseUrl = $this->normalizeUrl($request->input('url'));
        $this->maxDepth = 3;
        $this->maxCount = 200;  // Increased for fuller crawls

        // Init session for this crawl
        Session::put("crawl_{$this->crawlId}", [
            'progress' => 0,
            'message' => 'Starting crawl...',
            'logs' => ["[{$this->crawlId}] Crawl started for: {$this->baseUrl}"],
            'urls' => []  // Store URLs for XML
        ]);

        Log::info("Crawl initiated: {$this->baseUrl} (ID: {$this->crawlId})");

        // Start crawling (now iterative for progress updates)
        $this->crawlIterative();

        // Get final data from session
        $sessionData = Session::get("crawl_{$this->crawlId}", []);
        $xml = $this->generateXml($sessionData['urls'] ?? []);

        // Update final progress
        Session::put("crawl_{$this->crawlId}.progress", 100);
        Session::put("crawl_{$this->crawlId}.message", 'Crawl complete! Found ' . count($sessionData['urls'] ?? []) . ' URLs.');

        Log::info("Crawl completed: {$this->baseUrl} (ID: {$this->crawlId}) - Found " . count($sessionData['urls'] ?? []) . " URLs");

        return response()->json(['xml' => $xml]);
    }

    private function crawlIterative()
    {
        $queue = [$this->baseUrl => 0];  // URL => depth
        $sessionKey = "crawl_{$this->crawlId}";

        while ($queue && count($this->urls) < $this->maxCount) {
            $currentUrl = key($queue);
            $depth = $queue[$currentUrl];
            unset($queue[$currentUrl]);

            if ($depth > $this->maxDepth || in_array($currentUrl, $this->visited)) {
                continue;
            }

            $this->visited[] = $currentUrl;
            $this->urls[] = $currentUrl;

            // Update session progress and logs
            $progress = (count($this->urls) / $this->maxCount) * 100;
            $logs = Session::get("{$sessionKey}.logs", []);
            $logs[] = "[{$this->crawlId}] Crawled: {$currentUrl} (Depth: {$depth}, Total URLs: " . count($this->urls) . ")";
            Session::put($sessionKey, [
                'progress' => min($progress, 99),  // Cap at 99 until done
                'message' => "Crawled: {$currentUrl} (Depth {$depth})",
                'logs' => $logs,
                'urls' => $this->urls
            ]);

            Log::debug("Crawled: {$currentUrl} (Depth: {$depth})");

            try {
                $response = Http::timeout(10)->get($currentUrl);

                if ($response->successful() && $response->header('Content-Type') && strpos($response->header('Content-Type'), 'text/html') !== false) {
                    $html = $this->fixHtml($response->body());

                    // Suppress libxml errors for malformed HTML
                    libxml_use_internal_errors(true);
                    $dom = new DOMDocument();
                    $dom->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
                    libxml_clear_errors();

                    $xpath = new DOMXPath($dom);

                    // Extract <a href> links
                    $links = $xpath->query('//a[@href]');
                    $internalCount = 0;
                    foreach ($links as $link) {
                        $href = $link->getAttribute('href');
                        $fullUrl = $this->normalizeUrl($href, $currentUrl);

                        $logs[] = "[{$this->crawlId}] Link found: {$href} -> {$fullUrl}";  // Extra log for debugging

                        if ($this->isInternal($fullUrl) && filter_var($fullUrl, FILTER_VALIDATE_URL) && !in_array($fullUrl, $this->visited) && !isset($queue[$fullUrl])) {
                            $queue[$fullUrl] = $depth + 1;  // Add to queue
                            $internalCount++;
                        }
                    }

                    // Also check for pagination <link rel="next/prev">
                    $nextLinks = $xpath->query('//link[@rel="next" or @rel="prev"]/@href');
                    foreach ($nextLinks as $nextLink) {
                        $href = $nextLink->nodeValue;
                        $fullUrl = $this->normalizeUrl($href, $currentUrl);
                        if ($this->isInternal($fullUrl) && filter_var($fullUrl, FILTER_VALIDATE_URL) && !in_array($fullUrl, $this->visited) && !isset($queue[$fullUrl])) {
                            $queue[$fullUrl] = $depth + 1;
                            $internalCount++;
                        }
                    }

                    $logs[] = "[{$this->crawlId}] From {$currentUrl}: Found {$links->length} links, {$internalCount} internal queued.";
                    Session::put("{$sessionKey}.logs", $logs);
                    Log::debug("From {$currentUrl}: {$links->length} links, {$internalCount} internal");
                } else {
                    $logs[] = "[{$this->crawlId}] Skipped {$currentUrl}: Not HTML or failed ({$response->status()})";
                    Session::put("{$sessionKey}.logs", $logs);
                    Log::warning("Skipped {$currentUrl}: " . ($response->failed() ? $response->status() : 'Non-HTML'));
                }
            } catch (\Exception $e) {
                $logs[] = "[{$this->crawlId}] Error crawling {$currentUrl}: " . $e->getMessage();
                Session::put("{$sessionKey}.logs", $logs);
                Log::warning("Crawl error for {$currentUrl}: " . $e->getMessage());
            }

            // Small delay to allow polling and rate-limit respect
            usleep(200000);  // 0.2s delay
        }

        if (empty($queue)) {
            $logs[] = "[{$this->crawlId}] Crawl finished: No more links to process.";
            Session::put("{$sessionKey}.logs", $logs);
        }
    }

    // ... (keep existing: progress, clear, generateXml, normalizeUrl, isInternal, fixHtml)
    // generateXml unchanged
    private function generateXml($urls)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url) . '</loc>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function normalizeUrl($url, $base = null)
    {
        $base = $base ?? $this->baseUrl;
        if (strpos($url, 'http') !== 0) {
            $parsedBase = parse_url($base);
            $basePath = isset($parsedBase['path']) ? rtrim(dirname($parsedBase['path']), '/') . '/' : '/';
            $url = rtrim($parsedBase['scheme'] . '://' . $parsedBase['host'], '/') . $basePath . ltrim($url, '/');
        }
        return preg_replace('/\/+/', '/', $url);
    }

    private function isInternal($url)
    {
        $parsedBase = parse_url($this->baseUrl);
        $parsedUrl = parse_url($url);
        $hostMatch = ($parsedBase['host'] ?? '') === ($parsedUrl['host'] ?? '');
        $schemeMatch = ($parsedBase['scheme'] ?? '') === ($parsedUrl['scheme'] ?? '');
        return $hostMatch && $schemeMatch;
    }

    private function fixHtml($html)
    {
        // Remove script/style to avoid parsing issues
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Wrap if not full HTML
        if (strpos($html, '<html') === false && strpos($html, '<body') === false) {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
        }
        return $html;
    }
}
