<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MetaController extends Controller
{
    public function index()
    {
        return view('meta-generate');
    }

    public function generate(Request $request)
    {
        Log::info('Generate Meta Request:', $request->all());

        // ==========================
        // 1. AI PROMPT (Schema Type Included)
        // ==========================
        $prompt = "
You are an SEO expert.

Generate SEO meta data and the most appropriate Schema.org type.

Topic: {$request->topic}
Target Keywords: {$request->keywords}
Content Type: {$request->content_type}
Tone: {$request->tone}

Rules:
- Meta title must be 50–60 characters
- Meta description must be 150–160 characters
- Keywords must be comma separated
- Schema type must be ONE of:
  Article, BlogPosting, NewsArticle, SoftwareApplication
- Do NOT include markdown
- Do NOT include explanation

Return ONLY valid JSON in this exact format:

{
  \"meta_title\": \"\",
  \"meta_description\": \"\",
  \"meta_keywords\": \"\",
  \"schema_type\": \"\"
}
";

        // ==========================
        // 2. API CALL
        // ==========================
        $response = Http::withToken(env('MISTRAL_API_KEY'))
            ->post('https://api.mistral.ai/v1/chat/completions', [
                'model' => 'mistral-medium',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3
            ]);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // ==========================
        // 3. CLEAN & DECODE JSON
        // ==========================
        $cleanJson = preg_replace('/```json|```/i', '', $content);
        $cleanJson = trim($cleanJson);

        $json = json_decode($cleanJson, true);

        if (json_last_error() !== JSON_ERROR_NONE || !$json) {
            Log::error('JSON Decode Error: ' . json_last_error_msg());
            return response()->json([
                'error' => 'Invalid AI response',
                'raw'   => $content
            ], 422);
        }

        // ==========================
        // 4. SLUG & URL
        // ==========================
        $slug       = Str::slug($json['meta_title']);
        $articleUrl = url('/blog/' . $slug);
        $authorUrl  = url('/author/editorial-team');

        // ==========================
        // 5. SAFE SCHEMA TYPE (AI + FALLBACK)
        // ==========================
        $allowedTypes = [
            'Article',
            'BlogPosting',
            'NewsArticle',
            'SoftwareApplication'
        ];

        $schemaType = in_array($json['schema_type'], $allowedTypes)
            ? $json['schema_type']
            : 'Article';

        // ==========================
        // 6. SEO FRIENDLY SCHEMA
        // ==========================
        $schema = [
            "@context" => "https://schema.org",
            "@type"    => $schemaType,

            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id"   => $articleUrl
            ],

            "headline"    => $json['meta_title'],
            "description" => $json['meta_description'],
            "keywords"    => $json['meta_keywords'],

            "author" => [
                "@type" => "Person",
                "name"  => "Codetap Editorial Team",
                "url"   => $authorUrl
            ],

            "publisher" => [
                "@type" => "Organization",
                "name"  => "Codetap",
                "url"   => url('/'),
                "logo"  => [
                    "@type" => "ImageObject",
                    "url"   => asset('logo.png')
                ]
            ],

            "datePublished" => now()->toIso8601String(),
            "dateModified"  => now()->toIso8601String()
        ];

        // ==========================
        // 7. FINAL RESPONSE
        // ==========================
        return response()->json([
            'title'       => $json['meta_title'],
            'description' => $json['meta_description'],
            'keywords'    => $json['meta_keywords'],
            'schema_type' => $schemaType,
            'slug'        => $slug,
            'url'         => $articleUrl,
            'schema'      => json_encode(
                $schema,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            )
        ]);
    }
}
