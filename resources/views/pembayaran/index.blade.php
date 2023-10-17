@extends('layouts.main')

@section('content')
    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-header bg-primary">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="card-title fw-semibold text-white">Pembayaran</h5>
                        </div>
                        <div class="col-lg-6">
                            <button type="button" class="btn btn-danger float-end" id="refresh"> Reset Form</button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div id="alert-success" class="alert alert-success" style="display: none" role="alert">
                        Tagihan air berhasil dibayar!
                    </div>
                    <div id="alert-error" class="alert alert-danger" style="display: none" role="alert">
                        Error saat melakukan Transaksi
                    </div>
                    
                    
                    <form id="pembayaran-form">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="mb-3">
                                    <label for="periode_id" class="form-label">Pilih Periode Pemakaian</label>
                                    <select class="form-control" name="periode_id" id="periode_id" style="width: 100%;">
                                        @foreach ($periodes as $periode)
                                            <option value="{{ $periode->id }}">{{ $periode->periode }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="user_id" class="form-label">Pilih Nama Pelanggan</label>
                                    <select class="js-example-basic-single" name="user_id" style="width: 100%;">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->no_pelanggan }} | {{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tgl_bayar" class="form-label">Tanggal Hari Ini</label>
                                    <input type="text" class="form-control" name="tgl_bayar" id="tgl_bayar" readonly>
                                </div>
                                
                                <div class="row">
                                    <input type="hidden" id="pemakaian_id" name="pemakaian_id">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="penggunaan_awal" class="form-label">Penggunaan Awal</label>
                                            <input type="number" class="form-control" name="penggunaan_awal" id="penggunaan_awal" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="penggunaan_akhir" class="form-label">Penggunaan Akhir</label>
                                            <input type="number" class="form-control" name="penggunaan_akhir" id="penggunaan_akhir" disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label for="jumlah_penggunaan" class="form-label">Total Penggunaan</label>
                                            <input type="number" class="form-control" name="jumlah_penggunaan" id="jumlah_penggunaan" disabled> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card">
                                    <div class="card-body bg-warning">
                                        <h3><b>Sub-Total : Rp. <span id="jumlah_pembayaran"></span></b></h3>
                                    </div>
                                </div>

                                <table style="width: 100%" class="mb-3" class="table">
                                    <tr>
                                        <th><b>Deskripsi</b></th>
                                        <th><b>Jumlah</b></th>
                                    </tr>
                                    <tr>
                                        <td>Penggunaan m³</td>
                                        <td><span id="detail_penggunaan"></span> m³</td>
                                    </tr>
                                    <tr>
                                        <td>Tarif per m³</td>
                                        <td>Rp. <span id="m3"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Tarif Beban</td>
                                        <td>Rp. <span id="beban"></span></td>
                                    </tr>
                                    <tr>
                                        <td>Denda</td>
                                        <td>
                                            Rp. <span id="denda"></span>
                                        </td>
                                    </tr>
                                    
                                    <tr class="bg-warning text-black mt-4">
                                        <td class="p-2">Total</td>
                                        <td>Rp. <span id="detail_pembayaran"></span></td>
                                    </tr>
                                </table>

                                <div class="row mt-5">
                                    <div class="mb-3">
                                        <label for="uang_cash" class="form-label">Masukan uang Pelanggan</label>
                                        <input type="number" class="form-control" name="uang_cash" id="uang_cash">
                                    </div>
                                    <div class="mb-3">
                                        <label for="kembalian" class="form-label">Kembalian</label>
                                        <input type="number" class="form-control" name="kembalian" id="kembalian" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary m-1 float-end" id="simpan-button">Bayar</button>
                    </form>
                   
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh button -->
    <script>
        document.getElementById('refresh').addEventListener('click', function() {
            window.location.replace('/pembayaran');
        });
    </script>

    <!-- Generate Tanggal Hari Ini -->
    <script>
        var today   = new Date();
        var year    = today.getFullYear();
        var month   = (today.getMonth() + 1).toString().padStart(2, '0');
        var day     = today.getDate().toString().padStart(2, '0');

        var formattedDate = year + '-' + month + '-' + day;
        document.getElementById('tgl_bayar').value = formattedDate;
    </script>
    
    <!-- Select2 & Autocomplet-->
    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2();

            $('.js-example-basic-single').change(function(){
                var user_id     = $(this).val();
                var periode_id  = $('#periode_id').val();

                $.ajax({
                    url: '/pembayaran/get-data/' + user_id + '/' + periode_id,
                    type: 'GET',
                    success: function(data){
                        $('pemakaian_id').val(data.id);
                        $('penggunaan_awal').val(data.penggunaan_awal);
                        $('penggunaan_akhir').val(data.penggunaan_akhir);
                        $('jumlah_penggunaan').val(data.jumlah_penggunaan);
                        $('jumlah_pembayaran').val(data.jumlah_pembayaran);

                        var id                  = parseFloat(data.id);
                        var penggunaan_awal     = parseFloat(data.penggunaan_awal) || 0;
                        var penggunaan_akhir    = parseFloat(data.penggunaan_akhir) || 0;
                        var jumlah_penggunaan   = parseFloat(data.jumlah_penggunaan) || 0;
                        var jumlah_pembayaran   = parseFloat(data.jumlah_pembayaran) || 'Tidak Ada Tagihan !';

                        $('#pemakaian_id').val(id);
                        $('#penggunaan_awal').val(penggunaan_awal);
                        $('#penggunaan_akhir').val(penggunaan_akhir);
                        $('#jumlah_penggunaan').val(jumlah_penggunaan);
                        $('#jumlah_pembayaran').text(jumlah_pembayaran);
                        $('#detail_penggunaan').text(jumlah_penggunaan);
                        $('#detail_pembayaran').text(jumlah_pembayaran);

                        $.ajax({
                            url: '/tarif/get-data/' + user_id, 
                            type: 'GET',
                            success: function(tarifData){
                                $('#m3').val(tarifData.m3);
                                $('#beban').val(tarifData.beban);
                                $('#denda').val(tarifData.denda);

                                var m3      = parseFloat(tarifData.m3);
                                var beban   = parseFloat(tarifData.beban);
                                var denda   = parseFloat(tarifData.denda);

                                var tanggal_batas_bayar = new Date(data.batas_bayar);
                                var tgl_bayar           = new Date();

                                if (tgl_bayar > tanggal_batas_bayar) {
                                    var selisihBulan    = calculateMonthDifference(tgl_bayar, tanggal_batas_bayar);
                                    var totalDenda      = selisihBulan * 5000;
                                    var totalPembayaran = (jumlah_penggunaan * m3) + beban + totalDenda;

                                    $('#denda').text(totalDenda);
                                    $('#detail_pembayaran').text(totalPembayaran);
                                    $('#jumlah_pembayaran').text(totalPembayaran);
                                } else {
                                    $('#denda').text('0');
                                    $('#detail_pembayaran').text(totalPembayaran);
                                    $('#jumlah_pembayaran').text(totalPembayaran);
                                }

                                $('#m3').text(m3);
                                $('#beban').text(beban);
                            }
                        });

                        function calculateMonthDifference(date1, date2) {
                            var diff = (date1.getFullYear() - date2.getFullYear()) * 12;
                            diff -= date2.getMonth();
                            diff += date1.getMonth();
                            return diff <= 0 ? 0 : diff;
                        }
                    }
                });

            });
        });
    </script>

    <!-- Update kembalian -->
    <script>
        $(document).ready(function() {
            function updateKembalian() {
                var uangCash = parseFloat($('#uang_cash').val());
                var jumlahPembayaran = parseFloat($('#jumlah_pembayaran').text());
        
                if (!isNaN(uangCash) && !isNaN(jumlahPembayaran)) {
                    var kembalian = uangCash - jumlahPembayaran;
                    $('#kembalian').val(kembalian.toFixed(2));
                } else {
                    $('#kembalian').val("");
                }
            }

            $('#uang_cash').on('input', function() {
                updateKembalian();
            });
            updateKembalian();
        });
    </script>

    <!-- Proses bayar -->
    <script>
        $(document).ready(function(){
            $('#simpan-button').click(function(){
                var formData = $('#pembayaran-form').serialize();

                $.ajax({
                    type: 'POST',
                    url: '/pembayaran',
                    data: formData,
                    success: function(response){
                        $('#alert-success').show();
                    },
                    error: function(error) {
                        $('#alert-error').show();
                    }
                });
            });
        });
    </script>

@endsection