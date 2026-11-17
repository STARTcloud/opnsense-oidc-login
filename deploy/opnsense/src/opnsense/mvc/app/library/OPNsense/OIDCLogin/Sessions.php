<?php

namespace OPNsense\OIDCLogin;

class Sessions
{
    public const COOKIE = 'oidclogin';

    private const DIRECTORY = '/var/db/oidclogin';

    public function remember(array $entry): string
    {
        if (!is_dir(self::DIRECTORY)) {
            mkdir(self::DIRECTORY, 0700, true);
        }
        foreach ($this->entries() as $handle => $known) {
            if (!is_file($this->sessionFile((string)($known['session'] ?? '')))) {
                $this->forget($handle);
            }
        }
        $handle = bin2hex(random_bytes(32));
        $path = $this->path($handle);
        touch($path);
        chmod($path, 0600);
        file_put_contents($path, json_encode($entry), LOCK_EX);
        return $handle;
    }

    public function find(string $handle): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $handle) || !is_file($this->path($handle))) {
            return null;
        }
        $entry = json_decode((string)file_get_contents($this->path($handle)), true);
        return is_array($entry) ? $entry : null;
    }

    public function forget(string $handle): void
    {
        if (preg_match('/^[a-f0-9]{64}$/', $handle) && is_file($this->path($handle))) {
            unlink($this->path($handle));
        }
    }

    public function end(string $sid, string $subject): int
    {
        $ended = 0;
        foreach ($this->entries() as $handle => $entry) {
            $known = (string)($entry['sid'] ?? '');
            $match = $sid !== '' && $known !== ''
                ? $known === $sid
                : $subject !== '' && ($entry['sub'] ?? '') === $subject;
            if ($match) {
                $file = $this->sessionFile((string)($entry['session'] ?? ''));
                if (is_file($file)) {
                    unlink($file);
                }
                $this->forget($handle);
                $ended++;
            }
        }
        return $ended;
    }

    private function entries(): array
    {
        $entries = [];
        foreach (glob(self::DIRECTORY . '/*.json') ?: [] as $file) {
            $entry = json_decode((string)file_get_contents($file), true);
            if (is_array($entry)) {
                $entries[basename($file, '.json')] = $entry;
            }
        }
        return $entries;
    }

    private function path(string $handle): string
    {
        return self::DIRECTORY . '/' . $handle . '.json';
    }

    private function sessionFile(string $id): string
    {
        return rtrim((string)session_save_path(), '/') . '/sess_' . preg_replace('/[^A-Za-z0-9,-]/', '', $id);
    }
}
