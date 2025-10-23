<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use Carbon\Carbon;

class UpdateScheduleStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedules:update-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update schedule status based on current time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        
        // Update schedules that have ended to 'completed'
        $completedSchedules = Schedule::where('status', 'active')
            ->where('end_time', '<', $now)
            ->update(['status' => 'completed']);
            
        $this->info("Updated {$completedSchedules} schedules to completed status");
        
        // Update schedules that are starting soon to 'starting'
        $startingSoon = Schedule::where('status', 'active')
            ->where('start_time', '<=', $now->addMinutes(15))
            ->where('start_time', '>', $now->subMinutes(15))
            ->update(['status' => 'starting']);
            
        $this->info("Updated {$startingSoon} schedules to starting status");
        
        // Release expired seat locks
        $expiredLocks = \App\Models\ScheduleSeat::where('status', 'reserved')
            ->where('locked_until', '<', $now)
            ->update([
                'status' => 'available',
                'locked_until' => null
            ]);
            
        $this->info("Released {$expiredLocks} expired seat locks");
        
        return 0;
    }
}
