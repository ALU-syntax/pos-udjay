# Order Table - Panduan Migrasi Project

Dokumen ini menjadi pintu masuk dan panduan kerja untuk memindahkan material di repository ini ke project yang lebih rapi. Target akhirnya adalah satu repository yang menampung frontend dan backend dalam folder berbeda, dengan batas tanggung jawab yang jelas.

Dokumen desain rinci tetap berada di [`docs/`](docs/). Jika ada perbedaan antara panduan migrasi ini dan keputusan bisnis/arsitektur di [`docs/system-design.md`](docs/system-design.md), hentikan implementasi dan selaraskan dokumennya terlebih dahulu.

## 1. Kondisi Repository Saat Ini

Repository saat ini berisi material discovery, desain, prototype, dan presentasi. Belum ada backend Order Table dan belum ada frontend produksi.

| Lokasi | Isi | Status saat migrasi |
|---|---|---|
| `docs/system-design.md` | Arsitektur, data model, API, alur bisnis, keamanan | Acuan utama implementasi |
| `docs/brand-tokens.md` | Warna, tipografi, spacing, dan aturan UI | Acuan utama frontend |
| `docs/magic-patterns-prompt.md` | Spesifikasi layar dan catatan porting | Referensi UX dan acceptance criteria |
| `docs/magic-patterns-recolor-prompt.md` | Riwayat recolor prototype | Arsip/referensi, bukan requirement runtime |
| `docs/figma-prompt.md` | Brief desain dan handoff Figma | Referensi desain |
| `references/magic-patterns-react/` | Prototype React 18 + Vite 5 + Tailwind 3 dengan data mock | Referensi visual dan interaksi, bukan frontend produksi |
| `references/presentation/` | Deck presentasi HTML (belum ada di repo) | Materi non-runtime |
| `brand/Calle-Cafe-Brand-Guideline.pdf` | Brand guideline resmi | Aset referensi |
| `brand/Calle-Black-Logo.jpg` | Logo brand | Aset sumber; optimalkan sebelum dipakai di aplikasi |

Catatan penting:

- Prototype saat ini memakai React, tetapi keputusan arsitektur menetapkan frontend produksi berupa Vue 3 PWA.
- Prototype masih memakai data lokal pada `references/magic-patterns-react/src/data/` dan state demo, belum mengonsumsi Order Service.
- Backend Node.js/Express belum tersedia di repository ini.
- Backoffice Laravel existing tidak tersedia di repository ini. Laravel tetap menjadi pemilik seluruh perubahan skema MySQL, termasuk tabel `ot_*`.
- Folder `node_modules/` prototype tidak dipindahkan dan tidak boleh di-commit ke project baru.
- `references/magic-patterns-react/src/package.json` bukan manifest aplikasi utama. Gunakan hanya `references/magic-patterns-react/package.json` untuk memahami dependency prototype.

## 2. Keputusan Struktur Target

Gunakan struktur monorepo berbasis aplikasi berikut:

```text
order-table/
├── apps/
│   ├── frontend/                 # Vue 3 PWA untuk customer
│   │   ├── public/
│   │   ├── src/
│   │   │   ├── assets/
│   │   │   ├── components/
│   │   │   │   ├── ui/
│   │   │   │   ├── menu/
│   │   │   │   ├── cart/
│   │   │   │   ├── order/
│   │   │   │   └── layout/
│   │   │   ├── views/
│   │   │   ├── stores/
│   │   │   ├── services/
│   │   │   ├── router/
│   │   │   ├── types/
│   │   │   ├── utils/
│   │   │   └── styles/
│   │   ├── tests/
│   │   ├── .env.example
│   │   └── package.json
│   └── backend/                  # BFF Order Service Node.js/Express
│       ├── src/
│       │   ├── config/
│       │   ├── db/
│       │   ├── modules/
│       │   │   ├── catalog/
│       │   │   ├── tables/
│       │   │   ├── cart/
│       │   │   ├── orders/
│       │   │   ├── pricing/
│       │   │   ├── vouchers/
│       │   │   ├── banners/
│       │   │   ├── payments/
│       │   │   ├── bridge/
│       │   │   ├── sse/
│       │   │   └── notifications/
│       │   ├── middlewares/
│       │   ├── utils/
│       │   ├── app.ts
│       │   └── server.ts
│       ├── tests/
│       ├── .env.example
│       └── package.json
├── packages/
│   └── contracts/                # Opsional setelah kontrak API stabil
├── docs/
│   ├── system-design.md
│   ├── brand-tokens.md
│   └── ...
├── references/
│   ├── magic-patterns-react/     # Prototype lama, read-only
│   └── presentation/
├── brand/
│   ├── Calle-Cafe-Brand-Guideline.pdf
│   └── Calle-Black-Logo.jpg
├── .gitignore
├── .editorconfig
├── package.json                  # Workspace dan command lint/test/build bersama
├── package-lock.json             # Satu lockfile di root jika memakai npm workspaces
└── README.md
```

