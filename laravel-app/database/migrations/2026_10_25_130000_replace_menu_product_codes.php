<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReplaceMenuProductCodes extends Migration
{
    public function up()
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('products')) {
            return;
        }

        $used = [];
        foreach (DB::table('products')->pluck('code') as $code) {
            $used[(string) $code] = true;
        }

        $rows = DB::table('products')->where('code', 'like', 'MNU-%')->orderBy('id')->get(['id', 'code']);
        foreach ($rows as $row) {
            unset($used[(string) $row->code]);
            $code = $this->numericCode($used);
            $used[$code] = true;
            $update = ['code' => $code];
            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'barcode_symbology')) {
                $update['barcode_symbology'] = 'C128';
            }
            DB::table('products')->where('id', $row->id)->update($update);
        }
    }

    public function down()
    {
    }

    protected function numericCode(array $used)
    {
        do {
            $code = (string) random_int(10000000, 99999999);
        } while (isset($used[$code]));

        return $code;
    }
}
