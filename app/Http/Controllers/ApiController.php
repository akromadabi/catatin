<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function storeTransaction(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'amount' => 'required|numeric',
            'category' => 'required',
            'date' => 'required|date'
        ]);

        $txn = Transaction::create([
            'user_id' => auth()->id(),
            'type' => $request->type,
            'amount' => $request->amount,
            'category' => $request->category,
            'desc' => $request->desc,
            'date' => $request->date,
        ]);

        return response()->json($txn);
    }

    public function deleteTransaction($id)
    {
        $txn = Transaction::where('user_id', auth()->id())->findOrFail($id);
        $txn->delete();
        return response()->json(['success' => true]);
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required',
        ]);

        $cat = Category::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'type' => $request->type,
            'icon' => $request->icon ?? '📌',
        ]);

        return response()->json($cat);
    }

    public function deleteCategory($id)
    {
        $cat = Category::where('user_id', auth()->id())->findOrFail($id);
        $cat->delete();
        return response()->json(['success' => true]);
    }
}
