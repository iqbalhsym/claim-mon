@extends('layouts.noble_layout')

@section('title', 'Manajemen User')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap grid-margin mb-4">
  <div>
    <h4 class="mb-1 page-title">Pengelolaan Pengguna</h4>
    <p class="text-muted mb-0">Daftar akun yang terintegrasi melalui Active Directory dan Lokal Bypass.</p>
  </div>
</div>

<div class="row">
  <div class="col-md-12 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="card-title mb-0">Daftar Pengguna Aktif</h6>
            <span class="badge bg-primary text-white">Total: {{ $users->count() }} Akun</span>
        </div>
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Nama & Username</th>
                <th>Email</th>
                <th>Peran / Role</th>
                <th>Terakhir Login</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              @forelse($users as $user)
              <tr>
                <td>
                    <div class="fw-bold">{{ $user->name }}</div>
                    <div class="text-muted small">
                        <i data-feather="user" style="width:12px; height:12px;" class="me-1"></i>{{ $user->username ?? '-' }}
                    </div>
                </td>
                <td class="text-muted">{{ $user->email ?? '-' }}</td>
                <td>
                    @if($user->role == 'administrator')
                        <span class="badge badge-primary">Administrator</span>
                    @elseif($user->role == 'user')
                        <span class="badge badge-success">Editor</span>
                    @else
                        <span class="badge badge-warning text-dark">View Only</span>
                    @endif
                </td>
                <td class="text-muted">{{ $user->updated_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($user->id !== auth()->id())
                        <div class="d-flex gap-2">
                            <form action="{{ route('users.updateRole', $user->id) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                @method('PUT')
                                <select name="role" class="form-select form-select-sm" style="min-width: 120px;">
                                    <option value="viewer" {{ $user->role == 'viewer' ? 'selected' : '' }}>View Only</option>
                                    <option value="user"   {{ $user->role == 'user'   ? 'selected' : '' }}>Editor</option>
                                    <option value="administrator"  {{ $user->role == 'administrator'  ? 'selected' : '' }}>Administrator</option>
                                </select>
                                <button type="submit" class="btn btn-outline-primary btn-icon" title="Simpan Role" style="padding: 0.3rem 0.5rem;">
                                    <i data-feather="save" style="width: 16px; height: 16px;"></i>
                                </button>
                            </form>
                            <form action="{{ route('users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus akun {{ $user->name }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-icon" title="Hapus" style="padding: 0.3rem 0.5rem;">
                                    <i data-feather="trash-2" style="width: 16px; height: 16px;"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <span class="badge bg-light text-dark border">Akun Anda</span>
                    @endif
                </td>
              </tr>
              @empty
              <tr>
                  <td colspan="5" class="text-center text-muted py-5">Belum ada pengguna.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection