# Android Kasir API

Base URL uses `/api/v1`. Login remains Sanctum `POST /login`; protected Order Table requests require `Authorization: Bearer`, `X-Device-Id`, and `X-Shift-Session-Id`. The shift must be active, belong to the authenticated user/outlet, and have an open petty cash. The server, not a client-supplied outlet, determines scope.

## Order Table

`GET /order-table/orders?status=placed&payment_mode=pay_at_cashier&limit=50&updated_since=...` lists either `qris` or `pay_at_cashier`; omit `payment_mode` to list both. It returns `data` plus `meta.server_time`, `meta.next_cursor`, and `meta.has_more`. Use `GET /order-table/orders/{id}` for the authoritative item snapshot. Item IDs are the OT snapshot `productId` and `variantId`; do not remap by name or current catalog prices.

Order payload includes `paymentMode`, `paymentStatus`, `grandTotal`, `items`, `payments`, `posBridgeStatus`, and `posTransactionId`. No customer, rewards, open bills, or item-open-bill fields are used by this integration.

## Cashier Payment

1. Claim: `POST /order-table/orders/{id}/claim` with `{}`.
2. Pay: `POST /order-table/orders/{id}/pay` with the returned token and a POS payment method ID:

```json
{"claimToken":"uuid","paymentId":4,"nominalBayar":100000}
```

Example claim response:

```json
{"status":"success","data":{"orderId":123,"state":"claimed","claimToken":"uuid","claimExpiresAt":"2026-10-12T12:02:00+07:00","collectionAllowed":true,"settleRequired":false,"receiptAvailable":false,"coAvailable":false}}
```

The claim is atomic per OT order and bound to the device, user, outlet, and active shift. Default expiry is 120 seconds. Expiry only releases an uncommitted claim. Once the POS transaction is committed, the durable `transaction_id` evidence is authoritative and a later claim cannot collect again, even if its claim expired or the request timed out.

After `pay`, call `GET /order-table/orders/{id}/payment-state`. If state is `pos_committed`, retry `POST /order-table/orders/{id}/settle` using the same `posTransactionId`; never create another POS transaction. Settle forwards only `X-Internal-Api-Key`, sanitized `X-Actor-*`, and an idempotency key to Node. Laravel does not mutate `ot_*` statuses.

`qris` orders cannot be claimed or paid by this Android cashier endpoint. `pay_at_cashier` is the only collectible mode. Receipt is `GET /order-table/orders/{id}/receipt` and succeeds only when a POS transaction exists. CO data is `GET /order-table/orders/{id}/co` and succeeds only when `paymentStatus=paid`. Serve is `POST /order-table/orders/{id}/serve` and succeeds only for paid `received/preparing/served` orders; serve audit is separate from payment claim.

## Errors And Recovery

Errors are `{status:"error",code:"...",message:"..."}` and never contain SQL, stack traces, or upstream response bodies. `ORDER_CLAIMED` means refresh/retry state, `CLAIM_EXPIRED_OR_NOT_OWNED` means claim again only if no physical collection occurred, `SNAPSHOT_CHANGED` means reconfirm the changed snapshot, `SNAPSHOT_MAPPING_INVALID` means do not retry blindly, `PAYMENT_RECONCILIATION_REQUIRED` means stop and audit, and `ORDER_TABLE_UNAVAILABLE` means retry settle/payment-state with the same transaction ID. `ORDER_TABLE_PAYMENT_REQUIRED` from `POST /transactions/pay` means the generic POS endpoint cannot be used for an OT reference.

## FCM MVP

Accepted refresh events also include `order.bridge_updated` and `order.status_changed`. A QRIS bridge update after paid uses a new eventId; refresh receipt availability rather than assuming paid already has a POS transaction.

Register the current token with `POST /order-table/device/fcm-token` and `{ "fcmToken":"..." }`. Delete registration with `DELETE` on the same path; logout removes the binding. Tokens are encrypted. Laravel now implements authenticated Node event ingestion, eventId deduplication, transactional delivery outbox, queued OAuth/HTTP v1 sending, retries, and UNREGISTERED cleanup. Node must adopt the new ingestion contract; see the Indonesian `ANDROID_ORDER_TABLE_HANDOFF.md` for exact Firebase and worker setup. Deduplicate pushes by eventId and always refresh server state.

Heartbeat: `POST /order-table/orders/{id}/claim/heartbeat` with `{"claimToken":"uuid"}` extends only an owned unexpired claim, capped by order due time. Cash uses `paymentId:null`; non-cash uses an active POS payment ID. All OT items require a valid matching variant.

For sync retain `meta.sync_until` across cursor pages, then use it as the next inclusive `updated_since`. Sync without status filters for terminal transitions. Periodic full reconciliation remains necessary for Node backdated updates/deletions.

## Existing POS APIs

Catalog, shift, customer, open-bill, transaction sync, refund, and receipt resend APIs remain available. Android local Room payment/printer/rendering models are the client source for printing; `dataStruk` is not required for this Order Table contract.

OT items have no category/printer-routing snapshot. Use known Room productId mappings and existing printer configuration only; otherwise use an operationally approved manual fallback or block printing with a warning, never guess a category/printer.
