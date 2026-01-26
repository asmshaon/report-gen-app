<div class="card mb-5" x-data="formManager()">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0" x-text="editingId ? 'Edit Report Settings' : 'Create Report Settings'"></h5>
        <button class="btn btn-sm btn-light" x-show="editingId" @click="resetForm()">Cancel Edit</button>
    </div>
    <div class="card-body">
        <form @submit.prevent="saveForm" enctype="multipart/form-data">
            <input type="hidden" name="id" x-model="formData.id">
            <!-- Hidden inputs for existing images - used when generating reports -->
            <input type="hidden" name="article_image_existing" x-model="formData.images.article_image">
            <input type="hidden" name="pdf_cover_existing" x-model="formData.images.pdf_cover_image">

            <h6 class="section-header">Basic Information</h6>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Report File Name</label>
                    <input type="text" name="file_name" x-model="formData.file_name" class="form-control" placeholder="6AIStocks" required>
                    <small class="text-muted">Used for output URL (e.g. 6AIStocks.html)</small>
                </div>
                <div class="col-md-5 form-group">
                    <label>Report Title</label>
                    <input type="text" name="report_title" x-model="formData.report_title" class="form-control" placeholder="Today's Top 6 AI Stocks" required>
                </div>
                <div class="col-md-3 form-group">
                    <label>Author Name</label>
                    <input type="text" name="author_name" x-model="formData.author_name" class="form-control" placeholder="Today's Top Stocks">
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 form-group">
                    <label>Number of Stocks</label>
                    <input type="number" name="stock_count" x-model="formData.stock_count" class="form-control" value="6" min="1">
                </div>
                <div class="col-md-3 form-group">
                    <label>Data Source</label>
                    <input type="text" name="data_source" x-model="formData.data_source" class="form-control" value="data.csv" readonly>
                </div>
                <div class="col-md-3 form-group">
                    <label>Article Image (180x180)</label>
                    <input type="file" name="article_image" class="form-control-file" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" @change="validateArticleImage($event)">
                    <small x-show="formData.images.article_image" class="text-success" x-text="'Current: ' + formData.images.article_image"></small>
                    <small x-show="articleImageError" class="text-danger d-block mt-1" x-text="articleImageError"></small>
                </div>
                <div class="col-md-3 form-group">
                    <label>PDF Cover Image</label>
                    <input type="file" name="pdf_cover" class="form-control-file" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" @change="validatePdfCover($event)">
                    <small x-show="formData.images.pdf_cover_image" class="text-success" x-text="'Current: ' + formData.images.pdf_cover_image"></small>
                    <small x-show="pdfCoverError" class="text-danger d-block mt-1" x-text="pdfCoverError"></small>
                </div>
            </div>

            <h6 class="section-header mt-4">Report Content (HTML Templates)</h6>
            <p class="small text-muted">Use [Company], [Description], [Ticker], [Exchange], [Price], [Chart], [Target Price] and [Current Date] as shortcodes.</p>

            <div class="form-group">
                <label>Report Intro HTML</label>
                <textarea name="report_intro_html" x-model="formData.content_templates.intro_html" class="form-control html-code-area" rows="4" placeholder="<p>Welcome to our daily stock analysis...</p>"></textarea>
            </div>

            <div class="form-group">
                <label>Stock Block HTML</label>
                <textarea name="stock_block_html" x-model="formData.content_templates.stock_block_html" class="form-control html-code-area" rows="6" placeholder="<div class='stock-card'><h3>[Company] ([Ticker])</h3><p>Price: [Price]</p><div>[Chart]</div></div>"></textarea>
            </div>

            <div class="form-group">
                <label>Disclaimer HTML</label>
                <textarea name="disclaimer_html" x-model="formData.content_templates.disclaimer_html" class="form-control html-code-area" rows="3" placeholder="<p><i>Disclaimer: Investment carries risk...</i></p>"></textarea>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-block" :disabled="saving" x-text="saving ? 'Saving...' : (editingId ? 'Update Report Configuration' : 'Save Report Configuration')"></button>
            </div>
        </form>
    </div>
</div>
