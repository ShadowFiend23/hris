<?php

namespace Database\Seeders;

use App\Modules\Timekeeping\Models\BiometricTerminal;
use Illuminate\Database\Seeder;

class BiometricTerminalSeeder extends Seeder
{
    /**
     * Terminal assignments based on observed Alpeta log patterns:
     * - 'in'  terminals: those most commonly used as the FIRST swipe of the day
     * - 'out' terminals: those most commonly used as the LAST swipe of the day
     */
    public function run(): void
    {
        $terminals = [
            // IN terminals (entry points — most commonly first swipe of day)
            ['alpeta_terminal_id' => 30, 'name' => 'Main Entrance',       'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 29, 'name' => 'Side Entrance A',     'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 28, 'name' => 'Side Entrance B',     'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 27, 'name' => 'Side Entrance C',     'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 26, 'name' => 'Side Entrance D',     'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 18, 'name' => 'Staff Entry A',       'type' => 'in',  'is_active' => true],
            ['alpeta_terminal_id' => 25, 'name' => 'Staff Entry B',       'type' => 'in',  'is_active' => true],

            // OUT terminals (exit points — most commonly last swipe of day)
            ['alpeta_terminal_id' => 33, 'name' => 'Main Exit',           'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 32, 'name' => 'Side Exit A',         'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 31, 'name' => 'Side Exit B',         'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 19, 'name' => 'Side Exit C',         'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 20, 'name' => 'Staff Exit A',        'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 21, 'name' => 'Staff Exit B',        'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 23, 'name' => 'Staff Exit C',        'type' => 'out', 'is_active' => true],
            ['alpeta_terminal_id' => 24, 'name' => 'Staff Exit D',        'type' => 'out', 'is_active' => true],
        ];

        foreach ($terminals as $terminal) {
            BiometricTerminal::updateOrCreate(
                ['alpeta_terminal_id' => $terminal['alpeta_terminal_id']],
                $terminal
            );
        }
    }
}