### Alasan Pemilihan

- `apps/frontend` dan `apps/backend` langsung menunjukkan unit yang dapat dijalankan dan di-deploy secara terpisah.
- `docs`, `brand`, dan `references` tidak tercampur dengan source runtime.
- Satu repository memudahkan perubahan kontrak FE/BE, CI, code review, dan onboarding.
- `packages/contracts` disiapkan hanya jika dibutuhkan. Jangan membuat shared package sebelum shape request/response cukup stabil.
- Gunakan nama folder lowercase dan tanpa spasi. Jangan mempertahankan `Magic Patterns` sebagai nama aplikasi produksi. Material prototype dipindahkan ke `references/magic-patterns-react/`.

### Batas Repository

Repository target memiliki dua aplikasi runtime:

| Aplikasi | Tanggung jawab | Deployment |
|---|---|---|
| `apps/frontend` | UI PWA, local cart, IndexedDB, API client, SSE client | Static hosting/CDN |
| `apps/backend` | Session, tenant resolution, authoritative pricing, voucher, order, payment, bridge, SSE, FCM | Node.js service |

Backoffice Laravel existing tetap repository/sistem terpisah. Perubahan migrasi database dibuat dan dijalankan di repository Laravel, lalu referensinya dicatat di dokumentasi repository ini. Jangan membuat migrasi Node yang menjadi sumber kedua perubahan skema.

## 3. Arsitektur yang Tidak Boleh Berubah Saat Migrasi

Migrasi folder bukan kesempatan untuk diam-diam mengubah keputusan domain. Pertahankan aturan berikut dari `docs/system-design.md`:

1. Frontend produksi adalah Vue 3 PWA, bukan hasil salin langsung prototype React.
2. Backend publik adalah BFF Node.js/Express dengan base API `/api/v1`.
3. MySQL digunakan bersama POS, tetapi tabel Order Table memakai prefix `ot_`.
4. Laravel existing adalah satu-satunya pemilik migrasi skema.
5. Backend boleh membaca katalog POS dan menulis hasil bridge ke `transactions` serta `transaction_items`.
6. Backend dilarang membaca atau menulis `open_bills` dan `item_open_bills`.
7. Harga final, voucher, pajak, stok, dan validasi checkout selalu otoritatif di backend.
8. Webhook adalah sumber kebenaran pembayaran; SSE hanya mengirim perubahan status ke frontend.
9. Cart boleh local-first, tetapi checkout harus online dan direvalidasi backend.
10. Semua nominal uang menggunakan integer rupiah, bukan floating point.

## 4. Pilihan Tooling yang Direkomendasikan

Gunakan versi LTS aktif ketika migrasi benar-benar dimulai, lalu pin versi aktual di `engines`, CI, dan dokumentasi. Jangan menebak nomor versi jauh sebelum eksekusi.

| Area | Pilihan awal |
|---|---|
| Workspace | npm workspaces |
| Frontend | Vue 3, TypeScript, Vite, Vue Router, Pinia |
| PWA | `vite-plugin-pwa` |
| Styling | Tailwind CSS v4 dan shadcn-vue/Reka UI |
| Frontend utilities | `vaul-vue`, `embla-carousel-vue`, `lucide-vue-next`, `vue-sonner` |
| Backend | Node.js, TypeScript, Express |
| Validation | Pilih satu schema validator dan gunakan konsisten untuk env serta HTTP payload |
| Database | Driver MySQL dengan pool dan transaction support |
| Realtime | SSE; Redis pub/sub ketika backend dijalankan lebih dari satu instance |
| Logging | Structured JSON logs dengan request/correlation ID |
| Unit/integration test | Vitest atau test runner yang disepakati tim |
| API contract | OpenAPI sebagai kontrak yang dapat direview |

