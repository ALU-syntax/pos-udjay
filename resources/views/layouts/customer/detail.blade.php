@extends('layouts.app')
@section('content')
    <style>
        .member-detail-layout {
            align-items: stretch;
        }

        .member-profile-card,
        .member-level-progress-card,
        .member-summary-panel {
            height: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
        }

        .member-profile-card {
            padding: 22px;
        }

        .member-level-progress-card {
            padding: 20px 22px;
        }

        .member-profile-header {
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }

        .member-initial-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 70px;
            height: 70px;
            border-radius: 14px;
            background: #eef4ff;
            color: #2563eb;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0;
        }

        .member-heading {
            min-width: 0;
            flex: 1;
        }

        .member-name-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .member-name {
            margin: 0;
            color: #111827;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.25;
        }

        .member-level-badge {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .18);
        }

        .member-subtext-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px 16px;
            color: #4b5563;
            font-size: 14px;
            line-height: 1.4;
        }

        .member-subtext-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-width: 0;
            max-width: 100%;
        }

        .member-subtext-item i {
            color: #2563eb;
            font-size: 13px;
        }

        .member-subtext-item span {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .member-progress-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
        }

        .member-progress-title {
            margin: 0;
            color: #111827;
            font-size: 16px;
            font-weight: 800;
            line-height: 1.25;
        }

        .member-progress-caption {
            margin: 0px 0 0;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
        }

        .member-progress-exp {
            color: #111827;
            font-size: 13px;
            font-weight: 800;
            white-space: nowrap;
        }

        .member-level-progress-track {
            position: relative;
            height: 12px;
            overflow: hidden;
            border-radius: 999px;
            background: #e5e7eb;
        }

        .member-level-progress-fill {
            height: 100%;
            border-radius: inherit;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .3);
        }

        .member-level-markers {
            position: relative;
            min-height: 25px;
            margin-top: 5px;
            margin-right: 4px !important;
        }

        .member-level-marker {
            position: absolute;
            top: 0;
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            transform: translateX(-50%);
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.15;
            text-align: center;
            white-space: nowrap;
        }

        .member-level-marker::before {
            content: "";
            width: 8px;
            height: 8px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            background: #cbd5e1;
            box-shadow: 0 0 0 1px #cbd5e1;
        }

        .member-level-marker.is-active {
            color: #111827;
        }

        .member-level-marker.is-active::before {
            background: #2563eb;
            box-shadow: 0 0 0 1px #2563eb;
        }

        .member-next-level-info {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            color: #374151;
            font-size: 13px;
            font-weight: 700;
        }

        .member-next-level-info i {
            color: #2563eb;
        }

        .member-summary-panel {
            padding: 0 8px;
        }

        .member-stat-item {
            display: flex;
            align-items: center;
            gap: 9px;
            min-height: 72px;
            height: 100%;
            padding: 10px 11px;
            border: 1px solid #eef2f7;
            border-radius: 8px;
            background: #fbfdff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
        }

        .member-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #eef4ff;
            color: #2563eb;
            font-size: 15px;
        }

        .member-stat-content {
            min-width: 0;
        }

        .member-stat-label {
            margin: 0 0 3px;
            color: #6b7280;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .member-stat-value {
            margin: 0;
            color: #111827;
            font-size: 17px;
            font-weight: 800;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .member-tabs-shell {
            margin-top: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background: #ffffff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
        }

        .member-tabs-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 14px 16px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .member-tabs-nav {
            gap: 6px;
            border-bottom: 0;
        }

        .member-tabs-nav .nav-link {
            min-height: 40px;
            border: 0;
            border-radius: 8px 8px 0 0;
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
        }

        .member-tabs-nav .nav-link.active {
            background: #eef4ff;
            color: #0b5ed7;
        }

        .member-tabs-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 8px;
            padding-bottom: 10px;
            flex-wrap: wrap;
        }

        .member-tabs-body {
            padding: 18px 16px 16px;
        }

        .member-overview-panel {
            border: 1px solid #eef2f7;
            border-radius: 8px;
            background: #fbfdff;
        }

        .member-overview-panel + .member-overview-panel {
            margin-top: 14px;
        }

        .member-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 14px 16px;
            border-bottom: 1px solid #eef2f7;
        }

        .member-panel-title {
            margin: 0;
            color: #111827;
            font-size: 15px;
            font-weight: 800;
            line-height: 1.3;
        }

        .member-panel-caption {
            margin: 2px 0 0;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
        }

        .member-list-stack {
            display: flex;
            flex-direction: column;
        }

        .member-overview-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(116px, max-content);
            gap: 16px;
            align-items: center;
            padding: 13px 16px;
            border-bottom: 1px solid #eef2f7;
        }

        .member-overview-row:last-child {
            border-bottom: 0;
        }

        .member-row-title {
            margin: 0;
            color: #111827;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .member-row-subtext {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .member-row-amount {
            text-align: right;
        }

        .member-reward-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 24px;
            padding: 4px 9px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 800;
            white-space: nowrap;
        }

        .member-reward-badge.is-claimed {
            background: #dcfce7;
            color: #166534;
        }

        .member-reward-badge.is-open {
            background: #fff7ed;
            color: #9a3412;
        }

        .member-summary-list {
            padding: 4px 16px 10px;
        }

        .member-summary-line {
            display: flex;
            justify-content: space-between;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid #eef2f7;
        }

        .member-summary-line:last-child {
            border-bottom: 0;
        }

        .member-summary-label {
            color: #6b7280;
            font-size: 12px;
            font-weight: 700;
        }

        .member-summary-value {
            max-width: 58%;
            color: #111827;
            font-size: 12px;
            font-weight: 800;
            text-align: right;
            overflow-wrap: anywhere;
        }

        .member-benefit-block {
            padding: 13px 16px;
            border-bottom: 1px solid #eef2f7;
        }

        .member-benefit-block:last-child {
            border-bottom: 0;
        }

        .member-benefit-label {
            margin: 0 0 8px;
            color: #374151;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .member-benefit-list {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .member-benefit-pill {
            display: inline-flex;
            align-items: center;
            min-height: 27px;
            padding: 5px 9px;
            border-radius: 999px;
            background: #eef4ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 800;
        }

        .member-empty-state {
            padding: 22px 16px;
            color: #64748b;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
        }

        .member-tab-placeholder {
            min-height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            color: #64748b;
            font-size: 13px;
            font-weight: 800;
            text-align: center;
        }

        @media (max-width: 767.98px) {
            .member-profile-header {
                align-items: flex-start;
            }

            .member-profile-card {
                padding: 18px;
            }

            .member-stat-item {
                min-height: 68px;
            }

            .member-tabs-header {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }

            .member-tabs-actions {
                justify-content: flex-start;
            }

            .member-overview-row {
                grid-template-columns: minmax(0, 1fr);
            }

            .member-row-amount {
                text-align: left;
            }

            .member-summary-value {
                max-width: 52%;
            }
        }
    </style>
    <div class="main-content">
        <div class="row member-detail-layout mt-4">
            <div class="col-lg-6 ">
                <div class="member-profile-card">
                    <div class="member-profile-header">
                        <div class="member-initial-box">{{ $customerInitials }}</div>
                        <div class="member-heading">
                            <div class="member-name-row d-flex justify-content-between">
                                <h4 class="member-name">{{ $data->name }}</h4>
                                <span class="member-level-badge"
                                    style="background-color: {{ $levelBadgeColor }}; color: {{ $levelBadgeTextColor }};">
                                    {{ $data->levelMembership?->name ?? '-' }}
                                </span>
                            </div>

                            <div class="member-subtext-list">
                                <span class="member-subtext-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>{{ $data->email ?? '-' }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-phone"></i>
                                    <span>{{ $data->telfon ?? '-' }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Join {{ \Carbon\Carbon::parse($data->created_at)->format('d M Y') }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-clock"></i>
                                    <span>{{ $accountAge }}</span>
                                </span>
                                <span class="member-subtext-item">
                                    <i class="fas fa-store"></i>
                                    <span>{{ $createdLocation }}</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mt-3 mt-lg-0">
                <div class="member-level-progress-card">
                    <div class="member-progress-header">
                        <div>
                            <h5 class="member-progress-title">{{ $data->levelMembership?->name ?? '-' }}</h5>
                            <p class="member-progress-caption">Membership level progress</p>
                        </div>
                        <div class="member-progress-exp">
                            {{ number_format($currentExp, 0, ',', '.') }} /
                            {{ number_format($maxLevelBenchmark, 0, ',', '.') }} EXP
                        </div>
                    </div>

                    <div class="member-level-progress-track">
                        <div class="member-level-progress-fill"
                            style="width: {{ $levelProgressPercent }}%; background-color: #0b5ed7;">
                        </div>
                    </div>

                    <div class="member-level-markers">
                        @foreach ($membershipLevels as $index => $level)
                            @php
                                $levelBenchmark = (int) $level->benchmark;
                                $levelPosition = $maxLevelBenchmark > 0 ? min(98, ($levelBenchmark / $maxLevelBenchmark) * 100) : 0;
                            @endphp
                            <span class="member-level-marker {{ $currentExp >= $levelBenchmark ? 'is-active' : '' }}"
                                style="left: {{ $index == 0 ? 1 : $levelPosition }}%;">
                                {{ $level->name }}
                            </span>
                        @endforeach
                    </div>

                    <div class="member-next-level-info">
                        <i class="fas fa-arrow-up"></i>
                        @if ($nextLevel)
                            <span>{{ number_format($expToNextLevel, 0, ',', '.') }} EXP lagi ke {{ $nextLevel->name }}</span>
                        @else
                            <span>Member sudah berada di level tertinggi</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="member-card-information">
            <div class="row mt-4">
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="far fa-star"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Points</p>
                            <h4 class="member-stat-value" id="point">{{ number_format( $data->point, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fab fa-viacoin"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Total Exp</p>
                            <h4 class="member-stat-value" id="exp">{{ number_format( $data->exp, 0, ',', '.') }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fas fa-receipt"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Total Transactions</p>
                            <h4 class="member-stat-value" id="count-transaction">{{ count($data->transactions) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="icon-wallet"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Total Spent</p>
                            <h4 class="member-stat-value" id="transaction-nominal">{{ formatRupiah(strval($transactionNominal), "Rp. ") }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fas fa-chart-line"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Average Transaction</p>
                            <h4 class="member-stat-value" id="average-transaction">{{ formatRupiah(strval($averageTransaction), "Rp. ") }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4 col-lg-4 mb-3">
                    <div class="member-stat-item">
                        <span class="member-stat-icon"><i class="fas fa-gift"></i></span>
                        <div class="member-stat-content">
                            <p class="member-stat-label">Reward</p>
                            <h4 class="member-stat-value">{{ $totalReward }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (session()->has('success'))
            <div class="alert alert-success mt-2" role="alert">
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger mt-2" role="alert">
                {{ session('error') }}
            </div>
        @endif
        <div class="member-tabs-shell">
            <div class="member-tabs-header">
                <ul class="nav nav-tabs member-tabs-nav" id="memberDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                            data-bs-target="#overview-pane" type="button" role="tab" aria-controls="overview-pane"
                            aria-selected="true">Overview</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transactions-tab" data-bs-toggle="tab"
                            data-bs-target="#transactions-pane" type="button" role="tab"
                            aria-controls="transactions-pane" aria-selected="false">Transactions</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rewards-tab" data-bs-toggle="tab" data-bs-target="#rewards-pane"
                            type="button" role="tab" aria-controls="rewards-pane" aria-selected="false">Rewards</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="capsule-history-tab" data-bs-toggle="tab"
                            data-bs-target="#capsule-history-pane" type="button" role="tab"
                            aria-controls="capsule-history-pane" aria-selected="false">Capsule History</button>
                    </li>
                </ul>
                <div class="member-tabs-actions">
                    <a href="{{ route('membership/community/historyUseExp', $data->id) }}" type="button"
                        id="btnHistoryUseExp" class="btn btn-primary btn-sm">History Use Exp</a>
                    <a href="{{ route('membership/community/createExchangeExp', $data->id) }}" type="button"
                        class="btn btn-primary btn-round btn-sm action">Use Exp</a>
                </div>
            </div>
            <div class="member-tabs-body">
                <div class="tab-content" id="memberDetailTabsContent">
                    <div class="tab-pane fade show active" id="overview-pane" role="tabpanel"
                        aria-labelledby="overview-tab" tabindex="0">
                        <div class="row">
                            <div class="col-lg-8 mb-3 mb-lg-0">
                                <div class="member-overview-panel">
                                    <div class="member-panel-header">
                                        <div>
                                            <h5 class="member-panel-title">Recent Transaction</h5>
                                            <p class="member-panel-caption">Ringkasan transaksi terbaru member</p>
                                        </div>
                                    </div>
                                    <div class="member-list-stack">
                                        @forelse ($overviewRecentTransactions as $transaction)
                                            <div class="member-overview-row">
                                                <div>
                                                    <p class="member-row-title">{{ $transaction['outlet_name'] }}</p>
                                                    <p class="member-row-subtext">{{ $transaction['items'] }}</p>
                                                </div>
                                                <div class="member-row-amount">
                                                    <p class="member-row-title">
                                                        {{ formatRupiah(strval($transaction['total']), 'Rp. ') }}
                                                    </p>
                                                    <p class="member-row-subtext">
                                                        +{{ number_format($transaction['point'], 0, ',', '.') }} point/exp
                                                    </p>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="member-empty-state">Belum ada transaksi</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="member-overview-panel">
                                    <div class="member-panel-header">
                                        <div>
                                            <h5 class="member-panel-title">Rewards</h5>
                                            <p class="member-panel-caption">Reward yang berlaku untuk member ini</p>
                                        </div>
                                    </div>
                                    <div class="member-list-stack">
                                        @forelse ($availableRewards as $reward)
                                            <div class="member-overview-row">
                                                <div>
                                                    <p class="member-row-title">{{ $reward['name'] }}</p>
                                                    <p class="member-row-subtext">{{ $reward['type'] }}</p>
                                                </div>
                                                <div class="member-row-amount">
                                                    <span
                                                        class="member-reward-badge {{ $reward['claimed'] ? 'is-claimed' : 'is-open' }}">
                                                        {{ $reward['claimed'] ? 'Sudah diambil' : 'Belum diambil' }}
                                                    </span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="member-empty-state">Belum ada reward</div>
                                        @endforelse
                                    </div>
                                </div>

                                <div class="member-overview-panel">
                                    <div class="member-panel-header">
                                        <div>
                                            <h5 class="member-panel-title">Activity Summary</h5>
                                            <p class="member-panel-caption">Perilaku transaksi dan klaim reward</p>
                                        </div>
                                    </div>
                                    <div class="member-summary-list">
                                        <div class="member-summary-line">
                                            <span class="member-summary-label">Transaksi terakhir</span>
                                            <span class="member-summary-value">{{ $activitySummary['last_transaction_outlet'] }}</span>
                                        </div>
                                        <div class="member-summary-line">
                                            <span class="member-summary-label">Favourite outlet</span>
                                            <span class="member-summary-value">{{ $activitySummary['favorite_outlet'] }}</span>
                                        </div>
                                        <div class="member-summary-line">
                                            <span class="member-summary-label">Item favorit</span>
                                            <span class="member-summary-value">{{ $activitySummary['favorite_item'] }}</span>
                                        </div>
                                        <div class="member-summary-line">
                                            <span class="member-summary-label">Referral</span>
                                            <span class="member-summary-value">
                                                {{ number_format($activitySummary['referral_count'], 0, ',', '.') }} member
                                            </span>
                                        </div>
                                        <div class="member-summary-line">
                                            <span class="member-summary-label">Reward redeemed</span>
                                            <span class="member-summary-value">
                                                {{ number_format($activitySummary['reward_redeemed_count'], 0, ',', '.') }} reward
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="member-overview-panel">
                                    <div class="member-panel-header">
                                        <div>
                                            <h5 class="member-panel-title">Level Benefits</h5>
                                            <p class="member-panel-caption">Benefit level sekarang dan berikutnya</p>
                                        </div>
                                    </div>
                                    <div class="member-benefit-block">
                                        <p class="member-benefit-label">{{ $data->levelMembership?->name ?? 'Current Level' }}</p>
                                        @if ($levelBenefits['current']->isNotEmpty())
                                            <ul class="member-benefit-list">
                                                @foreach ($levelBenefits['current'] as $benefit)
                                                    <li class="member-benefit-pill">{{ $benefit->name }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="member-row-subtext mb-0">Belum ada reward level</p>
                                        @endif
                                    </div>
                                    <div class="member-benefit-block">
                                        <p class="member-benefit-label">{{ $levelBenefits['next_level_name'] ?? 'Next Level' }}</p>
                                        @if ($levelBenefits['next']->isNotEmpty())
                                            <ul class="member-benefit-list">
                                                @foreach ($levelBenefits['next'] as $benefit)
                                                    <li class="member-benefit-pill">{{ $benefit->name }}</li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <p class="member-row-subtext mb-0">Belum ada reward level berikutnya</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="transactions-pane" role="tabpanel" aria-labelledby="transactions-tab"
                        tabindex="0">
                        <div class="table-responsive">
                            {!! $dataTable->table() !!}
                        </div>
                    </div>
                    <div class="tab-pane fade" id="rewards-pane" role="tabpanel" aria-labelledby="rewards-tab"
                        tabindex="0">
                        <div class="member-tab-placeholder">TODO Rewards</div>
                    </div>
                    <div class="tab-pane fade" id="capsule-history-pane" role="tabpanel"
                        aria-labelledby="capsule-history-tab" tabindex="0">
                        <div class="member-tab-placeholder">TODO Capsule History</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modal_history_exp" tabindex="-1" role="dialog" aria-hidden="true">
        </div>
    </div>

    @push('js')
        {!! $dataTable->scripts() !!}

        <script>
            var success = "{{ session('success') }}";
            var datatable = 'listcustomertransaction-table';

            $('button[data-bs-target="#transactions-pane"]').on('shown.bs.tab', function() {
                if ($.fn.dataTable && $.fn.dataTable.tables) {
                    $($.fn.dataTable.tables(true)).DataTable().columns.adjust();
                }
            });

            handleAction(datatable);
            handleDelete(datatable, false, function(res){
                console.log(res);

                $('#point').text(res.data.point);
                $('#exp').text(res.data.exp);
                const transactionCount = res.data.transactions.length;
                const transactionNominal = parseInt(res.transactionNominal) || 0;
                const averageTransaction = transactionCount > 0 ? Math.round(transactionNominal / transactionCount) : 0;

                $('#count-transaction').text(transactionCount);
                $('#transaction-nominal').text(formatRupiah(res.transactionNominal.toString(), "Rp. "));
                $('#average-transaction').text(formatRupiah(averageTransaction.toString(), "Rp. "));
            });

            $('#btnHistoryUseExp').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: this.href,
                    method: "GET",
                    beforeSend: function() {
                        showLoader();
                    },
                    complete: function() {
                        showLoader(false);
                    },
                    success: (res) => {
                        const modal = $('#modal_history_exp');
                        modal.html(res);
                        modal.modal('show');
                    },
                    error: function(err) {
                        console.log(err);
                    }
                });
            });

            if (success) {
                Swal.fire({
                    title: 'Success!',
                    // text: 'Data User Berhasil Disimpan',
                    text: success,
                    icon: 'success',
                    type: 'success',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    },
                    buttonsStyling: false
                });
            }
        </script>
    @endpush
@endsection
