<?php

namespace App\Utils;

use App\TaxRate;
use App\GroupSubTax;

class TaxUtil extends Util
{

    /**
     * Updates tax amount of a tax group
     *
     * @param int $group_tax_id
     *
     * @return void
     */
    public function updateGroupTaxAmount($group_tax_id)
    {
        
        $tax_rate = TaxRate::where('id', $group_tax_id)->with(['sub_taxes'])->first();
        if($tax_rate){
            $amount = 0;
            foreach ($tax_rate->sub_taxes->where('for_tax_group',1) as $sub_tax) {
                $amount += $sub_tax->amount;
            }
            $tax_rate->amount = $amount;
            $tax_rate->save();
        }
        
        
    }
}