Root `package.json` sebaiknya hanya menjadi workspace orchestrator. Setiap aplikasi tetap memiliki script `dev`, `build`, `lint`, `typecheck`, dan `test` sendiri. Root meneruskan command tersebut ke seluruh workspace.

Contoh intent command, bukan manifest final:

```text
npm run dev             # menjalankan FE dan BE untuk development
npm run dev:frontend    # hanya Vue PWA
npm run dev:backend     # hanya Order Service
npm run lint
npm run typecheck
npm run test
npm run build
```

## 5. Pemetaan Material Lama ke Target

Jangan memindahkan seluruh folder secara buta. Gunakan pemetaan berikut:

| Sumber saat ini | Tujuan | Cara migrasi |
|---|---|---|
| `references/magic-patterns-react/src/components/ui/*` | `apps/frontend/src/components/ui/` | Implementasikan ulang dengan shadcn-vue/Reka UI |
| `references/magic-patterns-react/src/components/menu/*` | `apps/frontend/src/components/menu/` | Port perilaku dan visual ke Vue SFC |
| `references/magic-patterns-react/src/components/cart/*` | `apps/frontend/src/components/cart/` | Port ke Vue dan hubungkan store/API |
| `references/magic-patterns-react/src/components/order/*` | `apps/frontend/src/components/order/` | Port ke Vue; status datang dari API/SSE |
| `references/magic-patterns-react/src/components/layout/*` | `apps/frontend/src/components/layout/` | Port shell, nav, banner, dan safe area |
| `references/magic-patterns-react/src/pages/*` | `apps/frontend/src/views/` | Port per layar, jangan mempertahankan router demo |
| `references/magic-patterns-react/src/contexts/OrderContext.tsx` | `apps/frontend/src/stores/` | Rancang ulang sebagai Pinia stores |
| `references/magic-patterns-react/src/hooks/useOrderStore.ts` | `apps/frontend/src/stores/` | Pisahkan cart, session, dan order |
| `references/magic-patterns-react/src/data/*` | Fixture/test frontend | Jangan dipakai sebagai data produksi |
| `references/magic-patterns-react/src/types/order.ts` | FE types atau generated contract | Selaraskan dengan OpenAPI, jangan copy tanpa audit |
| `references/magic-patterns-react/src/index.css` | `apps/frontend/src/styles/` | Ambil token yang valid, sesuaikan Tailwind v4 |
| `references/magic-patterns-react/public/*` | `apps/frontend/public/` atau asset pipeline | Audit hak pakai, nama file, ukuran, dan optimasi |
| `references/presentation/` | `references/presentation/` | Pindahkan tanpa dijadikan dependency aplikasi |
| Dokumen `docs/*` | `docs/*` | Pertahankan path agar referensi antardokumen tidak rusak |
| File brand di root | `brand/` | Pindahkan dan perbarui referensi dokumentasi |

Hal yang tidak dipindahkan:

- `node_modules/` prototype
- Build output seperti `dist/`
- `.DS_Store`
- Dependency lockfile prototype sebagai lockfile workspace baru
- `references/magic-patterns-react/src/package.json`
- Data mock sebagai source of truth bisnis

## 6. Tahapan Migrasi

Lakukan migrasi bertahap. Setiap fase harus menghasilkan kondisi yang dapat di-build atau diverifikasi sebelum masuk fase berikutnya.

### Fase 0 - Bekukan Baseline

- [ ] Simpan screenshot seluruh layar dan edge state prototype.
- [ ] Jalankan dan catat hasil `npm run build` serta `npm run lint` pada prototype.
- [ ] Inventarisasi seluruh asset di `references/magic-patterns-react/public/` beserta layar pemakainya.
- [ ] Catat keputusan terbuka dari `docs/system-design.md`: mode stok, jam operasional, merchant QRIS, dan petty cash bridge.
- [ ] Jangan menghapus prototype sampai frontend baru mencapai parity yang disepakati.

### Fase 1 - Init Root Monorepo

- [ ] Buat satu repository Git pada root target.
- [ ] Buat workspace `apps/*` dan `packages/*` di root `package.json`.
- [ ] Tambahkan `.gitignore` root untuk `node_modules`, `dist`, coverage, env lokal, log, dan file OS/editor.
- [ ] Tambahkan `.editorconfig` dan aturan format/lint minimum.
- [ ] Buat command root untuk dev, build, lint, typecheck, dan test.
- [ ] Pastikan hanya satu package manager dan satu lockfile root yang digunakan.

