<?php

namespace App\Filament\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemLogs extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-command-line';

    protected static string | \UnitEnum | null $navigationGroup = 'System Management';

    protected static ?string $navigationLabel = 'System Logs';

    protected static ?string $title = 'System Logs';

    protected static ?int $navigationSort = 99;

    protected string $view = 'filament.pages.system-logs';

    // Filters
    public string $selectedFile = '';
    public string $level = 'all';
    public string $search = '';
    public string $dateFilter = '';
    public int $perPage = 50;
    public int $page = 1;

    /**
     * Allow access to Sudo Administrators and School Administrators.
     */
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }
        return in_array($user->role, ['sudo', 'admin'], true);
    }

    public function mount(): void
    {
        if (!static::canAccess()) {
            abort(403, 'Unauthorized access. System logs are restricted to Administrators.');
        }

        $availableFiles = $this->getAvailableLogFiles();
        if (!empty($availableFiles)) {
            $this->selectedFile = array_key_first($availableFiles);
        }
    }

    /**
     * Get array of available log files [label => full_path] sorted by last modified desc.
     * Searches central storage/logs, current context storage/logs, and all tenant log directories.
     *
     * @return array<string, string>
     */
    public function getAvailableLogFiles(): array
    {
        $found = [];

        // 1. Central storage/logs
        $centralLogDir = base_path('storage/logs');
        if (File::isDirectory($centralLogDir)) {
            foreach (File::glob("{$centralLogDir}/*.log") as $file) {
                $found[$file] = 'Central: ' . basename($file);
            }
        }

        // 2. Current context storage_path('logs') if different from central
        $currentLogDir = storage_path('logs');
        if (File::isDirectory($currentLogDir) && $currentLogDir !== $centralLogDir) {
            foreach (File::glob("{$currentLogDir}/*.log") as $file) {
                $found[$file] = 'Current Context: ' . basename($file);
            }
        }

        // 3. Any tenant-specific storage directories: storage/tenant*/logs/*.log
        $tenantLogDirs = File::glob(base_path('storage/tenant*/logs/*.log'));
        if (!empty($tenantLogDirs)) {
            foreach ($tenantLogDirs as $file) {
                if (!isset($found[$file])) {
                    // Extract tenant identifier from path e.g. storage/tenantlivingsspring/logs/laravel.log
                    preg_match('#storage/tenant([^/]+)/logs/(.+)#', $file, $m);
                    $tenantName = $m[1] ?? 'tenant';
                    $logName = $m[2] ?? basename($file);
                    $found[$file] = "Tenant ({$tenantName}): {$logName}";
                }
            }
        }

        if (empty($found)) {
            return [];
        }

        // Sort by last modified descending
        $files = array_keys($found);
        usort($files, fn ($a, $b) => File::lastModified($b) <=> File::lastModified($a));

        $result = [];
        foreach ($files as $file) {
            $label = $found[$file];
            $result[$label] = $file;
        }

        return $result;
    }

    /**
     * Resolve path to current log file.
     */
    public function getLogFilePath(): ?string
    {
        $available = $this->getAvailableLogFiles();

        if (!empty($this->selectedFile) && isset($available[$this->selectedFile])) {
            return $available[$this->selectedFile];
        }

        if (!empty($available)) {
            return reset($available);
        }

        $default = storage_path('logs/laravel.log');
        return File::exists($default) ? $default : (File::exists(base_path('storage/logs/laravel.log')) ? base_path('storage/logs/laravel.log') : null);
    }

    /**
     * Clear / truncate the selected log file.
     */
    public function clearLogs(): void
    {
        $logPath = $this->getLogFilePath();

        if ($logPath && File::exists($logPath)) {
            File::put($logPath, '');
            
            Notification::make()
                ->title('System Logs Cleared')
                ->body('The log file has been successfully truncated.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Log File Not Found')
                ->body('No log file found to clear.')
                ->warning()
                ->send();
        }
    }

    /**
     * Download the log file.
     */
    public function downloadLogs(): ?BinaryFileResponse
    {
        $logPath = $this->getLogFilePath();

        if (!$logPath || !File::exists($logPath)) {
            Notification::make()
                ->title('Log File Not Found')
                ->warning()
                ->send();
            return null;
        }

        return response()->download($logPath, basename($logPath));
    }

    /**
     * Parse the log file into structured entries.
     *
     * @return array{entries: array, stats: array}
     */
    public function getLogData(): array
    {
        $logPath = $this->getLogFilePath();

        if (!$logPath || !File::exists($logPath) || File::size($logPath) === 0) {
            return [
                'entries' => [],
                'stats' => [
                    'total' => 0,
                    'errors' => 0,
                    'warnings' => 0,
                    'info' => 0,
                    'file_size' => '0 B',
                ],
            ];
        }

        $fileSize = File::size($logPath);
        $formattedSize = $this->formatBytes($fileSize);

        // Read log content (limit to last 5MB for performance if file is huge)
        $maxBytes = 5 * 1024 * 1024;
        if ($fileSize > $maxBytes) {
            $f = fopen($logPath, 'rb');
            fseek($f, -$maxBytes, SEEK_END);
            $content = fread($f, $maxBytes);
            fclose($f);
        } else {
            $content = File::get($logPath);
        }

        // Regex pattern to split entries starting with timestamp: [YYYY-MM-DD HH:MM:SS]
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?\d*[\+\-]?\d*:?\d*)\]\s+(?:([a-zA-Z0-9_\-\.]+)\.)?([A-Z]+):\s+(.*)/m';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        $allEntries = [];
        $stats = [
            'total' => 0,
            'errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'file_size' => $formattedSize,
        ];

        $matchCount = count($matches);

        for ($i = 0; $i < $matchCount; $i++) {
            $match = $matches[$i];
            $timestamp = $match[1][0];
            $env = !empty($match[2][0]) ? $match[2][0] : 'local';
            $level = strtoupper($match[3][0]);
            $messageLine = $match[4][0];

            // Extract context/stack trace up to the start of next log entry
            $offsetCurrent = $match[0][1] + strlen($match[0][0]);
            $offsetNext = ($i + 1 < $matchCount) ? $matches[$i + 1][0][1] : strlen($content);
            $stackTrace = trim(substr($content, $offsetCurrent, $offsetNext - $offsetCurrent));

            // Stats tally
            $stats['total']++;
            if (in_array($level, ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'])) {
                $stats['errors']++;
            } elseif ($level === 'WARNING') {
                $stats['warnings']++;
            } elseif (in_array($level, ['INFO', 'NOTICE'])) {
                $stats['info']++;
            }

            // Filtering
            // 1. Level Filter
            if ($this->level !== 'all') {
                if ($this->level === 'error' && !in_array($level, ['ERROR', 'CRITICAL', 'EMERGENCY', 'ALERT'])) {
                    continue;
                }
                if ($this->level === 'warning' && $level !== 'WARNING') {
                    continue;
                }
                if ($this->level === 'info' && !in_array($level, ['INFO', 'NOTICE'])) {
                    continue;
                }
                if ($this->level === 'debug' && $level !== 'DEBUG') {
                    continue;
                }
            }

            // 2. Date Filter
            if (!empty($this->dateFilter)) {
                if (!str_starts_with($timestamp, $this->dateFilter)) {
                    continue;
                }
            }

            // 3. Search Term Filter
            if (!empty($this->search)) {
                $searchTerm = strtolower($this->search);
                $inMessage = str_contains(strtolower($messageLine), $searchTerm);
                $inStack = str_contains(strtolower($stackTrace), $searchTerm);
                $inEnv = str_contains(strtolower($env), $searchTerm);

                if (!$inMessage && !$inStack && !$inEnv) {
                    continue;
                }
            }

            $allEntries[] = [
                'id' => $i,
                'timestamp' => $timestamp,
                'env' => $env,
                'level' => $level,
                'message' => $messageLine,
                'stack' => $stackTrace,
            ];
        }

        // Return latest entries first (descending by timestamp)
        $reversed = array_reverse($allEntries);

        return [
            'entries' => $reversed,
            'stats' => $stats,
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function resetFilters(): void
    {
        $this->level = 'all';
        $this->search = '';
        $this->dateFilter = '';
        $this->page = 1;
    }
}
