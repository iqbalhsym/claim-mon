@extends('layouts.noble_layout')

@section('title', 'Detail Rekam Medis')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4 fade-in-up">
  <div>
    <h4 class="mb-1 page-title">Detail Rekam Medis</h4>
    <p class="text-muted mb-0">Informasi lengkap rekam medis pasien — Format 2026.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="{{ route('medical-records.edit', $medicalRecord->id) }}" class="btn btn-primary btn-icon-text">
      <i class="btn-icon-prepend" data-feather="edit-2"></i> Edit
    </a>
    <a href="{{ route('medical-records.index') }}" class="btn btn-outline-secondary btn-icon-text">
      <i class="btn-icon-prepend" data-feather="arrow-left"></i> Kembali
    </a>
  </div>
</div>

<div class="row">
  {{-- Kolom Kiri: Identitas Pasien --}}
  <div class="col-md-6 mb-4 fade-in-up delay-100">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title border-bottom pb-2 mb-3">
          <i data-feather="user" class="me-2" style="width:16px;height:16px;color:var(--primary-color)"></i>
          Identitas Pasien
        </h6>
        <table class="table table-sm table-borderless mb-0">
          <tbody>
            <tr>
              <td class="text-muted fw-semibold" style="width:40%">Billing No</td>
              <td class="fw-bold text-primary">{{ $medicalRecord->billing_no ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">No RM</td>
              <td class="fw-bold">{{ $medicalRecord->no_rm ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Nama Pasien</td>
              <td class="fw-bold">{{ $medicalRecord->nama_pasien ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Guarantor</td>
              <td>{{ $medicalRecord->guarantor ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Tanggal Masuk</td>
              <td>{{ $medicalRecord->tanggal_masuk ? \Carbon\Carbon::parse($medicalRecord->tanggal_masuk)->format('d-m-Y') : '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Tanggal Pulang</td>
              <td>{{ $medicalRecord->tanggal_pulang ? \Carbon\Carbon::parse($medicalRecord->tanggal_pulang)->format('d-m-Y') : '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Ruangan Afya</td>
              <td>{{ $medicalRecord->ruangan_afya ?? '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Nama Dokter (Utama)</td>
              <td>{{ $medicalRecord->nama_dokter ?? '-' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Kolom Kanan: Status & Monitoring --}}
  <div class="col-md-6 mb-4 fade-in-up delay-200">
    <div class="card h-100">
      <div class="card-body">
        <h6 class="card-title border-bottom pb-2 mb-3">
          <i data-feather="check-circle" class="me-2" style="width:16px;height:16px;color:var(--success-color)"></i>
          Status Monitoring
        </h6>
        <table class="table table-sm table-borderless mb-0">
          <tbody>
            <tr>
              <td class="text-muted fw-semibold" style="width:45%">Status Berkas</td>
              <td>
                @if($medicalRecord->is_rm_lengkap)
                  <span class="badge badge-success">LENGKAP</span>
                @else
                  <span class="badge badge-warning">TIDAK LENGKAP</span>
                @endif
              </td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Kembali ke RM</td>
              <td>
                @if($medicalRecord->status_kembali_rm)
                  <span class="badge badge-success">YA</span>
                  @if($medicalRecord->tanggal_kembali_rm)
                    <small class="ms-2 text-muted">{{ \Carbon\Carbon::parse($medicalRecord->tanggal_kembali_rm)->format('d-m-Y') }}</small>
                  @endif
                @else
                  <span class="badge badge-danger">BELUM</span>
                @endif
              </td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Analisa</td>
              <td>
                @if($medicalRecord->status_analisa)
                  <span class="badge badge-success">YA</span>
                  @if($medicalRecord->tanggal_analisa)
                    <small class="ms-2 text-muted">{{ \Carbon\Carbon::parse($medicalRecord->tanggal_analisa)->format('d-m-Y') }}</small>
                  @endif
                @else
                  <span class="badge badge-danger">BELUM</span>
                @endif
              </td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Laporan Pembedahan</td>
              <td>
                @php
                  $lp = $medicalRecord->laporan_pembedahan;
                  $badgeClass = $lp === 'LENGKAP' ? 'badge-success' : ($lp === 'TIDAK LENGKAP' ? 'badge-warning' : 'badge-primary');
                @endphp
                <span class="badge {{ $badgeClass }}">{{ $lp ?? '-' }}</span>
              </td>
            </tr>
            <tr>
              <td class="text-muted fw-semibold">Persetujuan Tindakan</td>
              <td>
                @php
                  $pt = $medicalRecord->persetujuan_tindakan;
                  $badgeClass2 = $pt === 'LENGKAP' ? 'badge-success' : ($pt === 'TIDAK LENGKAP' ? 'badge-warning' : 'badge-primary');
                @endphp
                <span class="badge {{ $badgeClass2 }}">{{ $pt ?? '-' }}</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Rawat Inap & Kelengkapan Dokter (Full Width) --}}
  <div class="col-md-12 mb-4 fade-in-up delay-300">
    <div class="card">
      <div class="card-body">
        <div class="row">
          {{-- RAWAT INAP --}}
          <div class="col-md-4 border-end">
            <h6 class="card-title border-bottom pb-2 mb-3">Rawat Inap (Grouped)</h6>
            @php
              $riGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($medicalRecord->formulir_rawat_inap, 'Ruangan Utama');
            @endphp
            @forelse($riGroups as $group)
              <div class="mb-3">
                <div class="fw-bold text-primary small mb-1">{{ $group['group_name'] ?: 'Tanpa Nama Ruangan' }}</div>
                <ul class="list-unstyled ms-2 mb-0">
                  @foreach($group['items'] as $item)
                    <li class="d-flex align-items-center gap-2 mb-1">
                      @if($item['is_kembali'])
                        <i data-feather="check-square" style="width:14px;height:14px;color:var(--success-color)"></i>
                      @else
                        <i data-feather="square" style="width:14px;height:14px;color:var(--muted-color)"></i>
                      @endif
                      <span class="small">{{ $item['nama'] }}</span>
                      @if($item['tanggal_kembali'])
                        <small class="text-muted">({{ \Carbon\Carbon::parse($item['tanggal_kembali'])->format('d/m/y') }})</small>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            @empty
              <span class="text-muted">-</span>
            @endforelse
          </div>

          {{-- KELENGKAPAN DOKTER --}}
          <div class="col-md-4 border-end">
            <h6 class="card-title border-bottom pb-2 mb-3">Kelengkapan Dokter (Grouped)</h6>
            @php
              $kdGroups = \App\Models\MedicalRecord::normalizeGroupedFormulir($medicalRecord->kelengkapan_dokter, 'Dokter Utama');
            @endphp
            @forelse($kdGroups as $group)
              <div class="mb-3">
                <div class="fw-bold text-primary small mb-1">{{ $group['group_name'] ?: 'Tanpa Nama Dokter' }}</div>
                <ul class="list-unstyled ms-2 mb-0">
                  @foreach($group['items'] as $item)
                    <li class="d-flex align-items-center gap-2 mb-1">
                      @if($item['is_kembali'])
                        <i data-feather="check-square" style="width:14px;height:14px;color:var(--success-color)"></i>
                      @else
                        <i data-feather="square" style="width:14px;height:14px;color:var(--muted-color)"></i>
                      @endif
                      <span class="small">{{ $item['nama'] }}</span>
                      @if($item['tanggal_kembali'])
                        <small class="text-muted">({{ \Carbon\Carbon::parse($item['tanggal_kembali'])->format('d/m/y') }})</small>
                      @endif
                    </li>
                  @endforeach
                </ul>
              </div>
            @empty
              <span class="text-muted">-</span>
            @endforelse
          </div>

          {{-- FORMULIR LAIN --}}
          <div class="col-md-4">
            <h6 class="card-title border-bottom pb-2 mb-3">Formulir Lain-lain & Keterangan</h6>
            @php
              $flList = \App\Models\MedicalRecord::normalizeFormulirItems(
                  is_array($medicalRecord->formulir_lain) ? $medicalRecord->formulir_lain : []
              );
              $sbMap = ['belum_selesai'=>['Belum Selesai','bg-secondary'],'on_progres'=>['On Progres','bg-info'],'sudah_selesai'=>['Sudah Selesai','bg-success']];
            @endphp
            @forelse($flList as $fl)
              <div class="d-flex align-items-center justify-content-between mb-1">
                <span class="small">{{ $fl['nama'] }}</span>
                @php [$sbLabel,$sbClass] = $sbMap[$fl['status']??'belum_selesai'] ?? ['Belum Selesai','bg-secondary']; @endphp
                <span class="badge {{ $sbClass }}" style="font-size:0.65rem;">{{ $sbLabel }}</span>
              </div>
            @empty
              <span class="text-muted small">Tidak ada formulir lain.</span>
            @endforelse

            <div class="mt-3 pt-2 border-top">
              <small class="fw-bold d-block mb-1">Keterangan:</small>
              <p class="small text-muted">{{ $medicalRecord->keterangan_formulir ?: '-' }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Metadata baris bawah --}}
<div class="row fade-in-up delay-300">
  <div class="col-12">
    <div class="card">
      <div class="card-body d-flex justify-content-between align-items-center">
        <small class="text-muted">
          <i data-feather="clock" style="width:13px;height:13px;" class="me-1"></i>
          Dibuat: {{ $medicalRecord->created_at ? $medicalRecord->created_at->format('d M Y, H:i') : '-' }}
          &nbsp;|&nbsp;
          Diperbarui: {{ $medicalRecord->updated_at ? $medicalRecord->updated_at->format('d M Y, H:i') : '-' }}
        </small>
        <form action="{{ route('medical-records.destroy', $medicalRecord->id) }}" method="POST"
              onsubmit="return confirm('Hapus data ini secara permanen?');">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-sm btn-outline-danger">
            <i data-feather="trash-2" style="width:14px;height:14px;" class="me-1"></i> Hapus Data
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