### Fase 2 - Init Frontend

- [ ] Scaffold `apps/frontend` dengan Vue 3 + TypeScript + Vite.
- [ ] Tambahkan Vue Router, Pinia, Tailwind CSS v4, PWA plugin, dan komponen UI yang dibutuhkan.
- [ ] Implementasikan brand token dari `docs/brand-tokens.md`; jangan menyalin konfigurasi Tailwind 3 apa adanya.
- [ ] Buat route utama: `/o/:outletSlug/t/:qrToken`, `/menu`, `/cart`, `/checkout`, `/pay/:orderId`, `/status/:orderId`, `/history`.
- [ ] Buat store terpisah untuk session, cart, dan order.
- [ ] Buat `services/api.ts`, `services/sse.ts`, dan persistence IndexedDB.
- [ ] Port komponen dan layar satu per satu dari prototype, mulai dari primitive UI dan layout.
- [ ] Pertahankan fixture hanya untuk development/test sampai endpoint backend tersedia.
- [ ] Verifikasi mobile 390 x 844, desktop, tap target, safe area, loading, empty, error, offline, dan session-ended state.

### Fase 3 - Init Backend

- [ ] Scaffold `apps/backend` dengan Node.js, TypeScript, dan Express.
- [ ] Pisahkan `app.ts` dari `server.ts` agar integration test tidak membuka port nyata.
- [ ] Implementasikan config tervalidasi, MySQL pool, structured logger, request ID, error handler, dan graceful shutdown.
- [ ] Implementasikan `GET /api/v1/health` lebih dahulu.
- [ ] Susun modul sesuai domain, bukan berdasarkan tipe file global.
- [ ] Definisikan OpenAPI untuk endpoint pada `docs/system-design.md` sebelum frontend terhubung.
- [ ] Tambahkan authentication/authorization terpisah untuk customer device, webhook gateway, dan aplikasi kasir.
- [ ] Tambahkan idempotency pada checkout, webhook, settle, dan bridge.

### Fase 4 - Database dan Integrasi Laravel

- [ ] Buat migration `ot_*` di repository Laravel existing, bukan di backend Node.
- [ ] Buat penambahan geolocation outlet melalui migration Laravel setelah review dampak POS.
- [ ] Sediakan akun DB backend dengan privilege minimum yang eksplisit.
- [ ] Dokumentasikan tabel POS yang hanya boleh dibaca dan tabel yang boleh ditulis saat bridge.
- [ ] Buat bridge user sistem dan putuskan strategi petty cash sebelum transaksi produksi pertama.
- [ ] Uji bridge dalam satu transaksi database dan buktikan rollback penuh saat salah satu insert gagal.
- [ ] Uji bahwa tidak ada query aplikasi menuju `open_bills` atau `item_open_bills`.

### Fase 5 - Hubungkan Frontend dan Backend

- [ ] Ganti fixture menu dengan `GET /api/v1/outlets/{id}/menu`.
- [ ] Hubungkan resolve QR dan lifecycle table session.
- [ ] Sinkronkan local cart ke server draft tanpa menjadikan harga lokal otoritatif.
- [ ] Hubungkan validasi voucher, create order, checkout, detail, dan history.
- [ ] Hubungkan SSE dengan reconnect dan polling fallback.
- [ ] Implementasikan QRIS waiting, success, expired, dan retry state.
- [ ] Pastikan error API diterjemahkan menjadi pesan UI yang aman dan mudah dipahami.

### Fase 6 - Verifikasi dan Cutover

- [ ] Jalankan lint, typecheck, unit test, integration test, dan production build dari root.
- [ ] Uji alur QRIS dan bayar di kasir secara end-to-end.
- [ ] Uji webhook invalid signature, duplicate event, nominal tidak cocok, dan expired payment.
- [ ] Uji multi-device pada meja yang sama serta privasi history setelah sesi ditutup.
- [ ] Uji stock mode, jam buka/tutup, forced close, geofence, dan offline recovery.
- [ ] Bandingkan semua layar dengan baseline prototype dan brand guideline.
- [ ] Validasi logging, metrics, alert bridge gagal, dan mekanisme re-bridge.
- [ ] Setelah sign-off, pindahkan prototype ke `references/` atau archive; jangan hapus sebelum history aman.

## 7. Kontrak Environment

