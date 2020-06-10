<?php

namespace App\Console;

use App\Console\Commands\OutgoingRecommendation;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\OutgoingRecommendation'
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('recommendations:queue')
            ->dailyAt('12:30')
            ->timezone('Australia/Sydney')
            ->weekdays()
            ->before(function () {
                Log::info('Starting recommendations...');
            })
            ->after(function () {
                Log::info('Finishing recommendations...');
            })
            ->onSuccess(function () {
                Log::info('Recommendations completed...');
            })
            ->onFailure(function () {
                Log::info('Recommendations failed...');
            });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
