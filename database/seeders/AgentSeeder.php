<?php

namespace Database\Seeders;

use App\Models\Agent;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Agent::tableExists()) {
            return;
        }

        $agents = [
            ['name' => 'Grace Njeri', 'code' => 'AG-1001', 'email' => 'grace.njeri@example.co.ke', 'phone' => '0712000101', 'type' => 'Agent'],
            ['name' => 'Brian Otieno', 'code' => 'AG-1002', 'email' => 'brian.otieno@example.co.ke', 'phone' => '0712000102', 'type' => 'Agent'],
            ['name' => 'Fatuma Hassan', 'code' => 'AG-1003', 'email' => 'fatuma.hassan@example.co.ke', 'phone' => '0712000103', 'type' => 'Agent'],
            ['name' => 'Samuel Kiprono', 'code' => 'AG-1004', 'email' => 'samuel.kiprono@example.co.ke', 'phone' => '0712000104', 'type' => 'Agent'],
            ['name' => 'Mercy Wambui', 'code' => 'AG-1005', 'email' => 'mercy.wambui@example.co.ke', 'phone' => '0712000105', 'type' => 'Agent'],
            ['name' => 'Peter Mwangi', 'code' => 'AG-1006', 'email' => 'peter.mwangi@example.co.ke', 'phone' => '0712000106', 'type' => 'Agent'],
            ['name' => 'Aisha Abdi', 'code' => 'AG-1007', 'email' => 'aisha.abdi@example.co.ke', 'phone' => '0712000107', 'type' => 'Agent'],
            ['name' => 'ABC Insurance Brokers Ltd', 'code' => 'BR-2001', 'email' => 'ops@abcbrokers.co.ke', 'phone' => '0202000201', 'type' => 'Broker'],
            ['name' => 'Metro Bancassurance', 'code' => 'BA-3001', 'email' => 'bancassurance@metrobank.co.ke', 'phone' => '0202000301', 'type' => 'Bancassurance'],
            ['name' => 'Direct / Head Office', 'code' => 'DIR-0000', 'email' => null, 'phone' => null, 'type' => 'Direct'],
        ];

        foreach ($agents as $agent) {
            Agent::updateOrCreate(['code' => $agent['code']], $agent + ['active' => true]);
        }
    }
}
