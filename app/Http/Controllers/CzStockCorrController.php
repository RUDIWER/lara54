<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\CzStockCorr;
use App\Models\CzStockCorrDetail;
use App\Models\CzParameter;
use App\Models\CzProduct;
use Dhtmlx\Connector\GridConnector;
use Illuminate\Http\Request;

class CzStockCorrController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function corrections()
    {
        return view('voorraad.correcties.correcties');
    }

    public function stockcorrData() {
        $connector = new GridConnector(null, "PHPLaravel");
        $connector->configure(
            new CzStockcorr(),
            "id",
            "id_cz_stock_corr,date_corr,description_corr"
        );
        $connector->render();
    }

    public function create() 
    {
        $stockCorr = new CzStockCorr();
        $stockCorrDetails = 0;
        $param = CzParameter::find(1);
        $products = CzProduct::all();
        $isNew = 1;
        return view('voorraad.correcties.edit', compact('products','stockCorr', 'stockCorrDetails','isNew','param'));
    }

    public function save(Request $request, $id_cz_stock_corr)
    {
        $data = $request->all();
  //     dd($data);
        $stockCorr = CzStockCorr::findornew($id_cz_stock_corr); 
         $stockCorr->user_name_corr = $data['user_name_corr'];
        $stockCorr->date_corr = $data['date_corr'];
        $stockCorr->description_corr = $data['description_corr'];

        DB::beginTransaction();
        try 
        {
        // Get invoice Number
            if($id_cz_stock_corr)    // If edit existing invoice
            {
                $currentStockCorrId = $stockCorr->id_cz_stock_corr;
            }else{                 // when create new invoice
                $lastStockCorrId = $stockCorr::orderBy('id_cz_stock_corr', 'desc')->first()->id_cz_stock_corr;
                $stockCorr->id_cz_stock_corr = $lastStockCorrId + 1;
                $currentStockCorrId = $lastStockCorrId + 1; 
            }
            $stockCorr->save();    // Save StockCorr (header)
            $stockCorr=CzStockCorr::find($currentStockCorrId);
        //   Create Stock correction Details
            $id_products = $data['id_product'];
            $quantitys = $data['quantity'];
            foreach($id_products as $index => $id_product)     // Loop over stock corrections arrays and make db rows
            {
                $stockCorrRow = new CzStockCorrDetail;
                $stockCorrRow->id_cz_stock_corr = $stockCorr->id_cz_stock_corr;
                $stockCorrRow->date_corr = $stockCorr->date_corr;
                $stockCorrRow->id_product = $id_product;
                $productInRow = CzProduct::where('id_product',$stockCorrRow->id_product)->first();
                $stockCorrRow->product_descr = $productInRow->name;
                $stockCorrRow->ean_product = $productInRow->ean13;
                $stockcorrRow->product_reference = $productInRow->reference;
                $stockCorrRow->product_supplier_reference = $productInRow->supplier_reference;
                $stockCorrRow->quantity_corr = $quantitys[$index];
                dd($stockCorrRow);
            }
      //      DB::commit();
        } catch (\Exception $e) 
        {      // something went wrong
            $noStockCorr = 1;
            DB::rollback();
            return $e;
        } 
        $notification = array(
	        'message' => 'Voorraad correcties werden succesvol uitgevoerd ! ',
            'alert-type' => 'success'
        );
        return redirect('/voorraad/correcties')->with($notification);

    }

}
