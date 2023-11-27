<?php

namespace App\Http\Controllers;

use DateTime;
use Midtrans\Snap;
use Midtrans\Config;
use App\Models\Tarif;
use App\Models\Pemakaian;
use App\Models\Pembayaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class CekTagihanPelangganController extends Controller
{
    public function index()
    {
        $user = auth()->user()->id;
        return view('cek-tagihan.index', [
            'tagihans'  => Pemakaian::with(['periode', 'user'])->where('user_id', $user)
                ->where('status', 'belum dibayar')
                ->orderBy('id', 'DESC')
                ->get()
        ]);
    }

    public function detailTagihan($id)
    {
        $tagihan = Pemakaian::with(['periode', 'user'])->find($id);

        return view('cek-tagihan.detail', [
            'tagihan'   => $tagihan,
            'tarif'     => Tarif::first(),
        ]);
    }

    public function bayar(Request $request)
    {
        $pemakaian_id = $request->input('pemakaian_id');
        $subTotal = $request->input('jumlah_pembayaran');

        $existingToken = Pemakaian::where('id', $pemakaian_id)->value('snap_token');

        if ($existingToken) {
            return response()->json(['snapToken' => $existingToken]);
        }

        if ($existingToken) {
            if ($this->isSnapTokenValid($existingToken)) {
                return response()->json(['snapToken' => $existingToken]);
            }
        }

        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = false;
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = array(
            'transaction_details' => array(
                'order_id' => $pemakaian_id,
                'gross_amount' => $subTotal,
            ),
            'customer_details' => array(
                'first_name' => auth()->user()->name,
                'phone' => auth()->user()->no_hp,
            ),
        );

        // Dapatkan Snap Token baru
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        // Update atau simpan Snap Token baru ke dalam database
        Pemakaian::where('id', $pemakaian_id)->update(['snap_token' => $snapToken]);

        return response()->json(['snapToken' => $snapToken]);
    }

    private function isSnapTokenValid($snapToken)
    {
        $midtransBaseUrl = config('midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $url = $midtransBaseUrl . '/v2/snap/token/' . $snapToken;

        $client = new \GuzzleHttp\Client();

        try {
            // Kirim permintaan GET ke API Midtrans untuk memeriksa status Snap Token
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode(config('midtrans.server_key') . ':')
                ]
            ]);

            // Periksa status kode respons
            if ($response->getStatusCode() === 200) {
                // Token masih berlaku
                return true;
            }
        } catch (\Exception $e) {
            // Token sudah tidak berlaku
            return false;
        }

        return false;
    }

    public function callback(Request $request)
    {
        $kd_pembayaran  = 'INV-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $kembalian      = 0;
        $tgl_bayar      = now();
        $denda          = 0;
        $tarif          = Tarif::first();

        $serverKey = config('midtrans.server_key');
        $hashed    = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $denda . $serverKey);

        if ($request->transaction_status == 'capture' or $request->transaction_status == 'settlement' or $request->transaction_status == 'pending') {
            $order_id        = $request->order_id;
            $gross_amount    = $request->gross_amount;

            $pemakaian = Pemakaian::findOrFail($order_id);
            $pemakaian->update(['status' => 'lunas']);

            $tanggal_batas_bayar = new DateTime($pemakaian->batas_bayar);
            $tgl_bayar = new DateTime();

            if ($tgl_bayar > $tanggal_batas_bayar) {
                $selisihBulan    = $this->calculateMonthDifference($tgl_bayar, $tanggal_batas_bayar);
                $dendaPerBulan   = $tarif->denda;
                $denda           = $selisihBulan * $dendaPerBulan;
            }

            $pembayaran     = new Pembayaran();
            $pembayaran->pemakaian_id    = $pemakaian->id;
            $pembayaran->kd_pembayaran   = $kd_pembayaran;
            $pembayaran->tgl_bayar       = $tgl_bayar->format('Y-m-d');
            $pembayaran->uang_cash       = $gross_amount;
            $pembayaran->kembalian       = $kembalian;
            $pembayaran->denda           = $denda;
            $pembayaran->subTotal        = $gross_amount;
            $pembayaran->save();
        }
    }

    private function calculateMonthDifference($date1, $date2)
    {
        if ($date1 instanceof DateTime) {
            $start = $date1;
        } else {
            $start = new DateTime($date1);
        }

        if ($date2 instanceof DateTime) {
            $end = $date2;
        } else {
            $end = new DateTime($date2);
        }

        $interval = $start->diff($end);
        $years = $interval->y;
        $months = $interval->m;

        if ($interval->d > 0) {
            $months += 1;
        }

        return $years * 12 + $months;
    }
}
