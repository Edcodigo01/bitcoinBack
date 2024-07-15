<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class users extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        DB::table('referral_earnings')->insert([
            'name'=>'Nivel 1',
            'earnings'=>5,
        ]);

        DB::table('referral_earnings')->insert([
            'name'=>'Nivel 2',
            'earnings'=>3,
        ]);

        DB::table('referral_earnings')->insert([
            'name'=>'Nivel 3',
            'earnings'=>1,
        ]);

        // DB::table('users')->insert([
        //     'alias'=>'Admin',
        //     'name'=>'Administrador principal',
        //     'email'=>'Administrador@gmail.com',
        //     'password'=>bcrypt('12345678'),
        //     "email_verified_at"=>'2022-02-02 00:00:00',
        //     'role'=>'administrador-p',
        //     'token_email'=>Null,
        //     'token_reference'=>'XWrefX95X959SDsfXFS'
        // ]);

        // DB::table('users')->insert([
        //     'alias'=>'Edwar',
        //     'name'=>'Edwar Villavicencio',
        //     'email'=>'eavc53189@gmail.com',
        //     'password'=>bcrypt('12345678'),
        //     "email_verified_at"=>'2022-02-02 00:00:00',
        //     'role'=>'cliente',
        //     'token_email'=>Null,
        // ]);

        // DB::table('plans')->insert([
        //     'name'=>'Gratis',
        //     'cost'=>0,
        //     'profit'=>0,
          
        //     'duration'=>0,
            
        //     'products'=>2
        // ]);
        
        // DB::table('plans')->insert([
        //     'name'=>'AVATAR',
        //     'cost'=>50,
        //     'profit'=>5,
            
        //     'duration'=>1,
            
        //     'products'=>30
        // ]);

        // DB::table('plans')->insert([
        //     'name'=>'ASSISTANT',
        //     'cost'=>100,
        //     'profit'=>5.83,
           
        //     'duration'=>1,
            
        //     'products'=>30
        // ]);

        // DB::table('licenses')->insert([
        //     'cost'=>99,
        // ]);
        
        

    }
}
