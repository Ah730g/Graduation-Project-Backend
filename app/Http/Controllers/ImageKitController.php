<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ImageKit\ImageKit;
class ImageKitController extends Controller
{
    /**
     * Get ImageKit authentication parameters for secure client-side uploads
     * 
     * This endpoint generates authentication parameters (token, expire, signature)
     * that allow the frontend to upload files directly to ImageKit without
     * exposing the private key.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function auth(Request $request)
    {
        $publicKey = config('services.imagekit.public_key');
        $privateKey = config('services.imagekit.private_key');
        $urlEndpoint = config('services.imagekit.url_endpoint');

        // Fail fast if ImageKit credentials are not configured
        if (empty($publicKey) || empty($privateKey) || empty($urlEndpoint)) {
            \Log::error('ImageKit configuration missing', [
                'public_key_set' => !empty($publicKey),
                'private_key_set' => !empty($privateKey),
                'url_endpoint_set' => !empty($urlEndpoint),
            ]);
            
            return response()->json([
                'error' => 'ImageKit configuration error',
                'message' => 'ImageKit credentials are not configured. Please set IMAGEKIT_PUBLIC_KEY, IMAGEKIT_PRIVATE_KEY, and IMAGEKIT_URL_ENDPOINT in your .env file.',
            ], 500);
        }

        try {
            $imageKit = new ImageKit($publicKey, $privateKey, $urlEndpoint);
            $authParams = $imageKit->getAuthenticationParameters();
            
            // Prevent caching of authentication tokens
            // Each token can only be used once, so we must always return fresh tokens
            return response()->json($authParams)
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            \Log::error('ImageKit authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'ImageKit authentication error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
