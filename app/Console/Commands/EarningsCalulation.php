<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserPlan;
use App\Models\Inversion;
use Carbon\Carbon;


class EarningsCalulation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculo diario de ganancias';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $user = User::where('alias','Edwar')->first();
        if($user){
            $user->alias = "Edwar2";
             $user->save();
        }
        // "sábado"  "domingo"
        $daynow = Carbon::now()->dayName;
        if ($daynow != "sábado" &&   $daynow != "domingo") {


            $users = User::whereNotNull('email_verified_at')->where('status', 'enabled')->where('role', 'cliente')->get();

            foreach ($users as $user) {


                $userplans = UserPlan::where('user_id', $user->id)->where('status', 'activo')->get();
                // minimum_charge
                // processing_earnings
                // processed_earnings

                foreach ($userplans as $userplan) {
                    // $user_plan
                    $lastEarning = ($userplan->inversion * 2) - $userplan->processed_earnings;


                    if ($lastEarning < $userplan->daily_gain) {

                        $userplan->processed_earnings += $lastEarning;
                        $userplan->processing_earnings += $lastEarning;
                        $user->earnings_to_date += $lastEarning;
                        $user->earnings_available += $userplan->processing_earnings;
                        $userplan->processing_earnings = 0;
                        $userplan->status = 'finalizado';
                        // }else if ($userplan->processed_earnings >= $userplan->inversion) {
                        //     $lastInversion = ($userplan->inversion * 2) - $userplan->processed_earnings;
                        //     if($lastInversion < $userplan->daily_gain){
                        //         $userplan->processed_inversion += $lastInversion;
                        //         $userplan->processing_inversion += $lastInversion;
                        //         $userplan->processed_earnings += $lastInversion;
                        //         $user->earnings_to_date += $lastInversion;
                        //         $user->inversion_available += $userplan->processing_inversion;
                        //         $userplan->processing_inversion = 0;
                        //         $userplan->status = 'finalizado';
                        //     }else{
                        //         $userplan->processed_inversion += $userplan->daily_gain;
                        //         $userplan->processing_inversion += $userplan->daily_gain;
                        //         $user->earnings_to_date += $userplan->daily_gain;
                        //         $userplan->processed_earnings += $userplan->daily_gain;
                        //         if($userplan->processing_inversion >= $userplan->minimum_charge){
                        //             $user->inversion_available += $userplan->processing_inversion;
                        //             $userplan->processing_inversion = 0;
                        //         }
                        //     }
                    } else {
                        $userplan->processed_earnings += $userplan->daily_gain;
                        $userplan->processing_earnings += $userplan->daily_gain;
                        $user->earnings_to_date += $userplan->daily_gain;

                        if ($userplan->processing_earnings >= $userplan->minimum_charge) {
                            $user->earnings_available += $userplan->processing_earnings;
                            $userplan->processing_earnings = 0;
                        }
                    }

                    $userplan->save();
                    $user->save();
                }
            }
        }
    }
}