Commit hanya `.env.example`. Jangan pernah commit credential nyata. Nama akhir dapat disesuaikan ketika provider dipilih, tetapi pisahkan env FE yang bersifat publik dari secret BE.

### Frontend - `apps/frontend/.env.example`

```dotenv
VITE_APP_NAME=Order di Meja
VITE_API_BASE_URL=http://localhost:3000/api/v1
VITE_SSE_BASE_URL=http://localhost:3000/api/v1
```

Semua variable ber-prefix `VITE_` akan masuk ke bundle browser. Jangan menaruh DB credential, payment secret, Firebase private key, atau secret lain di frontend.

### Backend - `apps/backend/.env.example`

```dotenv
NODE_ENV=development
PORT=3000
APP_ORIGIN=http://localhost:5173

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=
DB_USER=
DB_PASSWORD=
DB_POOL_SIZE=10

REDIS_URL=

SESSION_TOKEN_SECRET=
PAYMENT_PROVIDER=
PAYMENT_API_URL=
PAYMENT_API_KEY=
PAYMENT_WEBHOOK_SECRET=

FIREBASE_PROJECT_ID=
FIREBASE_CLIENT_EMAIL=
FIREBASE_PRIVATE_KEY=

LOG_LEVEL=info
```

Aturan environment:

- Validasi semua variable saat startup dan hentikan proses jika konfigurasi wajib tidak valid.
- Jangan memakai fallback production untuk secret.
- Batasi `APP_ORIGIN` secara eksplisit; jangan memakai wildcard CORS di production.
- Simpan multiline private key dengan mekanisme secret manager deployment, bukan file repository.
- Pisahkan credential development, staging, dan production.

## 8. Kontrak Frontend-Backend

Gunakan `docs/system-design.md` bagian Kontrak API sebagai baseline, lalu tuangkan ke OpenAPI. Minimal endpoint yang harus tersedia:

| Method | Path | Konsumen |
|---|---|---|
| `GET` | `/api/v1/health` | Infrastruktur |
| `GET` | `/api/v1/tables/{qrToken}` | Frontend |
| `POST` | `/api/v1/tables/{qrToken}/session` | Frontend |
| `GET` | `/api/v1/outlets/{id}/menu` | Frontend |
| `GET` | `/api/v1/outlets/{id}/banners` | Frontend |
| `GET/POST` | `/api/v1/cart/draft` | Frontend |
| `POST` | `/api/v1/vouchers/validate` | Frontend |
| `POST` | `/api/v1/orders` | Frontend |
| `POST` | `/api/v1/orders/{id}/checkout` | Frontend |
| `GET` | `/api/v1/orders/{id}` | Frontend |
| `GET` | `/api/v1/orders` | Frontend |
| `GET` | `/api/v1/orders/{id}/stream` | Frontend/SSE |
| `POST` | `/api/v1/orders/{id}/settle` | Aplikasi kasir |
| `POST` | `/api/v1/payments/webhook` | Payment gateway |

Aturan kontrak:

- Gunakan casing JSON yang konsisten. Rekomendasi: `camelCase` pada API, mapping eksplisit ke `snake_case` database.
- Waktu dikirim sebagai ISO 8601 dengan timezone.
- Nominal dikirim sebagai integer rupiah.
- Response error memiliki shape konsisten, misalnya `code`, `message`, `details`, dan `requestId`.
- Jangan expose raw SQL error, stack trace, payment credential, atau internal table detail ke client.
- SSE harus memiliki event ID, heartbeat, reconnect behavior, dan otorisasi order/session.
- Request yang dapat mengakibatkan pembayaran atau penulisan transaksi harus mendukung idempotency.

## 9. Guardrail Keamanan dan Data

- Backend tidak boleh mempercayai harga, diskon, pajak, outlet, table ID, atau payment status dari frontend.
- QR token harus opaque, dapat dirotasi, dan dapat dinonaktifkan.
- `device_id` bukan authentication kuat; kombinasikan dengan session token yang dibatasi scope dan TTL.
- Webhook wajib memverifikasi signature, nominal, reference, status transition, dan duplicate delivery.
- Endpoint aplikasi kasir tidak memakai kredensial customer.
- Terapkan rate limit per IP, device, meja, dan endpoint sensitif.
- Gunakan transaction database untuk voucher redemption, payment transition, dan POS bridge yang saling terkait.
- Jangan log secret, QR payload penuh, authorization header, atau private customer data.
- Audit setiap perubahan status order dan aktor pemicunya.
- Gunakan least privilege pada DB user. Akses bridge ke tabel POS harus terbatas pada kebutuhan yang telah disetujui.

