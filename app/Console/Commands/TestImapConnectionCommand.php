<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class TestImapConnectionCommand extends Command
{
    protected $signature = 'mail:test-imap';

    protected $description = 'Test IMAP connection for Mail Manager';

    public function handle(): int
    {
        if (! extension_loaded('imap')) {
            $this->warn('Native PHP IMAP extension is not loaded (optional for Webklex). Continuing with socket IMAP…');
            $this->line('To enable: uncomment extension=imap in C:\xampp\php\php.ini');
        }

        $host = config('imap.accounts.agilecraft.host', env('IMAP_HOST', 'mail.agilecraft.co.ke'));
        $port = (int) config('imap.accounts.agilecraft.port', env('IMAP_PORT', 993));
        $user = config('imap.accounts.agilecraft.username', env('IMAP_USERNAME'));
        $encryption = config('imap.accounts.agilecraft.encryption', env('IMAP_ENCRYPTION', 'ssl'));

        $this->info('Testing IMAP connection (Agile Craft)...');
        $this->line("Host: {$host}:{$port} ({$encryption}), User: {$user}");

        // Pre-check: DNS
        $ip = gethostbyname($host);
        if ($ip === $host) {
            $this->warn("DNS: Could not resolve '{$host}' — check hostname or firewall.");
        } else {
            $this->line("DNS: {$host} → {$ip}");
        }

        // Pre-check: port reachable
        $errno = 0;
        $errstr = '';
        $scheme = ($encryption === 'ssl') ? 'ssl' : 'tcp';
        $target = ($encryption === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
        $fp = @stream_socket_client($target, $errno, $errstr, 8);
        if (! $fp) {
            $this->warn("Port: Cannot reach {$host}:{$port} — {$errstr} ({$errno})");
            $this->line('Check: firewall, VPN, or host/port from your mail provider.');
        } else {
            $this->line('Port: Reachable');
            fclose($fp);
        }

        try {
            $cm = new ClientManager(config('imap'));
            $client = $cm->account('agilecraft');
            $client->connect();
            $this->info('IMAP: Connected successfully to Agile Craft mailbox.');
            $folders = $client->getFolders();
            $this->line('Folders: ' . $folders->pluck('path')->implode(', '));
            $client->disconnect();
            return self::SUCCESS;
        } catch (ConnectionFailedException $e) {
            $msg = $e->getMessage();
            $prev = $e->getPrevious();
            if ($prev) {
                $msg .= ' | ' . $prev->getMessage();
            }
            $this->error('IMAP failed: ' . $msg);
            $this->newLine();
            $this->line('Check IMAP_* settings in .env (mail.agilecraft.co.ke / info@agilecraft.co.ke).');
            $this->line('Or create email manually: Tools → Mail Manager → Create Email');
            return self::FAILURE;
        } catch (\Throwable $e) {
            $this->error('Error: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
