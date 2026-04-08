@csrf
@if ($method === 'PUT')
    @method('PUT')
@endif

<div class="ta-form-grid">
    <label class="ta-field">
        <span>Judul Tugas Akhir</span>
        <input type="text" name="title" value="{{ old('title', $project->title) }}" required maxlength="255">
        @error('title')<small>{{ $message }}</small>@enderror
    </label>

    <label class="ta-field">
        <span>Program Studi</span>
        <input type="text" name="study_program" value="{{ old('study_program', $project->study_program) }}" maxlength="255">
        @error('study_program')<small>{{ $message }}</small>@enderror
    </label>

    <label class="ta-field ta-field-full">
        <span>Abstrak Ringkas</span>
        <textarea name="abstract" rows="8">{{ old('abstract', $project->abstract) }}</textarea>
        @error('abstract')<small>{{ $message }}</small>@enderror
    </label>
</div>

<div class="ta-form-actions">
    <a href="{{ route('dashboard') }}" class="app-btn app-btn-secondary">Kembali</a>
    <button type="submit" class="app-btn app-btn-primary">{{ $submitLabel }}</button>
</div>