## 10. Definition of Done Migrasi

Migrasi struktur dianggap selesai ketika seluruh kondisi ini terpenuhi:

- [ ] Root adalah repository yang dapat di-clone dan di-install dengan satu package manager.
- [ ] `apps/frontend` dan `apps/backend` dapat dijalankan serta di-build dari command root.
- [ ] Tidak ada `node_modules`, secret, `.env` lokal, atau build output yang tracked.
- [ ] Frontend produksi adalah Vue 3 PWA dan tidak bergantung pada runtime prototype React.
- [ ] Semua layar utama dan edge state mempunyai parity fungsional yang disetujui.
- [ ] Backend menyediakan API terdokumentasi dan health check.
- [ ] FE dan BE berbagi kontrak yang teruji, bukan type hasil copy manual yang mudah drift.
- [ ] Migration database berada di Laravel existing dan telah direview pemilik POS.
- [ ] Test membuktikan pricing, voucher, payment webhook, status transition, dan bridge bersifat benar serta idempotent.
- [ ] Tidak ada akses ke `open_bills` dan `item_open_bills`.
- [ ] Lint, typecheck, test, dan build lulus di CI.
- [ ] `.env.example`, runbook lokal, deployment notes, dan rollback plan tersedia.
- [ ] Prototype, presentation, dan brand assets tersimpan sebagai reference tanpa menjadi dependency runtime.

## 11. Hal yang Harus Diputuskan Sebelum Produksi

Keputusan berikut masih terbuka dan harus ditutup dengan pemilik terkait:

| Keputusan | Pemilik yang perlu terlibat | Dampak |
|---|---|---|
| Merchant QRIS terpisah atau sama | Keuangan | Settlement dan rekonsiliasi |
| Petty cash untuk POS bridge | Keuangan dan backoffice | Integritas laporan kas |
| Payment gateway final | Keuangan dan engineering | Adapter, webhook, QR expiry |
| Kontrak auth aplikasi kasir | Mobile/POS dan backend | Keamanan endpoint settle |
| Strategi Redis dan horizontal scaling | Infrastruktur | SSE dan event delivery |

Sudah final (default tersimpan di konfigurasi, dapat diubah per outlet): `stock_mode` (`status_only`), jam operasional, QRIS expiry (15 menit), payment due kasir (60 menit), auto `received` → `preparing` (30 detik), dan session close time (23:59). Rinciannya di [`docs/system-design.md`](docs/system-design.md) §14 dan [`docs/laravel-migration-handoff.md`](docs/laravel-migration-handoff.md).

Jangan menjadikan asumsi sementara sebagai default production tanpa keputusan tertulis.

## 12. Urutan Implementasi yang Disarankan

Urutan berikut meminimalkan rework:

1. Rapikan root monorepo dan CI dasar.
2. Init backend foundation serta OpenAPI.
3. Init frontend foundation dan design tokens.
4. Implementasikan table session dan katalog end-to-end.
5. Port menu, product detail, dan cart dari prototype.
6. Implementasikan authoritative pricing, voucher, dan checkout.
7. Implementasikan payment adapter, webhook, dan layar QRIS.
8. Implementasikan order state machine, SSE, FCM, dan history.
9. Implementasikan POS bridge setelah keputusan petty cash final.
10. Jalankan hardening, observability, UAT tujuh outlet, lalu cutover bertahap.

## 13. Referensi Utama

- Arsitektur dan domain: [`docs/system-design.md`](docs/system-design.md)
- Handoff migrasi database Laravel: [`docs/laravel-migration-handoff.md`](docs/laravel-migration-handoff.md)
- Design tokens: [`docs/brand-tokens.md`](docs/brand-tokens.md)
- Detail layar dan porting: [`docs/magic-patterns-prompt.md`](docs/magic-patterns-prompt.md)
- Brief Figma: [`docs/figma-prompt.md`](docs/figma-prompt.md)
- Riwayat recolor prototype: [`docs/magic-patterns-recolor-prompt.md`](docs/magic-patterns-recolor-prompt.md)

Perbarui dokumen ini setiap kali struktur target, ownership sistem, atau urutan migrasi berubah. Tujuannya adalah agar perpindahan dilakukan sebagai migrasi terkontrol, bukan sekadar memindahkan folder.
