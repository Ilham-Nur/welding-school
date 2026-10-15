<section class="admin-panel" id="dokumen-tambahan">
    <header class="admin-panel__header"><div><h2>Dokumen Tambahan</h2><p>Arsip umum di luar persyaratan jabatan.</p></div><button type="button" class="button button--outline admin-button" data-add-general-document>+ Tambah Berkas</button></header>
    <div class="admin-panel__body">
        @foreach ($employee->documents->whereNull('requirement_id')->unique('file_path') as $document)
            <p><a href="{{ route('admin.employees.documents.preview', [$employee, $document]) }}" target="_blank">{{ $document->document_label }} — {{ $document->file_name }}</a></p>
        @endforeach
        <div data-general-documents></div>
        <template data-general-document-template>
            <div class="admin-form-grid" style="margin-top: 16px" data-general-document-row>
                <label class="ui-field admin-field"><span class="ui-field__label">Nama Dokumen</span><input data-general-label maxlength="255" required></label>
                <x-ui.file-input label="Berkas Dokumen" name="general-document-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.zip" :max-size-mb="10" hint="PDF, gambar, Word, Excel, atau ZIP. Maksimum 10 MB." data-general-file required />
                <button type="button" class="button button--outline admin-button" data-remove-general-document>Batal Tambah</button>
            </div>
        </template>
    </div>
</section>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let index = 0;
            document.querySelector('[data-add-general-document]')?.addEventListener('click', () => {
                const row = document.querySelector('[data-general-document-template]').content.cloneNode(true);
                row.querySelector('[data-general-label]').name = `documents[${index}][label]`;
                const file = row.querySelector('[data-general-file]');
                file.id = `general-document-file-${index}`;
                file.name = `documents[${index++}][file]`;
                row.querySelector('[data-remove-general-document]').addEventListener('click', event => event.target.closest('[data-general-document-row]').remove());
                const container = document.querySelector('[data-general-documents]');
                container.appendChild(row);
                window.WeldingSchoolUI.initFileDrops(container);
            });
        });
    </script>
@endpush
