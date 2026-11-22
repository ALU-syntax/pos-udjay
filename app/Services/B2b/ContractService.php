<?php

namespace App\Services\B2b;

use App\Models\B2b\Contract;
use App\Models\B2b\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Kontrak langsung aktif saat dibuat dan tetap bisa diedit (tanggal & harga) selama belum dihentikan.
 * Pesanan lama tidak terpengaruh karena harga disalin ke item pesanan.
 */
class ContractService
{
    public const ATTACHMENT_DISK = 'local';

    private const CONTRACT_FIELDS = ['start_date', 'end_date', 'notes'];

    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * `copied_from` = kontrak asal saat perpanjangan; dicatat di log supaya detail kontrak bisa menampilkan "Diperpanjang dari".
     *
     * @param  array{b2b_customer_id:int, start_date:string, end_date:string, notes?:string|null, copied_from?:Contract|null, items:array}  $data
     */
    public function create(array $data, ?UploadedFile $attachment = null): Contract
    {
        return DB::transaction(function () use ($data, $attachment) {
            $customerId = (int) $data['b2b_customer_id'];
            $this->lockCustomer($customerId);
            $this->assertNoOverlap($customerId, $data['start_date'], $data['end_date']);

            $contract = new Contract(Arr::only($data, self::CONTRACT_FIELDS));
            $contract->forceFill([
                'b2b_customer_id' => $customerId,
                'status' => Contract::STATUS_ACTIVE,
                'created_by' => auth()->id(),
            ]);
            $contract->contract_number = $this->sequences->contractNumber();
            $contract->save();

            $changes = $this->syncItems($contract, $data['items']);
            $this->storeAttachment($contract, $attachment);
            $source = $data['copied_from'] ?? null;
            $this->logger->log('contract.created', $contract, ['items' => $changes] + ($source ? [
                'copied_from' => ['id' => $source->id, 'contract_number' => $source->contract_number],
            ] : []));

            return $contract->fresh('items');
        });
    }

