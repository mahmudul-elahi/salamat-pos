<?php

namespace App\Console\Commands\Michael;

use App\Models\Pos\Purchase\Purchase;
use App\Models\Pos\Purchase\PurchaseItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MichaelJapanShiftCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    #[\Override]
    protected $signature = 'app:michael-japan-shift-command';

    /**
     * The console command description.
     *
     * @var string
     */
    #[\Override]
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $invoiceNumber = Purchase::query()
            ->withTrashed()
            ->max('invoice_number') + 1;

        try {
            DB::beginTransaction();
            $purchases = Purchase::query()
                ->create([
                    'contact_id' => 4306,
                    'tenant_id' => 1,
                    'created_by' => 1,
                    'date' => now(),
                    'invoice_number' => $invoiceNumber,
                    'full_invoice_number' => 'PP-'.str_pad($invoiceNumber, 6, '0', STR_PAD_LEFT),
                    'sub_total' => 0,
                    'discount_type' => null,
                    'discount_amount' => 0,
                    'order_tax' => 0,
                    'shipping_cost' => 0,
                    'status' => 'due',
                    'grand_total' => 0,
                ]);

            $this->info('Purchases created successfully.');

            $purchasesItems = [
                ['serial_number' => '352604726941464', 'unit_price' => 45000],
                ['serial_number' => '354827385099104', 'unit_price' => 45000],
                ['serial_number' => '354827388359646', 'unit_price' => 45000],
                ['serial_number' => '351952297561018', 'unit_price' => 45000],
                ['serial_number' => '350967684526011', 'unit_price' => 45000],
                ['serial_number' => '359203502524082', 'unit_price' => 45000],
                ['serial_number' => '359203502524082', 'unit_price' => 47000],

                ['serial_number' => '359203501151374', 'unit_price' => 45000],
                ['serial_number' => '359203506428710', 'unit_price' => 45000],
                ['serial_number' => '359203506688354', 'unit_price' => 45000],

                ['serial_number' => '357504149160780', 'unit_price' => 45000],

                ['serial_number' => '356815685535065', 'unit_price' => 47500],
                ['serial_number' => '352381815609480', 'unit_price' => 47500],
                ['serial_number' => '350923214511221', 'unit_price' => 47500],

                ['serial_number' => '354827385274590', 'unit_price' => 46000],

                ['serial_number' => '354811956737664', 'unit_price' => 45000],

                ['serial_number' => '350114366221120', 'unit_price' => 70000],

                ['serial_number' => '355833866229250', 'unit_price' => 62600],

                ['serial_number' => '350639792389539', 'unit_price' => 38000],
                ['serial_number' => '359551279567415', 'unit_price' => 38000],
                ['serial_number' => '351548608534262', 'unit_price' => 38000],
                ['serial_number' => '35955127956741',  'unit_price' => 37000],

                ['serial_number' => '357894926091666', 'unit_price' => 38000],

                ['serial_number' => '351519634855062', 'unit_price' => 34500],
                ['serial_number' => '354435320116163', 'unit_price' => 37000],

                ['serial_number' => '352827110730074', 'unit_price' => 32000],

                ['serial_number' => '353830101103011', 'unit_price' => 32000],

                ['serial_number' => '353841101230647', 'unit_price' => 32000],
                ['serial_number' => '352823110339040', 'unit_price' => 32000],
            ];

            foreach ($purchasesItems as $item) {
                PurchaseItem::query()
                    ->where('serial_number', $item['serial_number'])
                    ->where('purchase_id', 103)
                    ->update([
                        'purchase_id' => $purchases->id,
                    ]);
            }
            DB::commit();

            $this->info('Purchases items created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error($e->getMessage());
        }
    }
}
