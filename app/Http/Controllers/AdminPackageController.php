<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\Request;

class AdminPackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('users')->get();
        return view('admin.packages', compact('packages'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'transaction_limit' => 'nullable|integer',
        ]);

        Package::create([
            'name' => $request->name,
            'price' => $request->price,
            'transaction_limit' => $request->transaction_limit,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil ditambahkan');
    }

    public function update(Request $request, Package $package)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric',
            'transaction_limit' => 'nullable|integer',
        ]);

        $package->update([
            'name' => $request->name,
            'price' => $request->price,
            'transaction_limit' => $request->transaction_limit,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('admin.packages.index')->with('success', 'Data paket berhasil diperbarui');
    }

    public function destroy(Package $package)
    {
        if ($package->users()->count() > 0) {
            return redirect()->route('admin.packages.index')->with('error', 'Gagal menghapus! Paket ini masih digunakan oleh pengguna.');
        }

        $package->delete();
        return redirect()->route('admin.packages.index')->with('success', 'Paket berhasil dihapus');
    }
}
