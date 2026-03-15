@extends('layouts.admin')

@section('content')
<div class="bg-white rounded-xl shadow-sm p-6 max-w-2xl">
    <h3 class="text-xl font-semibold mb-1">Edit User</h3>
    <p class="text-sm text-gray-500 mb-6">Perbarui data user.</p>

    <form action="{{ route('users.update', $user->id) }}" method="POST" class="space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium mb-2">Nama</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Email</label>
            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Role</label>
            <select name="role" class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="musyrif" {{ old('role', $user->role) === 'musyrif' ? 'selected' : '' }}>Musyrif</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Password Baru</label>
            <input type="password" name="password"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
            <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Konfirmasi Password Baru</label>
            <input type="password" name="password_confirmation"
                   class="w-full border border-gray-300 rounded-lg px-4 py-2.5">
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-green-700 text-white px-5 py-2.5 rounded-lg hover:bg-green-800">
                Update
            </button>
            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-300">
                Kembali
            </a>
        </div>
    </form>
</div>
@endsection