    /** Tanggal, catatan, dan harga bisa diubah kapan saja selama kontrak belum dihentikan. Customer tidak bisa diganti. */
    public function update(Contract $contract, array $data, ?UploadedFile $attachment = null, bool $removeAttachment = false): Contract
    {
        return DB::transaction(function () use ($contract, $data, $attachment, $removeAttachment) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($contract->isTerminated()) {
                throw ValidationException::withMessages(['status' => 'Kontrak yang sudah dihentikan tidak bisa diubah. Buat kontrak baru sebagai gantinya.']);
            }

            $this->lockCustomer($contract->b2b_customer_id);
            $this->assertNoOverlap($contract->b2b_customer_id, $data['start_date'], $data['end_date'], $contract->id);

            $before = ['start_date' => $contract->start_date->toDateString(), 'end_date' => $contract->end_date->toDateString()];
            $contract->fill(Arr::only($data, self::CONTRACT_FIELDS));
            $contract->modified_by = auth()->id();
            $contract->save();

            $changes = $this->syncItems($contract, $data['items']);
            // File baru selalu menang atas permintaan hapus.
            $attachmentChange = null;
            if ($attachment) {
                $this->storeAttachment($contract, $attachment);
                $attachmentChange = 'replaced';
            } elseif ($removeAttachment && $this->removeAttachment($contract)) {
                $attachmentChange = 'removed';
            }
            $this->logger->log('contract.updated', $contract, [
                'before' => $before,
                'after' => ['start_date' => $contract->start_date->toDateString(), 'end_date' => $contract->end_date->toDateString()],
                'items' => $changes,
            ] + array_filter(['attachment' => $attachmentChange]));

            return $contract->fresh('items');
        });
    }

    /** Hentikan kontrak yang belum dihentikan dan belum berakhir. Pesanan berikutnya memakai harga normal. */
    public function terminate(Contract $contract, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($contract->isTerminated()) {
                throw ValidationException::withMessages(['status' => 'Kontrak ini sudah dihentikan.']);
            }
            if ($contract->end_date->lt(today())) {
                throw ValidationException::withMessages(['status' => 'Kontrak ini sudah berakhir, jadi tidak perlu dihentikan.']);
            }

            $contract->forceFill([
                'status' => Contract::STATUS_TERMINATED,
                'terminated_by' => auth()->id(),
                'terminated_at' => now(),
                'termination_reason' => $reason,
                'modified_by' => auth()->id(),
            ])->save();
            $this->logger->log('contract.terminated', $contract, ['reason' => $reason]);

            return $contract;
        });
    }

    /** Soft delete. Kontrak yang sedang berjalan harus dihentikan dulu. */
    public function delete(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if ($contract->isRunning()) {
                throw ValidationException::withMessages([
                    'status' => 'Kontrak sedang berjalan, jadi tidak bisa dihapus. Hentikan kontrak dulu kalau memang tidak dipakai lagi.',
                ]);
            }

            $contract->forceFill(['modified_by' => auth()->id()])->save();
            $contract->delete();
            $this->logger->log('contract.deleted', $contract);
        });
    }

    /**
     * Periode kontrak yang belum dihentikan milik customer yang sama tidak boleh tumpang tindih,
     * sehingga pada satu tanggal paling banyak ada satu kontrak yang berlaku. Kontrak terhapus tidak dihitung.
     */
    public function assertNoOverlap(int $customerId, Carbon|string $start, Carbon|string $end, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        $conflict = Contract::query()
            ->where('b2b_customer_id', $customerId)
            ->where('status', Contract::STATUS_ACTIVE)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_date' => sprintf(
                    'Customer ini sudah punya kontrak %s (%s, %s – %s) di periode yang sama.',
                    $conflict->contract_number,
                    $conflict->statusLabel(),
                    $conflict->start_date->translatedFormat('d M Y'),
                    $conflict->end_date->translatedFormat('d M Y'),
                ),
            ]);
        }
    }

    /**
     * Baris dengan id milik kontrak ini diupdate di tempat, baris baru dibuat, dan baris yang dibuang
     * di-soft-delete supaya pesanan lama tetap bisa merujuk harga yang dipakai.
     *
     * @return array{created:int, updated:int, deleted:int}
     */
    private function syncItems(Contract $contract, array $rows): array
    {
        $existing = $contract->items()->get()->keyBy('id');
        $keptIds = [];
        $changes = ['created' => 0, 'updated' => 0, 'deleted' => 0];

        foreach ($rows as $row) {
            $attributes = Arr::only($row, ['variant_id', 'min_qty', 'price']);
            $item = $row['id'] ? $existing->get($row['id']) : null;

            if ($item) {
                $item->fill($attributes);
                if ($item->isDirty()) {
                    $item->save();
                    $changes['updated']++;
                }
                $keptIds[] = $item->id;
            } else {
                $keptIds[] = $contract->items()->create($attributes)->id;
                $changes['created']++;
            }
        }

        $removed = $existing->except($keptIds);
        $removed->each->delete();
        $changes['deleted'] = $removed->count();

        return $changes;
    }

    /** Kunci baris customer supaya dua kontrak untuk customer yang sama tidak lolos cek overlap bersamaan. */
    private function lockCustomer(int $customerId): void
    {
        Customer::query()->whereKey($customerId)->lockForUpdate()->first();
    }

    private function storeAttachment(Contract $contract, ?UploadedFile $attachment): void
    {
        if (! $attachment) {
            return;
        }

        $oldPath = $contract->attachment_path;
        $path = $attachment->store('b2b/contracts/'.$contract->id, self::ATTACHMENT_DISK);
        $contract->forceFill([
            'attachment_path' => $path,
            'attachment_name' => mb_substr($attachment->getClientOriginalName(), 0, 255),
        ])->save();

        if ($oldPath && $oldPath !== $path) {
            DB::afterCommit(fn () => Storage::disk(self::ATTACHMENT_DISK)->delete($oldPath));
        }
    }

    /** Lepas file kontrak tersimpan; file fisiknya baru dihapus setelah transaksi berhasil. */
    private function removeAttachment(Contract $contract): bool
    {
        $oldPath = $contract->attachment_path;
        if (! $oldPath && ! $contract->attachment_name) {
            return false;
        }

        $contract->forceFill(['attachment_path' => null, 'attachment_name' => null])->save();
        if ($oldPath) {
            DB::afterCommit(fn () => Storage::disk(self::ATTACHMENT_DISK)->delete($oldPath));
        }

        return true;
    }
}
