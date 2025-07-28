<?php

namespace App\Http\Controllers;

use App\DataTables\NoteReceiptSchedulingDataTable;
use App\DataTables\NoteReceiptSchedulingRequirementProductDataTable;
use App\Http\Requests\NoteReceiptSchedulingRequest;
use App\Models\NoteReceiptScheduling;
use App\Models\Outlets;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NoteReceiptSchedulingController extends Controller
{
    public function index(NoteReceiptSchedulingDataTable $datatable){
        return $datatable->render('layouts.note_receipt_scheduling.index',[
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function create()
    {
        return view('layouts.note_receipt_scheduling.modal', [
            "data" => new NoteReceiptScheduling,
            "action" => route("library/note-receipt-scheduling/store"),
            "outlets" => Outlets::whereIn('id', json_decode(auth()->user()->outlet_id))->get(),
        ]);
    }

    public function store(NoteReceiptSchedulingRequest $request)
    {
        $validated = $request->validated();

        // Karena schema colomn start and end adalah TIME, cukup ambil format jam:menit
        $start = $validated['start_hour']; // sudah format 'H:i'
        $end = $validated['end_hour'];

        // Gunakan transaksi untuk menjamin atomicity: semua data berhasil disimpan atau rollback jika error
        DB::beginTransaction();

        try {
            foreach ($validated['outlet_id'] as $outlet) {
                $note = new NoteReceiptScheduling();
                $note->name = $validated['name'];
                $note->message = $validated['message'] ?? null;
                $note->start = $start;
                $note->end = $end;
                $note->outlet_id = $outlet;
                $note->status = $validated['status'] ?? true; // default true jika null
                $note->save();
            }

            DB::commit();

            // Response sukses dengan pesan dan data, jika perlu
            return responseSuccess(false);

        } catch (\Exception $e) {
            // Rollback jika ada error saat penyimpanan data
            DB::rollBack();

            // Log error jika perlu
            Log::error('Gagal menyimpan NoteReceiptScheduling: ' . $e->getMessage());

            // Response error dengan pesan dan status internal server error
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan Note Receipt Scheduling.',
                'error' => $e->getMessage(),
            ], 500);
        }

    }


    public function edit(NoteReceiptScheduling $noteReceiptScheduling)
    {
        // Decode JSON array outlet id (data yang sudah dipilih)
        $listOutletIds = json_decode($noteReceiptScheduling->list_outlet_id, true) ?? [];

        // Ambil semua data outlet (bukan hanya yang sudah dipilih)
        $allOutlets = Outlets::all();

        // Ubah collection ke array id dan name yang relevan untuk view
        $dataOutlets = $allOutlets->map(function ($outlet) {
            return ['id' => $outlet->id, 'name' => $outlet->name];
        });

        return view('layouts.note_receipt_scheduling.modal', [
            'data' => $noteReceiptScheduling,
            'action' => route('library/note-receipt-scheduling/update', $noteReceiptScheduling->id),
            'outlets' => json_encode($dataOutlets),
            'selectedOutlets' => $listOutletIds // kirim juga list yang terpilih ke view
        ]);
    }


    public function update(NoteReceiptSchedulingRequest $request, NoteReceiptScheduling $noteReceiptScheduling)
    {
        // Ambil data validasi
        $validatedData = $request->validated();

        // Simpan data ke model
        $noteReceiptScheduling->name = $validatedData['name'];
        $noteReceiptScheduling->message = $validatedData['message'] ?? null;

        // Gabungkan tanggal dengan jam jika ada atau langsung pakai
        // Karena di schema sudah pakai datetime, asumsikan start_hour dan end_hour sudah lengkap datetime
        // Jika hanya jam, Anda perlu menyesuaikan formatnya sesuai kebutuhan.
        $noteReceiptScheduling->start = $validatedData['start_hour']; // sesuaikan jika format perlu lengkap datetime
        $noteReceiptScheduling->end = $validatedData['end_hour'];     // sesuaikan jika format perlu lengkap datetime

        // // Simpan list outlet dalam bentuk JSON string
        // $noteReceiptScheduling->list_outlet_id = json_encode($validatedData['outlet_id']);

        // Simpan status boolean
        $noteReceiptScheduling->status = $validatedData['status'];

        // Simpan perubahan
        $noteReceiptScheduling->save();

        // Redirect atau response setelah update
        return responseSuccess(true);
    }

    public function destroy(NoteReceiptScheduling $noteReceiptScheduling) {
        $noteReceiptScheduling->delete();

        return responseSuccessDelete();
    }

    public function getRequirementProduct(NoteReceiptSchedulingRequirementProductDataTable $datatable, NoteReceiptScheduling $noteReceiptScheduling)
    {
        $dataProduct = Product::select('id')->where('outlet_id', $noteReceiptScheduling->outlet_id)->get()->pluck('id');
        return $datatable->with('noteReceiptScheduling', $noteReceiptScheduling)->render('layouts.note_receipt_scheduling.modal-requirement-note-receipt-scheduling', [
            "data" => $noteReceiptScheduling,
            "dataProduct" => $dataProduct,
            "action" => route("library/note-receipt-scheduling/setProductRequirement", $noteReceiptScheduling->id),
        ]);
    }

    public function setProductRequirement(Request $request, NoteReceiptScheduling $noteReceiptScheduling)
    {
        // dd($request);
        $data = [
            'product_id' => json_encode($request->products)
        ];

        $noteReceiptScheduling->update($data);

        return responseSuccess(true, "Requirement Product Berhasil diupdate");
    }

}
