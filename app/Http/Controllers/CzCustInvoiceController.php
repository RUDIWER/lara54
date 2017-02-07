<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use App\Models\CzCustInvoice;
use App\Models\CzCustInvoiceDetail;
use App\Models\CzParameter;
use App\Models\PsCustomer;
use App\Models\PsAddress;
use App\Models\CzProduct;
use App\Http\Controllers\Controller;
use Dhtmlx\Connector\GridConnector;
use Illuminate\Http\Request;

class CzCustInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function invoices()
    {
        return view('verkopen.facturen.invoices');
    }

    public function invoiceData() 
    {
        $connector = new GridConnector(null, "PHPLaravel");
        $connector->configure(
            new CzCustInvoice(),
            "id",
            "id_cust_invoice,invoice_date,payment_method,id_cust_order,order_reference,ordernr_bol,customer_name,customer_first_name,customer_email,total_invoice_incl_btw"
        );
        $connector->render();
    }

    public function edit($id_cust_invoice) 
    {
        $invoice = CzCustInvoice::where('id_cust_invoice', $id_cust_invoice)->first();
        $customers = PsCustomer::all();
        $products = CzProduct::all();
        $param = CzParameter::find(1);
        $invoiceDetails = $invoice->invoiceDetails;
        $isNew = 0;
        return view('verkopen.facturen.edit', compact('invoice','customers', 'products', 'invoiceDetails','param', 'isNew'));
    }

    public function create()
    {
        $invoice = new CzCustInvoice();
        $customers = PsCustomer::all();
        $products = CzProduct::all();
        $param = CzParameter::find(1);
        $invoiceDetails = 0;
        $isNew = 1;
        return view('verkopen.facturen.edit', compact('invoice', 'customers', 'products', 'invoiceDetails', 'param', 'isNew'));
    }

    public function save(Request $request, $id_cust_invoice)
    {
        $data = $request->all();
        $invoice = CzCustInvoice::findornew($id_cust_invoice); 
     // Fields to save for edit existing invoice AND for NEW invoice
        $invoice->id_cust_order = $data['id_cust_order'];
        $invoice->ordernr_bol = $data['ordernr_bol'];
        if(!$invoice->id_cust_order)
        {
            $invoice->id_cust_order = 0;
        }
     //  dd($data);
        $invoice->total_shipping_cost_exl_btw = $data['total_shipping_cost_exl_btw']; 
        $invoice->total_shipping_btw_procent = $data['total_shipping_btw_procent'];
        $invoice->total_shipping_exl_btw = $data['total_shipping_exl_btw'];
        $invoice->total_shipping_incl_btw = $data['total_shipping_incl_btw'];
        $invoice->total_products_exl_btw = $data['total_products_exl_btw'];
        $invoice->total_products_incl_btw = $data['total_products_incl_btw'];
        $invoice->total_paid = $data['total_paid']; 
        $invoice->total_ikp_cz_exl_btw = $data['total_ikp_cz_exl_btw'];
        $invoice->total_costs_bol_exl_btw = $data['total_costs_bol_exl_btw'];
        $invoice->total_invoice_exl_btw = $data['total_invoice_exl_btw'];
        $invoice->total_invoice_incl_btw = $data['total_invoice_incl_btw'];
        $invoice->total_wrapping_exl_btw = $data['total_wrapping_exl_btw'];
        $invoice->total_wrapping_incl_btw = $data['total_wrapping_incl_btw'];
        $invoice->total_wrapping_cost_ex_btw = $data['total_wrapping_cost_exl_btw'];
        $invoice->netto_margin_ex_btw = $data['netto_margin_ex_btw'];
        $invoice->invoice_type = $data['invoice_type'];
        $invoice->payment_method = $data['payment_method'];

    // Fields to fill ONLY FOR NEW invoices
        if(!$id_cust_invoice) // Only New Invoice
        {
            $invoice->id_customer = $data['id_customer'];
            $customer = PsCustomer::find($invoice->id_customer);
            $address = PsAddress::where('id_customer',  $invoice->id_customer )->first();
            $invoice->company_name = $address->company;
            $invoice->customer_name = $customer->lastname;
            $invoice->customer_first_name = $customer->firstname;
            $invoice->customer_street_nr = $address->address1;
            $invoice->customer_city = $address->city;
            $invoice->customer_postal_code = $address->postcode;
            $invoice->customer_vat_number = $address->vat_number;
            $invoice->customer_phone = $address->phone_mobile;
            $invoice->customer_email = $customer->email;

            if($address->id_country == 3)
            {
                $invoice->customer_country = 'BE';
            }
            elseif($address->id_country == 13 )
            {
                $invoice->customer_country = 'NL';
            }
            elseif($address->id_country == 8 )
            {
                $invoice->customer_country = 'FR';  
            }
            $invoice->invoice_date = date("Y/m/d");
            $invoice->order_date = $data['order_date'];;  
        }
        DB::beginTransaction();
        try 
        {
        // Get invoice Number
            if($id_cust_invoice)    // If edit existing invoice
            {
                $currentInvoiceNr = $invoice->id_cust_invoice;
            }else{                 // when create new invoice
                $lastInvoiceNr = $invoice::orderBy('id_cust_invoice', 'desc')->first()->id_cust_invoice;
                $invoice->id_cust_invoice = $lastInvoiceNr + 1;
                $currentInvoiceNr = $lastInvoiceNr + 1; 
            }
            $invoice->save();    // Save invoice (header)
            $invoice=CzCustInvoice::find($currentInvoiceNr);
        //2) Create invoice rows 
             $id_products = $data['id_product'];
             $quantitys = $data['quantity'];
             $unitPrices = $data['unitPrice'];
             $rowPricesEx = $data['rowPriceEx'];
             $rowPricesIn = $data['rowPriceIncl'];
             $ikPrices = $data['ikPrice'];
             $rowIkPrices = $data['rowIkPrice'];
             $rowVatProcents = $data['rowVatProcent'];
             if (array_key_exists('invoiceRowId',$data)) 
             {
                $invoiceRowId = $data['invoiceRowId']; 
             }
             $bolFixCost = $data['bolFixCost'];
             $bolProcentCost = $data['bolProcentCost'];
             $rowBolCost = $data['rowBolCost'];  
            foreach($id_products as $index => $id_product)     // Loop InvoiceRow arrays and make invoice rows
            {
                if(isset($invoiceRowId))
                {
                    $invoiceRow = CzCustInvoiceDetail::findornew($invoiceRowId[$index]);
                }else{
                    $invoiceRow = new CzCustInvoiceDetail;
                }
                $invoiceRow->id_cz_cust_invoice = $invoice->id_cz_cust_invoice;
                $invoiceRow->id_cust_invoice = $invoice->id_cust_invoice;
                $invoiceRow->id_product = $id_product;
                $productInRow = CzProduct::where('id_product',$invoiceRow->id_product)->first();
                $invoiceRow->product_reference = $productInRow->reference;
                $invoiceRow->product_suppl_reference = $productInRow->product_supplier_reference;
                $invoiceRow->ean_product = $productInRow->ean13;
                $invoiceRow->product_descr = $productInRow->name;
                $invoiceRow->id_supplier = $productInRow->id_supplier;
                $invoiceRow->quantity = $quantitys[$index];
                $invoiceRow->product_unit_price_incl_vat = $unitPrices[$index];
                $invoiceRow->product_ikp_price_cz_ex_vat = $ikPrices[$index];
                $invoiceRow->product_total_ikp_cz_ex_vat = $rowIkPrices[$index];
                $invoiceRow->product_total_price_ex_vat = $rowPricesEx[$index];
                $invoiceRow->vat_procent = $rowVatProcents[$index]; 
                $invoiceRow->product_total_price_incl_vat = $rowPricesIn[$index];
                $invoiceRow->product_unit_price_ex_vat = round(($invoiceRow->product_unit_price_incl_vat / (($invoiceRow->vat_procent / 100) + 1)),2);
                $invoiceRow->bol_procent_cost = $bolProcentCost[$index];
                $invoiceRow->bol_fix_cost = $bolFixCost[$index];
                if(!$rowBolCost[$index])
                {
                    $invoiceRow->row_bol_cost_amount = 0;

                }else{
                    $invoiceRow->row_bol_cost_amount = $rowBolCost[$index];
                }
                $invoiceRow->save();          
            }
            DB::commit();
            $notInvoiced = 0;
        } catch (\Exception $e) 
        {      // something went wrong
            $notInvoiced = 1;
            DB::rollback();
            throw $e;
        } 
        $notification = array(
	        'message' => 'Factuur succesvol Opgeslagen !',
            'alert-type' => 'success'
        );
        return redirect('/verkopen/facturen')->with($notification);
    } // end function save

    public function credit(Request $request, $id_cust_invoice)
    {
        $invoice = CzCustInvoice::find($id_cust_invoice);
        $invoiceRows = CzCustInvoice::find($id_cust_invoice)->invoiceDetails;

        $credit = new CzCustInvoice;
        $credit->id_cust_order = $invoice->id_cust_order;
        $credit->id_customer = $invoice->id_customer;
        $credit->customer_name = $invoice->customer_name;
        $credit->customer_first_name = $invoice->customer_first_name;
        $credit->customer_street_nr = $invoice->customer_street_nr;
        $credit->customer_city = $invoice->customer_city;
        $credit->customer_postal_code = $invoice->customer_postal_code;
        $credit->customer_vat_number = $invoice->customer_vat_number;
        $credit->customer_country = $invoice->customer_country;
        $credit->customer_phone = $invoice->customer_phone;
        $credit->customer_email = $invoice->customer_email;;
        $credit->customer_phone_mobile = $invoice->customer_phone_mobile;
        $credit->company_name = $invoice->company_name;
        $credit->invoice_date = date("Y/m/d");
        $credit->order_date = $invoice->order_date;
        $credit->order_reference = $invoice->order_reference;
        if($invoice->payment_method == 'Via bol.com')
        {
            $credit->payment_method = 'Via bol.com';
        }
        else
        {
            $credit->payment_method = 'Via Cool-Zawadi';
        }
        $credit->total_shipping_btw_procent = $invoice->total_shipping_btw_procent;
        $credit->total_shipping_exl_btw = $invoice->total_shipping_exl_btw;
        $credit->total_shipping_incl_btw = $invoice->total_shipping_incl_btw;
        $credit->total_products_exl_btw = 0 - $invoice->total_products_exl_btw;
        $credit->total_products_incl_btw = 0 - $invoice->total_products_incl_btw;
        $credit->total_paid = 0 - $invoice->total_paid;
        $credit->total_ikp_cz_exl_btw = 0 - $invoice->total_ikp_cz_exl_btw;
        $credit->total_costs_bol_exl_btw = 0 - $invoice->total_costs_bol_exl_btw;
        $credit->total_shipping_cost_exl_btw = $invoice->total_shipping_cost_exl_btw;
        $credit->total_invoice_exl_btw = 0 - $invoice->total_invoice_exl_btw;
        $credit->total_invoice_incl_btw = 0 - $invoice->total_invoice_incl_btw;
        $credit->total_wrapping_exl_btw = 0 - $invoice->total_wrapping_exl_btw ;
        $credit->total_wrapping_incl_btw = 0 - $invoice->total_wrapping_incl_btw ;
        $credit->invoice_type = $invoice->invoice_type;
        $credit->ordernr_bol = $invoice->ordernr_bol;
        $credit->netto_margin_ex_btw = (0 - $invoice->netto_margin_ex_btw) -  $credit->total_shipping_cost_exl_btw;
        $credit->total_wrapping_cost_ex_btw = 0;
        $lastInvoiceNr = $credit::orderBy('id_cust_invoice', 'desc')->first()->id_cust_invoice;
        $credit->id_cust_invoice = $lastInvoiceNr + 1;
        $credit->id_credit_invoice = $invoice->id_cust_invoice;
        DB::beginTransaction();
        try 
        {
            $credit->save();    // Save invoice (header)
            $credit=CzCustInvoice::find($lastInvoiceNr + 1);
            // 2) Create invoice rows & change to invoice field in products
            foreach ($invoiceRows as $invoiceRow)     // Loop over order rows and make invoice rows
            {
                $creditRow = new CzCustInvoiceDetail;
                $creditRow->id_cz_cust_invoice = $credit->id_cz_cust_invoice;
                $creditRow->id_cust_invoice = $credit->id_cust_invoice; 
                $creditRow->id_product = $invoiceRow->id_product;
                $creditRow->product_reference = $invoiceRow->product_reference;
                $creditRow->product_suppl_reference = $invoiceRow->product_suppl_reference;
                $creditRow->product_descr = $invoiceRow->product_descr;
                $creditRow->quantity = 0 - $invoiceRow->quantity;
                $creditRow->product_unit_price_ex_vat = $invoiceRow->product_unit_price_ex_vat;
                $creditRow->product_ikp_price_cz_ex_vat = $invoiceRow->product_ikp_price_cz_ex_vat;
                $creditRow->product_total_ikp_cz_ex_vat = 0-$invoiceRow->product_total_ikp_cz_ex_vat;
                $creditRow->product_total_price_ex_vat = 0-$invoiceRow->product_total_price_ex_vat;
                $creditRow->vat_procent = $invoiceRow->vat_procent;
                $creditRow->product_total_price_incl_vat = 0-$invoiceRow->product_total_price_incl_vat;
                $creditRow->ean_product = $invoiceRow->ean_product;
                $creditRow->id_supplier = $invoiceRow->id_supplier;
                $creditRow->product_unit_price_incl_vat = $invoiceRow->product_unit_price_incl_vat;
                $creditRow->bol_procent_cost = $invoiceRow->bol_procent_cost;
                $creditRow->bol_fix_cost = $invoiceRow->bol_fix_cost;
                $creditRow->row_bol_cost_amount = 0 - $invoiceRow->row_bol_cost_amount;
                $creditRow->save();
            } // en foreach
            $invoice->id_credit_invoice = $credit->id_cust_invoice;
            $invoice->save();
            DB::commit(); 
        } 
        catch (\Exception $e) 
        {      // something went wrong
            $notInvoiced = 1;
            echo'NIE GELUKT';
            DB::rollback();
            return $e;
        } 
        $notification = array(
	        'message' => 'Factuur werd succesvol gecrediteerd !',
            'alert-type' => 'success'
        );
        return redirect('/verkopen/facturen/print/'.  $invoice->id_credit_invoice)->with($notification);


    }
} // end class