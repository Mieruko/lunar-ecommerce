<?php

namespace App\Console\Commands;

use App\Models\SupportConversation;
use Illuminate\Console\Command;

class PurgeResolvedSupportConversations extends Command
{
    protected $signature = 'support:purge-resolved
        {--days= : Override the configured retention period}
        {--dry-run : Count eligible conversations without deleting them}';

    protected $description = 'Delete resolved customer-support conversations past the retention period';

    public function handle(): int
    {
        $daysOption = $this->option('days');
        $days = ($daysOption !== null && $daysOption !== '')
            ? (int) $daysOption
            : (int) config('support.retention_days', 180);

        if ($days < 1) {
            $this->error('Thời hạn lưu trữ phải từ 1 ngày trở lên.');

            return self::FAILURE;
        }

        $query = SupportConversation::query()
            ->where('status', 'resolved')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<', now()->subDays($days));

        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info("Có {$count} hội thoại đã giải quyết đủ điều kiện xóa.");

            return self::SUCCESS;
        }

        $deleted = 0;
        $query->select('id')->chunkById(100, function ($conversations) use (&$deleted): void {
            foreach ($conversations as $conversation) {
                $conversation->delete();
                $deleted++;
            }
        });

        $this->info("Đã xóa {$deleted} hội thoại quá thời hạn lưu trữ.");

        return self::SUCCESS;
    }
}
