<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Uom;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ItemController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.items.index');
    }

    public function data(Request $request)
    {
        $items = Item::with(['category','uom'])->latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'category' => optional($item->category)->name,
                'uom' => optional($item->uom)->name,
                'cnt' => $item->cnt,
                'description' => $item->description,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        return view('admin.masterdata.items.create', compact('categories','uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', 'unique:items,sku'],
            'cnt' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:uoms,id'],
            'description' => ['nullable', 'string'],
        ]);

        Item::create($validated);

        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil dibuat');
    }

    public function edit(Item $item)
    {
        $categories = Category::orderBy('name')->get();
        $uoms = Uom::orderBy('name')->get();
        return view('admin.masterdata.items.edit', compact('item', 'categories','uoms'));
    }

    public function update(Request $request, Item $item)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('items', 'sku')->ignore($item->id)],
            'cnt' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'uom_id' => ['required', 'exists:uoms,id'],
            'description' => ['nullable', 'string'],
        ]);

        $item->update($validated);

        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil diperbarui');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()
            ->route('admin.masterdata.items.index')
            ->with('success', 'Item berhasil dihapus');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:20480'],
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return $this->importResponse($request, 0, [], [['row' => 0, 'error' => 'Tidak dapat membaca file']]);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $this->importResponse($request, 0, [], [['row' => 0, 'error' => 'Header CSV tidak ditemukan']]);
        }

        $map = [];
        foreach ($headers as $i => $h) {
            $key = strtolower(trim($h));
            $map[$i] = $key;
        }

        $normalize = function(array $row) use ($map) {
            $data = [];
            foreach ($row as $i => $v) {
                $k = $map[$i] ?? ('col_'.$i);
                $data[$k] = trim((string)$v);
            }
            return $data;
        };

        $resolve = function(array $data, string $name, array $aliases) {
            foreach ($aliases as $a) {
                if (array_key_exists($a, $data) && $data[$a] !== '') return $data[$a];
            }
            return null;
        };

        $rowsToInsert = [];
        $errors = [];
        $rownum = 1; // counting header as row 1
        while (($row = fgetcsv($handle)) !== false) {
            $rownum++;
            if (count(array_filter($row, fn($v)=> trim((string)$v) !== '')) === 0) continue; // skip empty row
            $data = $normalize($row);

            $name = $resolve($data, 'name', ['name','nama']);
            $sku  = $resolve($data, 'sku', ['sku','kode','kode_sku']);
            $cnt  = $resolve($data, 'cnt', ['cnt','koli']);
            $cat  = $resolve($data, 'category', ['category','kategori','category_name']);
            $uom  = $resolve($data, 'uom', ['uom','uom_name']);
            $desc = $resolve($data, 'description', ['description','deskripsi','keterangan']);

            $rowErr = [];
            if (!$name) $rowErr[] = 'name wajib';
            if (!$sku) $rowErr[] = 'sku wajib';
            if (!$cnt) $rowErr[] = 'cnt wajib';
            if (!$cat) $rowErr[] = 'category wajib';
            if (!$uom) $rowErr[] = 'uom wajib';

            if ($rowErr) { $errors[] = ['row' => $rownum, 'error' => implode('; ', $rowErr)]; continue; }

            $category = Category::where('name', $cat)->first();
            if (!$category) { $errors[] = ['row' => $rownum, 'error' => "Kategori '$cat' tidak ditemukan"]; continue; }
            $uomModel = Uom::where('name', $uom)->first();
            if (!$uomModel) { $errors[] = ['row' => $rownum, 'error' => "UOM '$uom' tidak ditemukan"]; continue; }

            // duplicate SKU check
            if (Item::where('sku', $sku)->exists()) {
                $errors[] = ['row' => $rownum, 'error' => "SKU '$sku' sudah ada"]; continue;
            }

            $rowsToInsert[] = [
                'name' => $name,
                'sku' => $sku,
                'cnt' => $cnt,
                'description' => $desc,
                'category_id' => $category->id,
                'uom_id' => $uomModel->id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        fclose($handle);

        $inserted = 0;
        if (!empty($rowsToInsert)) {
            DB::table('items')->insert($rowsToInsert);
            $inserted = count($rowsToInsert);
        }

        return $this->importResponse($request, $inserted, $rowsToInsert, $errors);
    }

    protected function importResponse(Request $request, int $inserted, array $rows, array $errors)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'inserted' => $inserted,
                'errors' => $errors,
            ]);
        }
        $msg = "Import selesai: {$inserted} baris berhasil, ".count($errors)." error.";
        return redirect()->route('admin.masterdata.items.index')->with('success', $msg);
    }
}
