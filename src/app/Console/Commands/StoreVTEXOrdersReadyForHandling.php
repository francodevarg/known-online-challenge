<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Tasks\TaskStoreVTEXOrdersReadyForHandling;

class StoreVTEXOrdersReadyForHandling extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:store_vtex_orders_ready_for_handling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and Store VTEX Orders with status == "ready_for_handling" ';

    const INICIAL_MESSAGE = 'INICIO DEL PROCESO' .PHP_EOL;
    const FINAL_MESSAGE = 'FIN DEL PROCESO' .PHP_EOL;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //StartProcess
        echo self::INICIAL_MESSAGE;
        Log::channel($this->signature)->info(self::INICIAL_MESSAGE); 
        
        //Execute Process
        TaskStoreVTEXOrdersReadyForHandling::process();
        
        //EndOfProcess
        echo self::FINAL_MESSAGE;
        Log::channel($this->signature)->info(self::FINAL_MESSAGE); 
    }
}
