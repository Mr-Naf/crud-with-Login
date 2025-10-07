<?php
namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
  public function index(Request $request)  {
    $items = Auth::user()->items()->get();
    return view('items.index', compact('items'));

  }
      public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create item with user_id
        $data = $validator->validated();
        $data['user_id'] = Auth::id();
        $item = Item::create($data);

        return response()->json(['message' => 'Item created successfully', 'item' => $item], 201);
    }

    // Return single item json (only if belongs to user)
    public function show(Item $item)
    {
        if ($item->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($item);
    }

    // Update item (only if belongs to user)
    public function update(Request $request, Item $item)
    {
        if ($item->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $item->update($validator->validated());

        return response()->json(['message' => 'Item updated successfully', 'item' => $item]);
    }

    // Delete item (only if belongs to user)
    public function destroy(Item $item)
    {
        if ($item->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }
}
