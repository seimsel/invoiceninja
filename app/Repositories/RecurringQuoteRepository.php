<?php
/**
 * Invoice Ninja (https://invoiceninja.com)
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2019. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://opensource.org/licenses/AAL
 */

namespace App\Repositories;

use App\Helpers\Invoice\InvoiceCalc;
use App\Models\RecurringQuote;
use Illuminate\Http\Request;

/**
 * RecurringQuoteRepository
 */
class RecurringQuoteRepository extends BaseRepository
{


    public function getClassName()
    {
        return RecurringQuote::class;
    }
    
	public function save(Request $request, RecurringQuote $quote) : ?RecurringQuote
	{
        $quote->fill($request->input());
        
        $quote->save();


        $quote_calc = new InvoiceCalc($quote, $quote->settings);

        $quote = $quote_calc->build()->getInvoice();

        //fire events here that cascading from the saving of an Quote
        //ie. client balance update...
        
        return $quote;
	}

}