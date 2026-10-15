<section class="rab-category" data-budget-category data-category="{{ $category }}">
    <header class="rab-category-heading"><h3 data-category-title>{{ $category }}</h3><button type="button" class="button button--outline admin-button" data-add-category-item>+ Item</button></header>
    <div class="admin-table-wrap rab-table-wrap" tabindex="0" role="region" aria-label="Komponen {{ $category }}">
        <table class="admin-table rab-cost-table">
            <thead><tr><th scope="col">No.</th><th scope="col">Uraian biaya</th><th scope="col">Qty / pengali</th><th scope="col">Frekuensi</th><th scope="col">Harga satuan (Rp)</th><th scope="col" class="rab-money">Jumlah (Rp)</th><th scope="col">Aksi</th></tr></thead>
            <tbody data-category-items>@foreach($categoryItems as $index => $item) @include('admin.budgets.item') @endforeach</tbody>
            <tfoot><tr><th colspan="5" scope="row">Subtotal <span data-category-label>{{ $category }}</span></th><td class="rab-money"><output data-category-total>—</output></td><td></td></tr></tfoot>
        </table>
    </div>
</section>
