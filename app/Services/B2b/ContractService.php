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

class ContractService
{
    public const ATTACHMENT_DISK = 'local';

    public function __construct(
        private readonly SequenceService $sequences,
        private readonly ActivityLogger $logger,
    ) {}

    /** @param  array{rules?:array}  $data hasil validasi ContractRequest */
    public function create(array $data, ?UploadedFile $attachment = null, bool $activate = false): Contract
    {
        return DB::transaction(function () use ($data, $attachment, $activate) {
            $this->lockCustomer((int) $data['b2b_customer_id']);
            $this->assertNoOverlap((int) $data['b2b_customer_id'], $data['start_date'], $data['end_date']);

            $contract = new Contract(Arr::except($data, ['rules']));
            $contract->contract_number = $this->sequences->contractNumber();
            $contract->status = Contract::STATUS_DRAFT;
            $contract->created_by = auth()->id();
            $contract->save();

            $contract->rules()->createMany($data['rules'] ?? []);
            $this->storeAttachment($contract, $attachment);
            $this->logger->log('contract.created', $contract, ['rules' => count($data['rules'] ?? [])]);

            if ($activate) {
                $this->activate($contract);
            }

            return $contract->fresh('rules');
        });
    }

    /** Hanya kontrak draft yang bisa diubah; aturan diganti seluruhnya. */
    public function update(Contract $contract, array $data, ?UploadedFile $attachment = null): Contract
    {
        return DB::transaction(function () use ($contract, $data, $attachment) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if (! $contract->isDraft()) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya kontrak draft yang bisa diubah. Untuk mengubah diskon kontrak aktif, hentikan kontrak lalu buat kontrak baru.',
                ]);
            }

            $this->lockCustomer((int) $data['b2b_customer_id']);
            $this->assertNoOverlap((int) $data['b2b_customer_id'], $data['start_date'], $data['end_date'], $contract->id);

            $before = $contract->only(['b2b_customer_id', 'start_date', 'end_date']);
            $contract->fill(Arr::except($data, ['rules']));
            $contract->save();

            $contract->rules()->delete();
            $contract->rules()->createMany($data['rules'] ?? []);
            $this->storeAttachment($contract, $attachment);
            $this->logger->log('contract.updated', $contract, ['before' => $before, 'rules' => count($data['rules'] ?? [])]);

            return $contract->fresh('rules');
        });
    }

    public function activate(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if (! $contract->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Hanya kontrak draft yang bisa diaktifkan.']);
            }
            if ($contract->end_date->lt(today())) {
                throw ValidationException::withMessages(['end_date' => 'Tanggal berakhir kontrak sudah lewat. Ubah periodenya terlebih dahulu.']);
            }

            $this->lockCustomer($contract->b2b_customer_id);
            $this->assertNoOverlap($contract->b2b_customer_id, $contract->start_date, $contract->end_date, $contract->id);

            $contract->forceFill([
                'status' => Contract::STATUS_ACTIVE,
                'activated_by' => auth()->id(),
                'activated_at' => now(),
            ])->save();
            $this->logger->log('contract.activated', $contract);

            return $contract;
        });
    }

    public function terminate(Contract $contract, string $reason): Contract
    {
        return DB::transaction(function () use ($contract, $reason) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if (! $contract->isActive()) {
                throw ValidationException::withMessages(['status' => 'Hanya kontrak aktif yang bisa dihentikan.']);
            }

            $contract->forceFill([
                'status' => Contract::STATUS_TERMINATED,
                'terminated_by' => auth()->id(),
                'terminated_at' => now(),
                'termination_reason' => $reason,
            ])->save();
            $this->logger->log('contract.terminated', $contract, ['reason' => $reason]);

            return $contract;
        });
    }

    public function delete(Contract $contract): void
    {
        DB::transaction(function () use ($contract) {
            $contract = Contract::query()->whereKey($contract->id)->lockForUpdate()->firstOrFail();
            if (! $contract->isDraft()) {
                throw ValidationException::withMessages(['status' => 'Hanya kontrak draft yang bisa dihapus.']);
            }

            $contract->delete();
            $this->logger->log('contract.deleted', $contract);
        });
    }

    /** Kontrak aktif yang sudah lewat tanggal berakhir → expired. Dijalankan harian oleh b2b:sync-contract-status. */
    public function expireEndedContracts(?Carbon $today = null): int
    {
        $today ??= today();
        $count = 0;

        Contract::query()
            ->where('status', Contract::STATUS_ACTIVE)
            ->whereDate('end_date', '<', $today->toDateString())
            ->each(function (Contract $contract) use (&$count) {
                $contract->forceFill(['status' => Contract::STATUS_EXPIRED])->save();
                $this->logger->log('contract.expired', $contract, null, null);
                $count++;
            });

        return $count;
    }

    /**
     * Periode kontrak draft/aktif milik customer yang sama tidak boleh tumpang tindih,
     * sehingga pada satu tanggal paling banyak ada satu kontrak yang berlaku.
     */
    public function assertNoOverlap(int $customerId, Carbon|string $start, Carbon|string $end, ?int $ignoreId = null): void
    {
        $start = Carbon::parse($start)->toDateString();
        $end = Carbon::parse($end)->toDateString();

        $conflict = Contract::query()
            ->where('b2b_customer_id', $customerId)
            ->whereIn('status', Contract::BLOCKING_STATUSES)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->first();

        if ($conflict) {
            throw ValidationException::withMessages([
                'start_date' => sprintf(
                    'Periode bertabrakan dengan kontrak %s (%s, %s – %s).',
                    $conflict->contract_number,
                    $conflict->statusLabel(),
                    $conflict->start_date->translatedFormat('d M Y'),
                    $conflict->end_date->translatedFormat('d M Y'),
                ),
            ]);
        }
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
}
