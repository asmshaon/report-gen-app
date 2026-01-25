<div class="card mb-5" x-data="manualPdfUploader()">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center" style="cursor: pointer" @click="isOpen = !isOpen">
        <h5 class="mb-0">Manual PDF Override</h5>
        <span class="badge badge-dark">Upload PDF directly to /reports/</span>
    </div>
    <div class="card-body" x-show="isOpen" x-collapse>
        <p class="text-muted small">Upload a PDF file directly to the reports folder. This will overwrite any existing PDF with the same name.</p>
        <form @submit.prevent="uploadManualPdf" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 form-group">
                    <label>PDF File Name (without extension)</label>
                    <input type="text" x-model="fileName" class="form-control" placeholder="e.g. 6AIStocks" required>
                    <small class="text-muted">The PDF will be saved as: <strong>[filename].pdf</strong></small>
                </div>
                <div class="col-md-6 form-group">
                    <label>Select PDF File</label>
                    <input type="file" accept="application/pdf" class="form-control-file" required>
                    <small x-show="uploadError" class="text-danger d-block mt-1" x-text="uploadError"></small>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-warning btn-block" :disabled="uploading" x-text="uploading ? 'Uploading...' : 'Upload PDF to reports folder'"></button>
            </div>
        </form>
        <div x-show="uploadSuccess" x-transition class="alert alert-success mt-3" x-text="uploadSuccess"></div>
    </div>
</div>
