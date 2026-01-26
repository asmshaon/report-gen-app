<div class="d-flex flex-column flex-md-row justify-content-md-between align-items-md-center mb-4 gap-3">
    <h1 class="display-5 mb-0 text-nowrap">Report Manager</h1>
    <div class="d-flex flex-column align-items-end gap-2" x-data="reportGenerator()">
        <div class="d-flex flex-column flex-md-row align-items-stretch gap-2">
            <div class="input-group input-group-lg">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white"><i class="text-muted">📄</i> &nbsp; Report Type:</span>
                </div>
                <select x-model="reportType" class="form-control form-control-lg custom-select">
                    <template x-for="type in reportTypes" :key="type.value">
                        <option :value="type.value" x-text="type.label"></option>
                    </template>
                </select>
            </div>
            <button @click="generateReport" :disabled="generating" class="btn btn-success btn-lg shadow-sm px-4 report-get-btn" x-text="generating ? 'Generating...' : '🚀 Generate Report'"></button>
        </div>
        <!-- Show result message alert -->
        <div x-show="resultMessage" x-transition class="alert rounded-lg shadow-sm mb-0" :class="resultSuccess ? 'alert-success' : 'alert-danger'" style="min-width: 300px; max-width: 500px;" x-html="resultMessage"></div>
    </div>
</div>