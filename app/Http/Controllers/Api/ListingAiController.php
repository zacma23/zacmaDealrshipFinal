<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AI\GeminiProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ListingAiController extends Controller
{
    /**
     * AI-Assisted Vehicle Analysis
     *
     * Accepts a text description (and optional image URLs) about a vehicle
     * and returns structured suggestions. Never creates or modifies listings.
     * All suggestions are returned for the user to review and apply.
     */
    public function assistVehicle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'description' => 'required|string|max:2000',
            'image_urls'  => 'nullable|array|max:5',
            'image_urls.*' => 'nullable|url',
        ]);

        $description = $validated['description'];
        $imageUrls   = $validated['image_urls'] ?? [];

        $prompt = $this->buildVehicleAnalysisPrompt($description, $imageUrls);

        try {
            $provider = new GeminiProvider();
            $result = $provider->generateText($prompt, [
                'temperature'        => 0.3,
                'max_tokens'         => 800,
                'json_mode'          => true,
                'system_instruction' => 'You are a professional vehicle listing assistant for an Ethiopian car marketplace. Analyze vehicle details and return structured JSON. All responses must be valid JSON only.',
            ]);

            if ($result['success'] && !empty($result['text'])) {
                $suggestion = $this->parseAiSuggestion($result['text']);
                return response()->json([
                    'status' => 'success',
                    'data'   => $suggestion,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('AI vehicle assist error: ' . $e->getMessage());
        }

        // Fallback: extract basics from text
        return response()->json([
            'status' => 'success',
            'data'   => $this->fallbackExtract($description),
        ]);
    }

    private function buildVehicleAnalysisPrompt(string $description, array $imageUrls): string
    {
        $imageHint = count($imageUrls) > 0
            ? 'The user has also provided ' . count($imageUrls) . ' photo(s) of the vehicle. '
            : '';

        return <<<PROMPT
{$imageHint}Analyze the following vehicle information provided by an Ethiopian car dealer and extract structured listing data.

Vehicle information:
---
{$description}
---

Return a JSON object with exactly these fields (use null for any field you cannot determine):
{
  "brand": "string or null - vehicle manufacturer name e.g. Toyota",
  "model": "string or null - model name e.g. Corolla, Land Cruiser",
  "year": "integer or null - manufacturing year e.g. 2021",
  "color": "string or null - exterior color",
  "body_type": "string or null - one of: Sedan, SUV, Pickup, Hatchback, Van, Minivan, Coupe, Convertible, Wagon, Truck, Bus",
  "transmission": "string or null - Automatic or Manual",
  "fuel_type": "string or null - one of: Petrol, Diesel, Hybrid, Electric, CNG",
  "mileage": "integer or null - odometer reading in km",
  "condition": "string or null - one of: Brand New, Excellent, Good, Local Used, Foreign Used",
  "title": "string - professional listing title for marketplace (max 80 chars)",
  "description": "string - professional vehicle description for Ethiopian marketplace (150-200 words)",
  "missing_fields": ["array of string field names that are missing and important for a complete listing"],
  "confidence": "number 0.0-1.0 - how confident you are in the suggestions"
}

Return ONLY the JSON object, no other text.
PROMPT;
    }

    private function parseAiSuggestion(string $text): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $text = preg_replace('/```json\s*/i', '', $text);
        $text = preg_replace('/```\s*/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);

        if (!is_array($decoded)) {
            return $this->emptyStructure();
        }

        return [
            'brand'          => $decoded['brand'] ?? null,
            'model'          => $decoded['model'] ?? null,
            'year'           => is_numeric($decoded['year'] ?? null) ? (int) $decoded['year'] : null,
            'color'          => $decoded['color'] ?? null,
            'body_type'      => $decoded['body_type'] ?? null,
            'transmission'   => $decoded['transmission'] ?? null,
            'fuel_type'      => $decoded['fuel_type'] ?? null,
            'mileage'        => is_numeric($decoded['mileage'] ?? null) ? (int) $decoded['mileage'] : null,
            'condition'      => $decoded['condition'] ?? null,
            'title'          => $decoded['title'] ?? null,
            'description'    => $decoded['description'] ?? null,
            'missing_fields' => is_array($decoded['missing_fields'] ?? null) ? $decoded['missing_fields'] : [],
            'confidence'     => is_numeric($decoded['confidence'] ?? null) ? (float) $decoded['confidence'] : 0.5,
        ];
    }

    private function fallbackExtract(string $description): array
    {
        // Very basic extraction from text when AI fails
        $desc = strtolower($description);
        $brands = ['toyota', 'hyundai', 'kia', 'nissan', 'honda', 'ford', 'bmw', 'mercedes', 'lexus', 'suzuki', 'mitsubishi', 'volkswagen', 'audi', 'subaru', 'mazda', 'jeep', 'land rover', 'volvo', 'isuzu'];
        $foundBrand = null;
        foreach ($brands as $b) {
            if (str_contains($desc, $b)) {
                $foundBrand = ucwords($b);
                break;
            }
        }

        preg_match('/\b(19[89]\d|20[0-2]\d)\b/', $description, $yearMatch);

        return array_merge($this->emptyStructure(), [
            'brand'          => $foundBrand,
            'year'           => isset($yearMatch[1]) ? (int) $yearMatch[1] : null,
            'title'          => $foundBrand ? "$foundBrand Vehicle Listing" : null,
            'missing_fields' => ['model', 'mileage', 'color', 'transmission'],
            'confidence'     => 0.2,
        ]);
    }

    private function emptyStructure(): array
    {
        return [
            'brand'          => null,
            'model'          => null,
            'year'           => null,
            'color'          => null,
            'body_type'      => null,
            'transmission'   => null,
            'fuel_type'      => null,
            'mileage'        => null,
            'condition'      => null,
            'title'          => null,
            'description'    => null,
            'missing_fields' => [],
            'confidence'     => 0.0,
        ];
    }
}
