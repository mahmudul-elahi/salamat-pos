<?php

namespace Database\Seeders;

use App\Models\Ecommerce\Emi\EmiBank;
use Illuminate\Database\Seeder;

class EmiBankSeeder extends Seeder
{
    private array $banks = [
        ['name' => 'AB Bank PLC',                                               'gw_key' => 'abbank',            'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Al-Arafah Islami Bank PLC',                                'gw_key' => 'alarafah',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Bank Asia Limited',                                         'gw_key' => 'bankasia',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'BRAC Bank PLC',                                             'gw_key' => 'bracbank',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Citizens Bank PLC',                                         'gw_key' => 'citizensbank',      'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'City Bank PLC',                                             'gw_key' => 'citybank',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Community Bank Bangladesh Limited',                         'gw_key' => 'communitybank',     'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Dhaka Bank PLC',                                            'gw_key' => 'dhakabank',         'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Dutch-Bangla Bank PLC',                                    'gw_key' => 'dutchbangla',       'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 36=>19.50]],
        ['name' => 'Eastern Bank PLC',                                          'gw_key' => 'easternbank',       'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Export-Import Bank of Bangladesh Limited (Exim Bank)',      'gw_key' => 'eximbank',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'IFIC Bank',                                                 'gw_key' => 'ificbank',          'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Islami Bank Bangladesh PLC',                                'gw_key' => 'islamibank',        'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Jamuna Bank Limited',                                       'gw_key' => 'jamunabank',        'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 36=>19.50]],
        ['name' => 'Lanka Bangla Financial Limited',                            'gw_key' => 'lankabangla',       'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Mercantile Bank PLC',                                       'gw_key' => 'mercantilebank',    'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Meghna Bank Limited',                                       'gw_key' => 'meghnabank',        'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Midland Bank Limited',                                      'gw_key' => 'midlandbank',       'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Modhumoti Bank PLC',                                        'gw_key' => 'modhumotibank',     'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50]],
        ['name' => 'Mutual Trust Bank PLC',                                     'gw_key' => 'mtb',               'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 36=>19.50]],
        ['name' => 'National Bank Limited',                                     'gw_key' => 'nationalbank',      'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'National Credit & Commerce Bank PLC',                       'gw_key' => 'nccbank',           'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 36=>19.50]],
        ['name' => 'NRB Bank Limited',                                          'gw_key' => 'nrbbank',           'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 36=>19.50]],
        ['name' => 'NRB Commercial Bank',                                       'gw_key' => 'nrbcommercial',     'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'One Bank PLC',                                              'gw_key' => 'onebank',           'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Premier Bank',                                              'gw_key' => 'premierbank',       'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Prime Bank',                                                'gw_key' => 'primebank',         'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Pubali Bank Limited',                                       'gw_key' => 'pubalibank',        'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Shahjalal Islami Bank Limited (SJIBL)',                     'gw_key' => 'sjibl',             'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50]],
        ['name' => 'Shimanto Bank',                                             'gw_key' => 'shimantobank',      'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'South Bangla Agriculture & Commerce Bank Limited (SBAC)',   'gw_key' => 'sbac',              'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Southeast Bank PLC',                                        'gw_key' => 'southeastbank',     'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'Standard Bank Limited',                                     'gw_key' => 'standardbank',      'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50]],
        ['name' => 'Standard Chartered Bank',                                   'gw_key' => 'standardchartered', 'tenures' => [3=>3.50, 6=>5.50, 9=>8.00, 12=>10.50, 18=>13.50, 24=>17.50, 36=>22.50]],
        ['name' => 'Trust Bank Limited',                                        'gw_key' => 'trustbank',         'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 18=>11.50, 24=>15.50, 30=>16.50, 36=>19.50]],
        ['name' => 'United Commercial Bank PLC (UCB)',                          'gw_key' => 'ucb',               'tenures' => [3=>3.00, 6=>4.50, 9=>6.50, 12=>8.50, 24=>15.50]],
    ];

    public function run(): void
    {
        foreach ($this->banks as $bank) {
            $emiBank = EmiBank::updateOrCreate(
                ['gw_key' => $bank['gw_key']],
                ['bank_name' => $bank['name'], 'is_active' => true]
            );

            $emiBank->tenures()->delete();

            $tenures = [];
            foreach ($bank['tenures'] as $months => $rate) {
                $tenures[] = ['months' => $months, 'charge_rate' => $rate, 'is_active' => true];
            }
            $emiBank->tenures()->createMany($tenures);
        }
    }
}
