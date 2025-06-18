<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Filament\Widgets\RingkasanStatistikUtama;

class TestWidget extends Command
{
    protected $signature = 'test:widget';
    protected $description = 'Test widget functionality';

    public function handle()
    {
        $this->info('Testing RingkasanStatistikUtama widget...');
        
        $periods = ['keseluruhan', 'tahun_ini', 'bulan_ini'];
        
        foreach ($periods as $period) {
            $this->info("Testing period: {$period}");
            
            try {
                $widget = new RingkasanStatistikUtama();
                $widget->currentPeriod = $period;
                
                $stats = $widget->getStatsProperty();
                
                $this->line("Period [{$period}] - Total stats: " . count($stats));
                $this->line("  Total Dana: {$stats[0]['value']} - {$stats[0]['description']}");
                $this->line("  Penyaluran Langsung: {$stats[7]['value']} - {$stats[7]['description']}");
                
            } catch (\Exception $e) {
                $this->error("Period [{$period}] failed: " . $e->getMessage());
            }
            
            $this->line('');
        }
    }
}
