<?php

namespace App\Console\Commands;

use App\Http\Controllers\ChatbotController;
use Illuminate\Console\Command;

class TestGeminiConnection extends Command
{
    protected $signature = 'gemini:test {--key= : Optional API key override to test}';
    protected $description = 'Test Google Gemini AI API connection for the Teraju workshop assistant';

    public function handle(): int
    {
        $this->info('Testing Google Gemini AI Connection...');
        $this->line('--------------------------------------------------');

        $keyOverride = $this->option('key');
        $apiKey = $keyOverride ?: config('services.gemini.api_key');
        $model  = config('services.gemini.model', 'gemini-flash-lite-latest');

        if (empty($apiKey)) {
            $this->error('✗ GEMINI_API_KEY is not configured in your .env file.');
            $this->newLine();
            $this->line('How to connect:');
            $this->line('1. Visit: https://aistudio.google.com/app/apikey (Google AI Studio - 100% Free)');
            $this->line('2. Click "Create API Key"');
            $this->line('3. Open your .env file and set:');
            $this->comment('   GEMINI_API_KEY=AIzaSy...');
            $this->comment('   GEMINI_MODEL=gemini-1.5-flash');
            $this->newLine();
            return self::FAILURE;
        }

        $maskedKey = substr($apiKey, 0, 7) . '...' . substr($apiKey, -4);
        $this->line("Using API Key: {$maskedKey}");
        $this->line("Target Model:  {$model}");
        $this->line('Sending diagnostic ping to Google Generative Language API...');

        $result = ChatbotController::checkConnection($keyOverride);

        if ($result['connected']) {
            $this->newLine();
            $this->info('✓ SUCCESS: Google Gemini AI is connected and active!');
            $this->line("Model in use: {$result['model']}");
            if (isset($result['note'])) {
                $this->comment($result['note']);
            }
            $this->line("AI Ping Reply: \"{$result['reply']}\"");
            $this->newLine();
            $this->info('The workshop chatbot is now powered by Google Gemini AI!');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('✗ FAILED: Unable to communicate with Google Gemini API.');
        $this->line("Error Details: {$result['error']}");
        if (isset($result['http_code'])) {
            $this->line("HTTP Status Code: {$result['http_code']}");
        }
        $this->newLine();
        $this->comment('Troubleshooting Tips:');
        $this->line('• Verify your key at https://aistudio.google.com/app/apikey');
        $this->line('• If your quota or region is limited, check if gemini-2.0-flash or gemini-1.5-flash is supported');
        $this->line('• Ensure your internet connection can reach generativelanguage.googleapis.com');
        return self::FAILURE;
    }
}
