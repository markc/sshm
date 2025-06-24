<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Panther\Client;

echo "🧪 Testing Hybrid Mode - Livewire Forms + Pure JS Terminal...\n";

// Start Firefox with Panther
$client = Client::createFirefoxClient();

try {
    echo "📍 Navigating to SSH Command Runner (Hybrid)...\n";
    $crawler = $client->request('GET', 'http://127.0.0.1:8000/admin/ssh-command-runner');

    // Wait for page to load
    sleep(3);

    echo "🔍 Checking hybrid setup...\n";
    echo 'Title: ' . $client->getTitle() . "\n";

    // Check if Livewire form is present
    $commandForm = $crawler->filter('textarea[placeholder*="SSH command"]');
    echo 'Livewire form found: ' . ($commandForm->count() > 0 ? 'YES' : 'NO') . "\n";

    // Check if pure JS terminal is present
    $terminalOutput = $crawler->filter('#terminal-output');
    echo 'Pure JS terminal found: ' . ($terminalOutput->count() > 0 ? 'YES' : 'NO') . "\n";

    // Fill in a test command
    if ($commandForm->count() > 0) {
        echo "✏️ Entering test command: 'echo Hybrid Test; free'...\n";
        $commandForm->sendKeys("echo 'Hybrid Test Success'; free");

        // Find run button and click it
        $runButton = $crawler->filter('button')->reduce(function ($node) {
            return strpos($node->text(), 'Run Command') !== false;
        });

        if ($runButton->count() > 0) {
            echo "🚀 Clicking Run Command button...\n";
            $runButton->click();

            // Wait for execution and monitor for content
            echo "⏳ Monitoring terminal output for 10 seconds...\n";

            for ($i = 0; $i < 10; $i++) {
                sleep(1);
                $crawler = $client->refreshCrawler();

                // Check terminal section visibility
                $terminalSection = $crawler->filter('#terminal-section');
                $terminalOutput = $crawler->filter('#terminal-output');

                if ($terminalSection->count() > 0) {
                    $sectionDisplay = $terminalSection->attr('style');
                    echo '  Second ' . ($i + 1) . ": Terminal section style: {$sectionDisplay}\n";

                    if ($terminalOutput->count() > 0) {
                        $content = trim($terminalOutput->text());
                        if (strlen($content) > 0) {
                            echo '  📝 Terminal content found: ' . strlen($content) . " chars\n";
                            echo "  📄 Content preview: '" . substr($content, 0, 100) . "...'\n";

                            if (strpos($content, 'Hybrid Test Success') !== false) {
                                echo "  ✅ SUCCESS: Test string found in output!\n";
                                break;
                            }
                        }
                    }
                }
            }
        } else {
            echo "❌ Run Command button not found\n";
        }
    }

    // Take screenshot
    echo "\n📸 Taking final screenshot...\n";
    $client->takeScreenshot('/tmp/sshm-hybrid-test.png');
    echo "Screenshot saved to: /tmp/sshm-hybrid-test.png\n";

    echo "\n✅ Hybrid test complete!\n";

} catch (Exception $e) {
    echo '❌ Error: ' . $e->getMessage() . "\n";
    $client->takeScreenshot('/tmp/sshm-hybrid-error.png');
} finally {
    $client->quit();
}
