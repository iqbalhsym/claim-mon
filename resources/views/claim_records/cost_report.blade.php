@extends('layouts.noble_layout')

@section('title', 'Komponen Biaya ' . ($jenisRawat === 'ranap' ? 'Ranap' : 'Rajal'))

@section('css')
<style>
  .table-cost th {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 700;
    white-space: nowrap;
    text-align: center;
    vertical-align: middle;
  }
  .table-cost td {
    font-size: 0.8rem;
    vertical-align: middle;
    white-space: nowrap;
  }
  .table-cost tr.totals-row td {
    font-weight: 700;
    background-color: rgba(var(--primary-rgb, 15, 93, 166), 0.05);
  }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
  <div>
    <h4 class="mb-1 page-title">Laporan Komponen Biaya - {{ $jenisRawat === 'ranap' ? 'Ranap' : 'Rajal' }}</h4>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.' . $jenisRawat) }}">Dashboard</a></li>
        <li class="breadcrumb-item active" aria-current="page">Komponen Biaya</li>
      </ol>
    </nav>
  </div>
  <div>
    <a href="{{ route('claim-records.cost.export', ['jenis_rawat' => $jenisRawat]) }}" class="btn btn-success btn-sm py-1.5 px-3 text-white">
      <i data-feather="download" style="width:14px;height:14px;" class="me-1"></i> Ekspor Excel
    </a>
  </div>
</div>

<div class="card shadow-sm border-0 mb-4">
  <div class="card-body">
    <h6 class="card-title mb-3">
      <i data-feather="dollar-sign" class="text-primary me-1" style="width: 18px; height: 18px;"></i> Rincian Biaya per Komponen per Bulan
    </h6>
    
    <div class="table-responsive" style="max-height: 650px; overflow: auto;">
      <table class="table table-bordered table-striped table-hover table-cost mb-0">
        <thead class="table-light sticky-top">
          <tr>
            <th class="align-middle text-start">Komponen Biaya (Hospital Tariffs)</th>
            @foreach($stats as $row)
              @php
                try {
                  $carbon = \Carbon\Carbon::createFromFormat('Y-m', $row->month_key);
                  $monthName = $carbon->translatedFormat('F Y');
                } catch (\Exception $e) {
                  $monthName = $row->month_key;
                }
              @endphp
              <th class="text-center align-middle">{{ $monthName }}</th>
            @endforeach
            <th class="text-center align-middle bg-primary text-white border-primary">Total</th>
          </tr>
        </thead>
        <tbody>
          @php
            $monthTotals = [];
            foreach($stats as $row) {
                $monthTotals[$row->month_key] = 0;
            }
            $grandTotal = 0;
          @endphp

          @forelse($fields as $field)
            @php
              $key = strtolower($field);
              $componentTotal = 0;
            @endphp
            <tr>
              <td class="fw-bold bg-light">{{ ucwords(strtolower(str_replace('_', ' ', $field))) }}</td>
              @foreach($stats as $row)
                @php
                  $val = (float)($row->$key ?? 0);
                  $componentTotal += $val;
                  $monthTotals[$row->month_key] += $val;
                @endphp
                <td class="text-end">
                  @if($val > 0)
                    Rp {{ number_format($val, 0, ',', '.') }}
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              @endforeach
              <td class="text-end fw-bold bg-primary bg-opacity-10">
                Rp {{ number_format($componentTotal, 0, ',', '.') }}
              </td>
            </tr>
            @php $grandTotal += $componentTotal; @endphp
          @empty
            <tr>
              <td colspan="{{ count($stats) + 2 }}" class="text-center py-4 text-muted">Belum ada data klaim yang terdaftar.</td>
            </tr>
          @endforelse
        </tbody>
        @if(count($stats) > 0)
          <tfoot>
            <tr class="totals-row">
              <td class="bg-light"><b>Total</b></td>
              @foreach($stats as $row)
                @php
                  $val = $monthTotals[$row->month_key] ?? 0;
                @endphp
                <td class="text-end text-primary fw-bold">
                  Rp {{ number_format($val, 0, ',', '.') }}
                </td>
              @endforeach
              <td class="text-end bg-primary text-white fw-bold">
                Rp {{ number_format($grandTotal, 0, ',', '.') }}
              </td>
            </tr>
          </tfoot>
        @endif
      </table>
    </div>
  </div>
</div>
@endsection
