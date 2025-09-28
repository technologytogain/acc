<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel{

    protected function schedule(Schedule $schedule){
      
        
       $schedule->command('attendance')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
       $schedule->command('notification')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
       $schedule->command('database:backup')->daily()->withoutOverlapping()->runInBackground();


       // $schedule->command('addstudent')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
       // $schedule->command('deletestudent')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
       // $schedule->command('clone:device')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
       // $schedule->command('importstudent:device')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();
        //$schedule->command('sync:log_instant')->everyMinute()->withoutOverlapping()->runInBackground();
        //$schedule->command('sync:log')->everyThirtyMinutes()->withoutOverlapping()->runInBackground();*/


    }

    protected function commands(){
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
