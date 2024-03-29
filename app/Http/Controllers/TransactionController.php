<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    function index()
    {
        $transactions = Transaction::all();
        $assets       = Asset::all();
        $customers    = Customer::all();
        return view('transaction.index', compact('transactions', 'assets', 'customers'));
    }

    function show(Transaction $transaction) {
        return view('transaction.detail', compact('transaction'));
    }

    function store(Request $request)
    {
        //check asset's stock
        $asset = Asset::find($request->asset_id);

        if ($request->quantity > $asset->stock) {
            alert()->error('Maaf...', 'Asset yang dipinjam tidak memiliki stok yang dibutuhkan.');
        }

        DB::beginTransaction();

        try {

            // creating transaction
            $transaction = new Transaction($request->except('_token', 'total_price'));

            $total = intval(implode('', explode(',', $request->total_price)));

            $transaction->total_price = $total;

            $transaction->save();

            // updating stock on asset
            $asset->stock -= $request->quantity;

            $asset->save();

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            alert()->error('Maaf...', 'Ada Kesalahan saat menyimpan data Transaksi.');

            return back();
        }

        alert()->success('Berhasil!', 'Transaksi Telah Berhasil Disimpan!');

        return redirect()->route('transactions.index');
    }

    function update(Request $request, Transaction $transaction)
    {
        $transaction->status = $request->status;

        if ($request->status == 'telah dikembalikan') {
            $asset         = $transaction->asset;
            $asset->stock += $transaction->quantity;

            $asset->save();
        }

        $transaction->save();


        alert()->success('Berhasil!', 'Stasus Transaksi Telah Berhasil Dirubah!');

        return redirect()->route('transactions.index');
    }

    function destroy(Transaction $transaction)
    {
        $transaction->delete();

        alert()->success('Berhasil!', 'Transaksi Telah Berhasil Dihapus!');

        return redirect()->route('transactions.index');
    }
}
