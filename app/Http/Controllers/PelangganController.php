<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PelangganController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pelanggan.index', [
            'pelanggans'     => User::where('role_id', 3)->orderBy('id', 'DESC')->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pelanggan.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'              => 'required',
            'email'             => 'required|email',
            'no_hp'             => 'required',
            'tgl_pasang'        => 'required',
            'password'          => 'required',
            'confirmPassword'   => 'required|same:password'
        ], [
            'name.required'             => 'Form wajib diisi !',
            'email.required'            => 'Form wajib diisi !',
            'email.email'               => 'Gunakan email yang valid !',
            'no_hp.required'            => 'Form wajib diisi !',
            'tgl_pasang.required'       => 'Form wajib diisi !',
            'password.required'         => 'Form wajib diisi !',
            'confirmPassword.required'  => 'Form wajib diisi !',
            'confirmPassword.same'      => 'Konfirmasi password tidak sama !'  
        ]);
    
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
    
        $last_no_pelanggan = User::max('no_pelanggan');
        $last_no_pelanggan = str_replace('PAM', '', $last_no_pelanggan);
        $last_no_pelanggan++;
    
        $new_no_pelanggan = 'PAM' . str_pad($last_no_pelanggan, 4, '0', STR_PAD_LEFT);
    
        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'no_hp'         => $request->no_hp,
            'tgl_pasang'    => $request->tgl_pasang,
            'password'      => $request->password,
            'no_pelanggan'  => $new_no_pelanggan
        ]);
    
        return redirect('/pelanggan')->with('success', 'Berhasil menambahkan pelanggan baru');
    }
    

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);
        return view('pelanggan.show', [
            'pelanggan' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = User::find($id);
        return view('pelanggan.edit', [
            'pelanggan' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'name'          => 'required',
            'email'         => 'required|email',
            'no_hp'         => 'required',
            'tgl_pasang'    => 'required',
        ], [
            'name.required'     => 'Form wajib diisi !',
            'email.required'    => 'Form wajib diisi !',
            'email.email'       => 'Gunakan email yang valid !',
            'no_hp.required'    => 'Form wajib diisi !',
            'tgl_pasang.required' => 'Form wajib diisi !',
        ]);
    
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
    

        if (!empty($request->password)) {
            $validator = Validator::make($request->all(), [
                'password'          => 'required',
                'confirmPassword'   => 'same:password',
            ], [
                'password.required' => 'Form wajib diisi !',
                'confirmPassword.same' => 'Konfirmasi password tidak sama !',
            ]);
    
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
    
            $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'no_hp'         => $request->no_hp,
                'tgl_pasang'    => $request->tgl_pasang,
                'password'      => $request->password
            ]);
        } else {
            $user->update([
                'name'          => $request->name,
                'email'         => $request->email,
                'no_hp'         => $request->no_hp,
                'tgl_pasang'    => $request->tgl_pasang,
            ]);
        }
    
        return redirect('/pelanggan')->with('success', 'Berhasil memperbarui data pelanggan');
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        User::find($id)->delete();

        return redirect()->back()->with('success', 'Berhasil menghapus pelanggan !');
    }
}
