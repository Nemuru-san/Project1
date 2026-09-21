<?php

/*
|--------------------------------------------------------------------------
| Daftar izin modul & otorisasi
|--------------------------------------------------------------------------
| Kunci izin modul mengikuti nama route halaman (kecuali alias di bawah).
| Grup yang berakhiran " - Otorisasi" berisi izin aksi (approve/post/delete/...),
| bukan izin akses halaman. Dipakai oleh Peran Pengguna, middleware akses modul,
| dan sidebar.
*/

return [

    'groups' => [
        'Platform' => [
            'dashboard' => 'Dashboard',
        ],

        'Pembelian - Master' => [
            'purchases.master.supplier' => 'Supplier',
        ],

        'Pembelian - Transaksi' => [
            'purchases.transaction.purchase-order' => 'Pesanan Pembelian',
            'purchases.transaction.good-receive' => 'Penerimaan Barang',
            'purchases.transaction.purchase-invoice' => 'Faktur Pembelian',
        ],

        'Pesanan Pembelian - Otorisasi' => [
            'purchases.transaction.purchase-order.approve' => 'Setujui / Ubah Status',
            'purchases.transaction.purchase-order.delete' => 'Hapus',
        ],

        'Penerimaan Barang - Otorisasi' => [
            'purchases.transaction.good-receive.receive' => 'Terima / Ubah Status',
            'purchases.transaction.good-receive.delete' => 'Hapus',
        ],

        'Faktur Pembelian - Otorisasi' => [
            'purchases.transaction.purchase-invoice.post' => 'Posting',
            'purchases.transaction.purchase-invoice.delete' => 'Hapus',
        ],

        'Pembelian - Retur' => [
            'purchases.return.purchase-return' => 'Retur Pembelian',
            'purchases.return.purchase-return.confirm' => 'Konfirmasi Retur Pembelian',
            'purchases.return.purchase-return-invoice' => 'Faktur Retur Pembelian',
            'purchases.return.purchase-return-invoice.post' => 'Posting Faktur Retur Pembelian',
        ],

        'Pembelian - Laporan' => [
            'purchases.report.unfinished-purchase-order' => 'PO Belum Selesai',
            'purchases.report.unfinished-purchase-invoice' => 'Faktur Pembelian Belum Lunas',
        ],

        'Persediaan - Master' => [
            'inventory.product.productMaster' => 'Master Produk',
            'inventory.product.productCategory' => 'Kategori Produk',
            'inventory.product.uom' => 'Satuan',
            'inventory.product.warehouse' => 'Gudang',
        ],

        'Persediaan - Transaksi' => [
            'inventory.transaction.transfer-stock' => 'Transfer Stok',
            'inventory.transaction.adjustment-in' => 'Penyesuaian Stok Masuk',
            'inventory.transaction.adjustment-out' => 'Penyesuaian Stok Keluar',
        ],

        'Transfer Stok - Otorisasi' => [
            'inventory.transaction.transfer-stock.approve' => 'Setujui',
            'inventory.transaction.transfer-stock.delete' => 'Hapus',
        ],

        'Penyesuaian Stok Masuk - Otorisasi' => [
            'inventory.transaction.adjustment-in.approve' => 'Setujui',
            'inventory.transaction.adjustment-in.delete' => 'Hapus',
        ],

        'Penyesuaian Stok Keluar - Otorisasi' => [
            'inventory.transaction.adjustment-out.approve' => 'Setujui',
            'inventory.transaction.adjustment-out.delete' => 'Hapus',
        ],

        'Persediaan - Laporan' => [
            'inventory.report.stock-balance' => 'Saldo Stok',
            'inventory.report.stock-card' => 'Kartu Stok',
            'inventory.report.stock-movement' => 'Pergerakan Stok',
        ],

        'Penjualan - Master' => [
            'sales.master.customer' => 'Pelanggan',
            'sales.master.customer-address-code' => 'Kode Alamat Pelanggan',
            'sales.master.salesman' => 'Tenaga Penjualan',
        ],

        'Penjualan - Transaksi' => [
            'sales.transaction.salesCanvas' => 'Penjualan Kanvas',
            'sales.transaction.salesPreOrder' => 'Pesanan Awal',
            'sales.transaction.salesOrder' => 'Pesanan Penjualan',
            'sales.transaction.delivery-order' => 'Surat Jalan',
            'sales.transaction.sales-invoice' => 'Faktur Penjualan',
        ],

        'Penjualan Kanvas - Otorisasi' => [
            'sales.transaction.salesCanvas.confirm' => 'Konfirmasi',
            'sales.transaction.salesCanvas.convert' => 'Konversi ke Sales Order',
            'sales.transaction.salesCanvas.delete' => 'Hapus / Pulihkan',
        ],

        'Pesanan Penjualan - Otorisasi' => [
            'sales.transaction.salesOrder.verify' => 'Konfirmasi',
        ],

        'Faktur Penjualan - Otorisasi' => [
            'sales.transaction.sales-invoice.confirm' => 'Konfirmasi',
        ],

        'Pesanan Awal - Otorisasi' => [
            'sales.transaction.salesPreOrder.confirm' => 'Konfirmasi',
            'sales.transaction.salesPreOrder.convert' => 'Konversi ke Sales Order',
            'sales.transaction.salesPreOrder.delete' => 'Hapus / Pulihkan',
        ],

        'Penjualan - Retur' => [
            'sales.return.sales-return' => 'Retur Penjualan',
            'sales.return.sales-return.confirm' => 'Konfirmasi Retur Penjualan',
            'sales.return.sales-return-invoice' => 'Faktur Retur Penjualan',
            'sales.return.sales-return-invoice.post' => 'Posting Faktur Retur Penjualan',
        ],

        'Penjualan - Laporan' => [
            'sales.report.po-outstanding' => 'SO Belum Selesai',
            'sales.report.invoice-outstanding' => 'Faktur Penjualan Belum Lunas',
        ],

        'Keuangan - Master' => [
            'finance.master.chart-of-accounts' => 'Daftar Akun',
            'finance.master.bank-accounts' => 'Rekening Bank',
            'finance.master.payment-terms' => 'Termin Pembayaran',
        ],

        'Keuangan - Transaksi' => [
            'finance.transaction.ap-payment' => 'Pembayaran Utang',
            'finance.transaction.expense' => 'Pengeluaran',
            'finance.transaction.ar-dp-payment' => 'Penerimaan DP Pelanggan',
            'finance.transaction.ar-payment' => 'Pembayaran Piutang',
        ],

        'Keuangan - Laporan' => [
            'finance.report.journal-entry' => 'Entri Jurnal',
            'finance.report.general-ledger' => 'Buku Besar',
            'finance.report.trial-balance' => 'Neraca Saldo',
            'finance.report.profit-loss' => 'Laba Rugi',
            'finance.report.balance-sheet' => 'Neraca',
        ],

        'Pengguna' => [
            'user.action.user' => 'Pengguna',
            'user.action.role' => 'Peran Pengguna',
        ],
    ],

    /*
    | Route halaman yang nama-nya tidak sama persis dengan kunci izin modulnya.
    */
    'route_aliases' => [
        'sales.transaction.deliveryOrder' => 'sales.transaction.delivery-order',
        'sales.transaction.salesInvoice' => 'sales.transaction.sales-invoice',
    ],

    /*
    | Akhiran route turunan (print/preview) yang mengikuti izin halaman induknya.
    */
    'route_suffixes' => ['.print', '.view', '.thermal-print'],

];
