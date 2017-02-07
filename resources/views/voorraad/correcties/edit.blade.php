@extends('layouts.app')
@section('content')
<div id="StockCorrApp">
    <div id="isNew" data-field-id="{{$isNew}}" ></div>

    @if ($isNew == 0)
        <form action="/voorraad/correcties/save/{{ $invoice->id_cust_invoice }}" name="stockCorrForm" id="stockCorrForm" method="post">
    @else
        <form action="/voorraad/correcties/save/0" name="stockCorrForm" id="stockCorrForm" method="post">
    @endif
    <input type="hidden" name="_token" id="_token" value="{{ csrf_token() }}">
    <input type="hidden"  id="stand_vat_procent" value="{{ $param->stand_vat_procent }}">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    @if ($isNew == 0)
                        <h4 class="panel-heading">Stockcorrectie wijzigen
                    @else
                        <h4 class="panel-heading">Stock Correctie Toevoegen
                    @endif
                        <div class="btn-group btn-titlebar pull-right">
                            <a href="{{ URL::to('/voorraad/correcties') }}" type="button" class='btn btn-default btn-sm'>Annuleer</a>
                            <input type="submit" class='btn btn-default btn-sm' value="Opslaan">
                        </div>
                    </h4>
                    <div class="panel-body panel-body-form">
                        <div class="form-group">
                            <div class="col-xs-12">
                                <div class="row">
                                    <div class="col-xs-2">
                                        <label class="control-label">Volgnr</label>
                                        @if ($isNew == 0)
                                            <input type="number" class="form-control input-sm" name="id_cz_stock_corr" id="id_cz_stock_corr" readonly tabindex="-1">
                                        @else
                                            <input type="text" class="form-control input-sm" name="id_cz_stock_corr" id="id_cz_stock_corr" value="Nog niet toegekend" readonly tabindex="-1">
                                        @endif                              
                                    </div>
                                    <div class="col-xs-2">                                                                 
                                        <label class="control-label">Gebruiker</label>
                                            @if ($isNew == 1)
                                                <input type="text" class="form-control input-sm" name="user_name_corr" id="" value="{{ Auth::user()->name }}">
                                            @else
                                                <input type="text" class="form-control input-sm" name="user_name_corr" id="" readonly tabindex="-1">
                                            @endif
                                    </div>
                                    <div class="col-xs-4">                                                               
                                        <label class="control-label">Omschrijving</label>
                                            @if ($isNew == 1)
                                                <input type="text" class="form-control input-sm" name="description_corr" id="description_corr">
                                            @else
                                                <input type="text" class="form-control input-sm" name="description_corr" id="description_corr" readonly tabindex="-1">
                                            @endif
                                    </div>


                                    <div class="col-xs-2 pull-right">
                                        <label class="control-label">Datum Correctie</label>
                                        @if ($isNew == 0)
                                            <input type="text" class="form-control input-sm" name="date_corr" id="date_corr" readonly tabindex="-1">
                                        @else
                                            <input type="date" class="form-control input-sm input-required" required name="date_corr" id="date_corr" value="{{ date('Y-m-d') }}" placeholder="Order datum...">
                                        @endif                      
                                    </div>
                                </div> <!-- row -->
                            </div> <!-- class xs-12 -->  
                            <div class="form-group">
                                <div class="stockCorrDetail">
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div id="table" class="table-editable">
                                                <table name="stockCorrTable" id="stockCorrTable" class="table">
                                                    <tr> 
                                                        <th><button type="button" id="addRow"  name="addRow" class="table-add pull-left">+</button></th>
                                                        <th>Ref. / Omschrijving</th>
                                                        <th>Aantal</th>
                                                        <th>Acties</th>
                                                    </tr>
                                                    @if($stockCorrDetails)   
                                                        <?php $rowCount = 1; ?>
                                                        @foreach($invoiceDetails as $invoiceDetail)
                                                            <tr class="original" id="row{{$rowCount}}"> 
                                                                <td class="rowNumber" name="row[]" value="{{$rowCount}}">{{ $rowCount}}</td>
                                                                <td class="col-xs-8">
                                                                    <div class="form-group">
                                                                        <select class="selectpicker productSelector show-tick show-menu-arrow form-control" data-live-search="true" data-style="btn-default btn-sm" data-size="8" name="id_product[]" id="{{$rowCount}}">
                                                                            @foreach ($products as $product)
                                                                                @if($product->id_product == $invoiceDetail->id_product)
                                                                                    <option value="{{ $product->id_product }}" selected="selected">{{ $product->id_product }} / {{$product->name }}</option>
                                                                                @else
                                                                                    <option value="{{ $product->id_product }}">{{ $product->id_product }} / {{ $product->name }}</option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                </td>

                                                                <td class="input-sm col-xs-2"><input type="number" class="form-control input-sm quantity" name="quantity[]" id="quantity{{$rowCount}}" value="{{ $stockCorrDetail->quantity }}"></td>

                                                                 <input type="hidden" class="invoiceRowId" name="invoiceRowId[]" id="invoiceRowId{{$rowCount}}" value="{{ $invoiceDetail->id_cz_cust_invoice_detail }}"></td>
                                                                <td class="input-sm col-xs-2">
                                                                    <button type="button" class="delRow glyphicon glyphicon-remove pull-right"></button>
                                                                </td>
                                                            <tr>
                                                            <?php $rowCount++; ?> 
                                                            <input type="hidden" name="rowCount" id="rowCount" value="{{ $rowCount }}">
                                                        @endforeach
                                                    @endif
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('partials.footer')
 </div>

<script>

$(document).ready(function() {
    var invoiceType = 0
    var standVatProcent = Number($('#stand_vat_procent').val());

    $('#addRow').click(function(){
        rowNumber = Number(($('.rowNumber').length)+1);
        var row = 
            '<tr id="row'+ rowNumber +'">' + 
                '<td class="rowNumber"  name="row[]" value="' + rowNumber + '">'+ rowNumber + '</td>'+
                '<td class="col-xs-8">'+
                    '<div class="form-group">' +
                        '<select class="selectpicker productSelector show-tick show-menu-arrow form-control" data-live-search="true" data-style="btn-default btn-sm btn-required" data-size="8" title="product..." name="id_product[]" id="'+ rowNumber +'"">' +
                            '@foreach ($products as $product)' +
                                '<option value="{{ $product->id_product }}">{{ $product->id_product }} / {{$product->name }}</option>' +
                            '@endforeach' +
                        '</select>' +
                    '</div>'+
                '</td>'+
                '<td class="input-sm col-xs-2"><input type="number" class="form-control input-sm quantity" name="quantity[]" id="quantity' + rowNumber + '"></td>' +

                '<td class="input-sm col-xs-2">' +
                    '<button type="button" class="delRow glyphicon glyphicon-remove pull-right"></button>' +
                '</td>' +
            '</tr>' 
        $('#stockCorrTable').append(row);
        $("#stockCorrTable").find('.selectpicker').last().selectpicker();

        return false;

    });

    $('body').delegate('.delRow','click',function(){
        $(this).parent().parent().remove();

        var rowNumber = 0;
        $('.rowNumber').each(function(){
            rowNumber++;
            $(this).text(rowNumber);
        })
        return false;
    });
});


</script>
@endsection