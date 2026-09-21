<?php

/*
|--------------------------------------------------------------------------
| Struktur menu sidebar
|--------------------------------------------------------------------------
| Setiap item merujuk nama route halaman; izin modulnya diturunkan dari nama
| route tersebut (lihat App\Http\Middleware\EnsureModuleAccess), sehingga item
| yang tidak boleh diakses pengguna otomatis disembunyikan.
*/

return [
    [
        'heading' => 'Pembelian',
        'icon' => 'credit-card',
        'prefix' => 'purchases.',
        'groups' => [
            ['heading' => 'Master', 'prefix' => 'purchases.master.', 'items' => [
                ['label' => 'Supplier', 'route' => 'purchases.master.supplier'],
            ]],
            ['heading' => 'Transaksi', 'prefix' => 'purchases.transaction.', 'items' => [
                ['label' => 'Pesanan Pembelian', 'route' => 'purchases.transaction.purchase-order'],
                ['label' => 'Penerimaan Barang', 'route' => 'purchases.transaction.good-receive'],
                ['label' => 'Faktur Pembelian', 'route' => 'purchases.transaction.purchase-invoice'],
            ]],
            ['heading' => 'Retur', 'prefix' => 'purchases.return.', 'items' => [
                ['label' => 'Retur Pembelian', 'route' => 'purchases.return.purchase-return'],
                ['label' => 'Faktur Retur Pembelian', 'route' => 'purchases.return.purchase-return-invoice'],
            ]],
            ['heading' => 'Laporan', 'prefix' => 'purchases.report.', 'items' => [
                ['label' => 'PO Belum Selesai', 'route' => 'purchases.report.unfinished-purchase-order'],
                ['label' => 'Faktur Pembelian Belum Lunas', 'route' => 'purchases.report.unfinished-purchase-invoice'],
            ]],
        ],
    ],
    [
        'heading' => 'Persediaan',
        'icon' => 'rectangle-stack',
        'prefix' => 'inventory.',
        'groups' => [
            ['heading' => 'Master Persediaan', 'prefix' => 'inventory.product.', 'items' => [
                ['label' => 'Master Produk', 'route' => 'inventory.product.productMaster'],
                ['label' => 'Kategori Produk', 'route' => 'inventory.product.productCategory'],
                ['label' => 'Satuan', 'route' => 'inventory.product.uom'],
                ['label' => 'Gudang', 'route' => 'inventory.product.warehouse'],
            ]],
            ['heading' => 'Transaksi Persediaan', 'prefix' => 'inventory.transaction.', 'items' => [
                ['label' => 'Transfer Stok', 'route' => 'inventory.transaction.transfer-stock'],
                ['label' => 'Penyesuaian Stok Masuk', 'route' => 'inventory.transaction.adjustment-in'],
                ['label' => 'Penyesuaian Stok Keluar', 'route' => 'inventory.transaction.adjustment-out'],
            ]],
            ['heading' => 'Laporan', 'prefix' => 'inventory.report.', 'items' => [
                ['label' => 'Saldo Stok', 'route' => 'inventory.report.stock-balance'],
                ['label' => 'Kartu Stok', 'route' => 'inventory.report.stock-card'],
                ['label' => 'Pergerakan Stok', 'route' => 'inventory.report.stock-movement'],
            ]],
        ],
    ],
    [
        'heading' => 'Penjualan',
        'icon' => 'presentation-chart-line',
        'prefix' => 'sales.',
        'groups' => [
            ['heading' => 'Master', 'prefix' => 'sales.master.', 'items' => [
                ['label' => 'Pelanggan', 'route' => 'sales.master.customer'],
                ['label' => 'Kode Alamat', 'route' => 'sales.master.customer-address-code'],
                ['label' => 'Tenaga Penjualan', 'route' => 'sales.master.salesman'],
            ]],
            ['heading' => 'Transaksi', 'prefix' => 'sales.transaction.', 'items' => [
                ['label' => 'Penjualan Kanvas', 'route' => 'sales.transaction.salesCanvas'],
                ['label' => 'Pesanan Awal', 'route' => 'sales.transaction.salesPreOrder'],
                ['label' => 'Pesanan Penjualan', 'route' => 'sales.transaction.salesOrder'],
                ['label' => 'Surat Jalan', 'route' => 'sales.transaction.deliveryOrder'],
                ['label' => 'Faktur Penjualan', 'route' => 'sales.transaction.salesInvoice'],
            ]],
            ['heading' => 'Retur', 'prefix' => 'sales.return.', 'items' => [
                ['label' => 'Retur Penjualan', 'route' => 'sales.return.sales-return'],
                ['label' => 'Faktur Retur Penjualan', 'route' => 'sales.return.sales-return-invoice'],
            ]],
            ['heading' => 'Laporan', 'prefix' => 'sales.report.', 'items' => [
                ['label' => 'SO Belum Selesai', 'route' => 'sales.report.po-outstanding'],
                ['label' => 'Faktur Penjualan Belum Lunas', 'route' => 'sales.report.invoice-outstanding'],
            ]],
        ],
    ],
    [
        'heading' => 'Keuangan',
        'icon' => 'banknotes',
        'prefix' => 'finance.',
        'groups' => [
            ['heading' => 'Master', 'prefix' => 'finance.master.', 'items' => [
                ['label' => 'Daftar Akun', 'route' => 'finance.master.chart-of-accounts'],
                ['label' => 'Rekening Bank', 'route' => 'finance.master.bank-accounts'],
            ]],
            ['heading' => 'Transaksi', 'prefix' => 'finance.transaction.', 'items' => [
                ['label' => 'Pembayaran Utang', 'route' => 'finance.transaction.ap-payment'],
                ['label' => 'Pengeluaran', 'route' => 'finance.transaction.expense'],
                ['label' => 'Penerimaan DP Pelanggan', 'route' => 'finance.transaction.ar-dp-payment'],
                ['label' => 'Pembayaran Piutang', 'route' => 'finance.transaction.ar-payment'],
            ]],
            ['heading' => 'Laporan', 'prefix' => 'finance.report.', 'items' => [
                ['label' => 'Entri Jurnal', 'route' => 'finance.report.journal-entry'],
                ['label' => 'Buku Besar', 'route' => 'finance.report.general-ledger'],
                ['label' => 'Neraca Saldo', 'route' => 'finance.report.trial-balance'],
                ['label' => 'Laba Rugi', 'route' => 'finance.report.profit-loss'],
                ['label' => 'Neraca', 'route' => 'finance.report.balance-sheet'],
            ]],
        ],
    ],
    [
        'heading' => 'Pengguna',
        'icon' => 'users',
        'prefix' => 'user.',
        'groups' => [
            ['heading' => 'Aksi', 'prefix' => 'user.action.', 'items' => [
                ['label' => 'Pengguna', 'route' => 'user.action.user'],
                ['label' => 'Peran Pengguna', 'route' => 'user.action.role'],
            ]],
        ],
    ],
];
